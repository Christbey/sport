<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\OpenAIChatService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Parsedown;

class ChatGPTController extends Controller
{
    protected OpenAIChatService $chatService;

    public function __construct(OpenAIChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function showChat()
    {
        $user = Auth::user();
        $answerHtml = 'Ask your '; // Initialize with an empty string or any default content
        $conversations = $this->loadUserConversations();
        $userLimit = $this->getUserLimit();

        $key = "chat:{$user->id}";
        $remainingRequests = $userLimit - RateLimiter::attempts($key);
        $secondsUntilReset = RateLimiter::availableIn($key);

        return view('chat.index', compact('answerHtml', 'conversations', 'userLimit', 'remainingRequests', 'secondsUntilReset'));
    }

    private function loadUserConversations()
    {
        return Conversation::where('user_id', Auth::id())
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->select('id', 'input', 'output')
            ->get();
    }

    private function getUserLimit(): int
    {
        $user = Auth::user();

        // Check permissions based on role
        if ($user->hasRole('pro_user')) {
            return config('chat.limits.pro', 100); // Store in config
        }

        // Default to free user limit
        return config('chat.limits.free', 5);
    }

    public function clearConversations()
    {
        $deletedCount = Conversation::where('user_id', Auth::id())->delete();

        return response()->json([
            'message' => 'All conversations cleared successfully',
            'deleted_count' => $deletedCount
        ]);
    }

    /**
     * Repeatedly call OpenAI until all function calls are resolved,
     * then return the final text to the user.
     */
    public function chat(Request $request)
    {
        // Validate input
        $request->validate([
            'question' => 'required|string|max:500'
        ]);

        $user = Auth::user();
        $key = "chat:{$user->id}";
        $limit = $this->getUserLimit();

        // Check rate limit
        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'error' => "Rate limit exceeded. Please try again in {$seconds} seconds.",
                'remaining_requests' => 0,
                'seconds_until_reset' => $seconds,
                'upgrade_url' => route('subscription.index') // Add upgrade link
            ], 429);
        }

        // If within rate limit, hit the limiter
        RateLimiter::hit($key);

        $userQuestion = $request->input('question');

        // Create our initial conversation messages
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

                // Add assistant message to our conversation
                $messages[] = $message;

                // Check function calls
                if (isset($message['tool_calls']) && count($message['tool_calls']) > 0) {
                    $functionResults = $this->processFunctionCalls($message['tool_calls']);
                    $messages = array_merge($messages, $functionResults);
                } else {
                    // No more function calls => final text
                    $conversationOver = true;
                }
            }

            // The last assistant message likely has the final text
            $assistantMessage = end($messages);
            $assistantMarkdown = $assistantMessage['content'] ?? '(No content)';

            // Convert Markdown to HTML
            $parsedown = new Parsedown();
            $assistantHtml = $parsedown->text($assistantMarkdown);

            $this->saveConversation($request->input('question'), $assistantHtml);

            // After processing the AI's markdown:
            return response()->json([
                'answerHtml' => $assistantHtml,
                'question' => $userQuestion,
                'remaining_requests' => $limit - RateLimiter::attempts($key),
                'seconds_until_reset' => RateLimiter::availableIn($key)
            ]);

        } catch (Exception $e) {
            Log::error('Chat error', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Process function calls
     */
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

                // Actually call the function
                $result = $this->chatService->functionHandler->invokeFunction($functionName, $functionArgs);

                // Return the result as a "tool" role message
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

    public function generateTweet(Request $request)
    {
        // Validate the input data
        $request->validate([
            'data' => 'required|array', // Ensure data is passed as an array
        ]);

        $data = $request->input('data'); // The data to base the tweet on

        // Format the user prompt for the AI
        $prompt = 'Based on the following data, generate a short and engaging tweet (under 280 characters): ' . json_encode($data);

        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a social media assistant. Create concise and engaging tweets based on the provided data.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];

        try {
            // Call the chat service to get the tweet suggestion
            $responseData = $this->chatService->getChatCompletion($messages, [
                'temperature' => 0.8, // Higher creativity for tweets
                'max_tokens' => 100, // Short output
            ]);

            $tweet = $responseData['choices'][0]['message']['content'] ?? 'Unable to generate tweet';

            return response()->json([
                'tweet' => $tweet,
                'data' => $data
            ]);
        } catch (Exception $e) {
            Log::error('Tweet generation error', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


}
