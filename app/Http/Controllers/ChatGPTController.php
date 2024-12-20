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

        // Retrieve suggested questions from the config
        $suggestedQuestions = config('open_ai_questions.suggested_questions.nfl');

        return view('openai.index', [
            'conversations' => $conversations,
            'chatId' => $userId,
            'remainingRequests' => RateLimiter::remaining($key, $limit),
            'maxRequests' => $limit,
            'resetTime' => RateLimiter::availableIn($key),
            'suggestedQuestions' => $suggestedQuestions
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
            // Allow up to FREE_LIMIT requests
            $limit = self::FREE_LIMIT;
        } else {
            $limit = $this->getUserLimit();
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
                'content' => 'You are a sports analytics assistant that helps users with their sports queries. Focus on 
                providing insights and analysis. Structure your responses as engaging articles with proper HTML formatting 
                using Tailwind classes for styling. Do not include DOCTYPE, html, head, or body tags. Only provide the 
                inner HTML content to be inserted into the chat interface.'
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
        // Remove the database storage

        // Broadcast to others
        broadcast(new MessageSent((object)[
            'user_id' => $userId,
            'input' => $userMessage,
            'output' => $assistantResponse,
            'created_at' => now() // Use current timestamp
        ]))->toOthers();
    }

    /**
     * Clear all conversations for the current user.
     */


    public function clearConversations()
    {
        $user = auth()->user();

        // Soft delete all conversations for the user
        Conversation::where('user_id', $user->id)->delete();

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
        $user = auth()->user();

        // Fetch all non-deleted conversations for the authenticated user, ordered by creation time
        $conversations = Conversation::where('user_id', $user->id)
            ->orderBy('created_at', 'asc')
            ->get(['user_id', 'input', 'output', 'created_at']);

        return response()->json([
            'chat_history' => $conversations
        ]);
    }

}
