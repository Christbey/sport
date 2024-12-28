<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\OpenAIChatService;
use Exception;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Parsedown;

class ChatGPTController extends Controller
{
protected const WEEKLY_SECONDS = 604800;
    protected const FREE_LIMIT = 5; // 7 days in seconds
    protected const PRO_LIMIT = 25;  // 5 requests per week
        protected OpenAIChatService $chatService;  // 25 requests per week

    public function __construct(OpenAIChatService $chatService)
    {
        $this->chatService = $chatService;

        // Define the rate limiter for chat requests with weekly reset
        RateLimiter::for('chat', function ($user) {
            return Limit::perPeriod(
                $this->getUserLimit(),
                self::WEEKLY_SECONDS
            )->by($user->id);
        });
    }

    private function getUserLimit(): int
    {
        $user = Auth::user();

        // Return weekly request limit based on user role
        if ($user->hasRole('pro_user')) {
            return self::PRO_LIMIT;
        }

        return self::FREE_LIMIT;
    }

    public function showChat()
    {
        $user = Auth::user();
        $answerHtml = 'Ask your question';
        $conversations = $this->loadUserConversations();
        $userLimit = $this->getUserLimit();

        $key = "chat:{$user->id}";
        $remainingRequests = $userLimit - RateLimiter::attempts($key);
        $secondsUntilReset = RateLimiter::availableIn($key);

        // Convert seconds to days and hours for better UX
        $daysUntilReset = floor($secondsUntilReset / 86400);
        $hoursUntilReset = floor(($secondsUntilReset % 86400) / 3600);

        return view('chat.index', compact(
            'answerHtml',
            'conversations',
            'userLimit',
            'remainingRequests',
            'daysUntilReset',
            'hoursUntilReset'
        ));
    }

    private function loadUserConversations()
    {
        return Conversation::where('user_id', Auth::id())
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->select('id', 'input', 'output')
            ->get();
    }

    public function clearConversations()
    {
        $deletedCount = Conversation::where('user_id', Auth::id())->delete();

        return response()->json([
            'message' => 'All conversations cleared successfully',
            'deleted_count' => $deletedCount
        ]);
    }

    public function chat(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:500'
        ]);

        $user = Auth::user();
        $key = "chat:{$user->id}";

        // Execute rate limiting with weekly window
        $executed = RateLimiter::attempt(
            $key,
            $this->getUserLimit(),
            function () use ($request, $user, $key) {
                return $this->processChatRequest($request, $user, $key);
            },
            self::WEEKLY_SECONDS
        );

        if (!$executed) {
            $seconds = RateLimiter::availableIn($key);
            $days = floor($seconds / 86400);
            $hours = floor(($seconds % 86400) / 3600);

            $timeMessage = $days > 0
                ? "{$days} days and {$hours} hours"
                : "{$hours} hours";

            return response()->json([
                'error' => "Weekly limit reached. Your limit will reset in {$timeMessage}.",
                'remaining_requests' => 0,
                'days_until_reset' => $days,
                'hours_until_reset' => $hours,
                'upgrade_url' => route('subscription.index')
            ], 429);
        }

        return $executed;
    }

    private function processChatRequest(Request $request, $user, $key)
    {
        $userQuestion = $request->input('question');
        $limit = $this->getUserLimit();

        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a helpful sports analytics assistant. Use available functions to provide detailed insights.'
            ],
            [
                'role' => 'user',
                'content' => $userQuestion
            ]
        ];

        try {
            $conversationOver = false;

            while (!$conversationOver) {
                $responseData = $this->chatService->getChatCompletion($messages);

                if (!isset($responseData['choices'][0])) {
                    break;
                }

                $message = $responseData['choices'][0]['message'] ?? [];
                if (!$message) {
                    break;
                }

                $messages[] = $message;

                if (isset($message['tool_calls']) && count($message['tool_calls']) > 0) {
                    $functionResults = $this->processFunctionCalls($message['tool_calls']);
                    $messages = array_merge($messages, $functionResults);
                } else {
                    $conversationOver = true;
                }
            }

            $assistantMessage = end($messages);
            $assistantMarkdown = $assistantMessage['content'] ?? '(No content)';

            $parsedown = new Parsedown();
            $assistantHtml = $parsedown->text($assistantMarkdown);

            $this->saveConversation($request->input('question'), $assistantHtml);

            // Calculate remaining time in days and hours
            $remainingRequests = $limit - RateLimiter::attempts($key);
            $secondsUntilReset = RateLimiter::availableIn($key);
            $daysUntilReset = floor($secondsUntilReset / 86400);
            $hoursUntilReset = floor(($secondsUntilReset % 86400) / 3600);

            return response()->json([
                'answerHtml' => $assistantHtml,
                'question' => $userQuestion,
                'remaining_requests' => $remainingRequests,
                'days_until_reset' => $daysUntilReset,
                'hours_until_reset' => $hoursUntilReset
            ]);

        } catch (Exception $e) {
            Log::error('Chat error', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function processFunctionCalls(array $toolCalls): array
    {
        $functionResults = [];

        foreach ($toolCalls as $toolCall) {
            try {
                $functionName = $toolCall['function']['name'];
                $functionArgs = json_decode($toolCall['function']['arguments'], true);

                Log::info('Invoking function from ChatGPTController', [
                    'functionName' => $functionName,
                    'functionArgs' => $functionArgs
                ]);

                $result = $this->chatService->functionHandler->invokeFunction($functionName, $functionArgs);

                $functionResults[] = [
                    'tool_call_id' => $toolCall['id'],
                    'role' => 'tool',
                    'name' => $functionName,
                    'content' => json_encode($result)
                ];
            } catch (Exception $e) {
                Log::error('Function call error', [
                    'function' => $functionName ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
                $functionResults[] = [
                    'tool_call_id' => $toolCall['id'] ?? '',
                    'role' => 'tool',
                    'name' => $functionName ?? 'unknown',
                    'content' => json_encode([
                        'error' => 'Function call failed',
                        'details' => $e->getMessage()
                    ])
                ];
            }
        }

        return $functionResults;
    }

    private function saveConversation($input, $output)
    {
        Conversation::create([
            'user_id' => Auth::id(),
            'input' => $input,
            'output' => $output,
        ]);
    }
}