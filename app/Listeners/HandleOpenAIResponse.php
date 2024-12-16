<?php

namespace App\Listeners;

use App\Events\OpenAIResponseReceived;
use App\Models\OpenAICompletion;
use Exception;
use Illuminate\Support\Facades\Log;

class HandleOpenAIResponse
{
    /**
     * Handle the event.
     *
     * @param OpenAIResponseReceived $event
     * @return void
     */
    public function handle(OpenAIResponseReceived $event)
    {
        $response = $event->response;

        try {
            $completionData = [
                'user_id' => auth()->id(), // Ensure user ID is provided
                'completion_id' => $response['id'] ?? null,
                'model' => $response['model'] ?? null,
                'openai_created_at' => isset($response['created']) ? now()->createFromTimestamp($response['created']) : null,
                'metadata' => json_encode($response),
                'response_messages' => json_encode($response['choices'] ?? null),
                'system_fingerprint' => $response['system_fingerprint'] ?? null,
                'object' => $response['object'] ?? null,
                'usage' => json_encode($response['usage'] ?? null),
            ];

            OpenAICompletion::create($completionData);

            Log::info('OpenAI response saved successfully', ['completion_id' => $response['id'] ?? 'unknown']);
        } catch (Exception $e) {
            Log::error('Failed to save OpenAI response:', [
                'error' => $e->getMessage(),
                'response' => $response,
            ]);
        }
    }
}
