<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\OpenAIChatService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
        $answerHtml = 'Ask your '; // Initialize with an empty string or any default content
        $conversations = $this->loadUserConversations();

        return view('chat.index', compact('answerHtml', 'conversations'));
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

            // 1) Convert Markdown to HTML
            $parsedown = new Parsedown();
            // Convert Markdown to HTML
            $assistantHtml = $parsedown->text($assistantMarkdown);

            $this->saveConversation($request->input('question'), $assistantHtml);

            // After processing the AI’s markdown:
            return response()->json([
                'answerHtml' => $assistantHtml,
                'question' => $userQuestion,
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
}
