<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\OpenAIResponseReceived;
use App\Models\Conversation;
use App\Services\OpenAIChatService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatGPTController extends Controller
{
    protected OpenAIChatService $chatService;

    public function __construct(OpenAIChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Show the chat view.
     */
    public function showChat()
    {
        $userId = auth()->id();
        $conversations = Conversation::where('user_id', $userId)
            ->orderBy('created_at', 'asc')
            ->get();

        return view('openai.index', [
            'conversations' => $conversations,
            'chatId' => $userId, // Use user ID or any unique identifier
        ]);
    }

    /**
     * Handle chat messages and OpenAI response.
     */
    public function ask(Request $request)
    {
        $request->validate(['question' => 'required|string|max:500']);

        $userMessage = $request->input('question');
        $userId = auth()->id();

        try {
            // Fetch OpenAI response
            $response = $this->chatService->getChatCompletion([
                [
                    'role' => 'system',
                    'content' => 'You are an NFL assistant that helps users with their NFL queries. Focus on providing insights and analysis. Structure your responses as engaging articles with proper HTML formatting using Tailwind classes for styling. Never use markdown tables.'
                ],
                ['role' => 'user', 'content' => $userMessage],
            ]);

            $assistantResponse = $response['choices'][0]['message']['content'] ?? 'No response from OpenAI.';

            // Store conversation in session
            $chatHistory = session('chat_history', []);
            $chatHistory[] = [
                'user_id' => $userId,
                'input' => htmlspecialchars($userMessage, ENT_QUOTES, 'UTF-8'),
                'output' => $assistantResponse,
                'created_at' => now(),
            ];
            session(['chat_history' => $chatHistory]);

            // Broadcast the last message
            $lastMessage = (object)end($chatHistory);
            broadcast(new MessageSent($lastMessage))->toOthers();

            // Dispatch the event to handle and save the OpenAI response
            event(new OpenAIResponseReceived($response));

            return response()->json(['status' => 'success', 'response' => $assistantResponse]);
        } catch (Exception $e) {
            Log::error('Error interacting with OpenAI API:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'An error occurred while processing your request.'], 500);
        }
    }

    /**
     * Clear all conversations for the current user.
     */
    public function clearConversations()
    {
        $userId = auth()->id();

        // Clear conversations from database
        Conversation::where('user_id', $userId)->delete();

        // Clear session data
        session()->forget('chat_history');
        session()->forget('conversation_context');
        session()->forget('conversation_state');

        return response()->json([
            'status' => 'success',
            'message' => 'Conversations and session data cleared successfully',
        ]);
    }

    /**
     * Load chat history.
     */
    public function loadChat()
    {
        $chatHistory = session('chat_history', []);
        return response()->json(['chat_history' => $chatHistory]);
    }
}
