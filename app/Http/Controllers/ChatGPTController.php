<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\OpenAIResponseReceived;
use App\Models\Conversation;
use App\Models\Plan;
use App\Services\OpenAIChatService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class ChatGPTController extends Controller
{
    private const FREE_LIMIT = 5;
    private const DECAY_SECONDS = 3600; // 1 hour window

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
        $key = $this->getRateLimitKey($userId);
        $limit = $this->getUserLimit();

        $conversations = Conversation::where('user_id', $userId)
            ->orderBy('created_at', 'asc')
            ->get();

        return view('openai.index', [
            'conversations' => $conversations,
            'chatId' => $userId,
            'remainingRequests' => RateLimiter::remaining($key, $limit),
            'maxRequests' => $limit,
            'resetTime' => RateLimiter::availableIn($key)
        ]);
    }

    /**
     * Get the rate limiting key for a user.
     */
    private function getRateLimitKey(int $userId): string
    {
        return "chat:{$userId}";
    }

    /**
     * Get the rate limit for the current user.
     */
    private function getUserLimit(): int
    {
        $user = auth()->user();

        // Retrieve the active 'default' subscription
        $subscription = $user->subscription('default');

        if ($subscription) {
            // Fetch the corresponding plan based on stripe_price_id
            $plan = Plan::where('stripe_price_id', $subscription->stripe_price)->first();

            if ($plan) {
                return $plan->limit;
            } else {
                // Log the missing plan for debugging purposes
                \Log::warning("No plan found for stripe_price_id: {$subscription->stripe_price} for user ID: {$user->id}");
                return self::FREE_LIMIT;
            }
        }

        // User is not subscribed; return free limit
        return self::FREE_LIMIT;
    }

    /**
     * Handle chat messages and OpenAI response.
     */
    public function ask(Request $request)
    {
        $request->validate(['question' => 'required|string|max:500']);

        $user = auth()->user();
        $key = $this->getRateLimitKey($user->id);
        $limit = $this->getUserLimit();

        // Check subscription status first
        if (!$user->subscribed('default')) {
            return response()->json([
                'message' => 'Subscription required to continue.',
                'redirect' => route('subscription.index')
            ], 403);
        }

        // Then check rate limit for subscribed users
        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'error' => "Rate limit exceeded. Please try again in {$seconds} seconds.",
                'remaining_requests' => 0,
                'seconds_until_reset' => $seconds
            ], 429);
        }

        try {
            // Process the chat request
            $response = $this->processChat($request->question);

            // Record the hit with decay time
            RateLimiter::hit($key, self::DECAY_SECONDS);

            return response()->json([
                'status' => 'success',
                'response' => $response['assistantResponse'],
                'remaining_requests' => RateLimiter::remaining($key, $limit),
                'seconds_until_reset' => RateLimiter::availableIn($key)
            ]);
        } catch (Exception $e) {
            Log::error('ChatGPT Error:', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'message' => $request->question
            ]);

            return response()->json([
                'error' => 'An error occurred while processing your request.',
                'remaining_requests' => RateLimiter::remaining($key, $limit)
            ], 500);
        }
    }

    /**
     * Process the chat interaction with OpenAI.
     */
    private function processChat(string $userMessage): array
    {
        $user = auth()->user();

        $response = $this->chatService->getChatCompletion([
            [
                'role' => 'system',
                'content' => 'You are an NFL assistant that helps users with their NFL queries. Focus on providing insights and analysis. Structure your responses as engaging articles with proper HTML formatting using Tailwind classes for styling. Never use markdown tables.'
            ],
            ['role' => 'user', 'content' => $userMessage],
        ]);

        $assistantResponse = $response['choices'][0]['message']['content'] ?? 'No response from OpenAI.';

        // Store and broadcast the conversation
        $this->storeAndBroadcastMessage($user->id, $userMessage, $assistantResponse);

        // Dispatch event for the response
        event(new OpenAIResponseReceived($response));

        return [
            'assistantResponse' => $assistantResponse
        ];
    }

    /**
     * Store and broadcast the chat message.
     */
    private function storeAndBroadcastMessage(int $userId, string $userMessage, string $assistantResponse): void
    {
        $newMessage = [
            'user_id' => $userId,
            'input' => htmlspecialchars($userMessage, ENT_QUOTES, 'UTF-8'),
            'output' => $assistantResponse,
            'created_at' => now(),
        ];

        // Store in session
        $chatHistory = session('chat_history', []);
        $chatHistory[] = $newMessage;
        session(['chat_history' => $chatHistory]);

        // Store in database
        Conversation::create([
            'user_id' => $userId,
            'input' => $userMessage,
            'output' => $assistantResponse
        ]);

        // Broadcast to others
        broadcast(new MessageSent((object)$newMessage))->toOthers();
    }

    /**
     * Clear all conversations for the current user.
     */
    public function clearConversations()
    {
        session()->forget([
            'chat_history',
            'conversation_context',
            'conversation_state'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Conversations cleared successfully'
        ]);
    }

    /**
     * Load chat history.
     */
    public function loadChat()
    {
        return response()->json([
            'chat_history' => session('chat_history', [])
        ]);
    }
}
