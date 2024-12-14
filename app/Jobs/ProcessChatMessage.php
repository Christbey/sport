<?php

namespace App\Jobs;

use App\Helpers\OpenAI;
use App\Http\Controllers\ChatGPTController;
use App\Models\Conversation;
use App\Models\OpenAICompletion;
use App\Services\OpenAIChatService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\JsonResponse;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessChatMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $userId;
    public $userMessage;

    public function __construct($userId, $userMessage)
    {
        $this->userId = $userId;
        $this->userMessage = $userMessage;
    }

    public function handle(): void
    {
        try {
            $chatService = app(OpenAIChatService::class);
            $messages = $this->prepareMessages();

            // Process the OpenAI response
            $response = $this->processOpenAIResponse($messages, $chatService);
            $content = $this->validateResponse($response);

            // Save the conversation and OpenAI completion
            $this->saveConversation($content);
            $this->saveOpenAICompletion($response, $messages);

        } catch (Exception $e) {
            Log::error('Failed to process chat message', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Prepare the messages for the OpenAI API.
     */
    private function prepareMessages(): array
    {
        $currentWeek = OpenAI::getCurrentNFLWeek();

        return OpenAI::buildConversationMessages(
            $currentWeek,
            now()->toFormattedDateString(),
            $this->userMessage
        );
    }

    /**
     * Process the OpenAI response, including handling recursive function calls.
     */
    private function processOpenAIResponse(array $messages, OpenAIChatService $chatService): array
    {
        $maxFunctionCalls = 3;
        $functionCallCount = 0;

        while ($functionCallCount < $maxFunctionCalls) {
            $response = $this->callOpenAI($chatService, $messages);
            $choice = $response['choices'][0]['message'] ?? null;

            if (!$choice) {
                throw new Exception('Invalid OpenAI response structure.');
            }

            if (!empty($choice['function_call'])) {
                $functionResult = $this->handleFunctionCall($choice['function_call']);
                $messages[] = $this->appendFunctionResult($choice['function_call']['name'], $functionResult);
                $functionCallCount++;
                continue;
            }

            if (!empty($choice['content'])) {
                return $response;
            }

            Log::warning('Unexpected OpenAI response structure, retrying...', ['response' => $response]);
        }

        throw new Exception('Maximum function call limit reached without producing final content.');
    }

    /**
     * Call the OpenAI API and return the response.
     */
    private function callOpenAI(OpenAIChatService $chatService, array $messages): array
    {
        $response = $chatService->getChatCompletion($messages, ['function_call' => 'auto']);

        // Normalize JsonResponse to an array
        if ($response instanceof JsonResponse) {
            $response = $response->getData(true);
        }

        Log::info('OpenAI API Response:', ['response' => $response]);
        return $response;
    }

    /**
     * Handle a function call by invoking the appropriate function.
     */
    private function handleFunctionCall(array $functionCall): array
    {
        $functionName = $functionCall['name'];
        $arguments = json_decode($functionCall['arguments'], true) ?? [];

        Log::info('Function Call:', ['functionName' => $functionName, 'arguments' => $arguments]);

        $functionResult = app(ChatGPTController::class)->invokeFunction($functionName, $arguments);

        // Normalize JsonResponse to an array
        if ($functionResult instanceof JsonResponse) {
            $functionResult = $functionResult->getData(true);
        }

        if (empty($functionResult) || !isset($functionResult['success']) || !$functionResult['success']) {
            throw new Exception("Function '{$functionName}' returned no valid data.");
        }

        return $functionResult;
    }

    /**
     * Append a function result to the messages array.
     */
    private function appendFunctionResult(string $functionName, array $functionResult): array
    {
        return [
            'role' => 'function',
            'name' => $functionName,
            'content' => json_encode($functionResult),
        ];
    }

    /**
     * Validate the OpenAI response content.
     */
    private function validateResponse(array $response): string
    {
        $content = $response['choices'][0]['message']['content'] ?? null;

        if (is_null($content)) {
            throw new Exception('Response content is null or missing.');
        }

        return $content;
    }

    /**
     * Save the conversation in the database.
     */
    private function saveConversation(string $content): void
    {
        Conversation::create([
            'user_id' => $this->userId,
            'input' => $this->userMessage,
            'output' => $content,
            'timestamp' => now(),
        ]);
    }

    /**
     * Save the OpenAI completion in the database.
     */
    private function saveOpenAICompletion(array $response, array $messages): void
    {
        OpenAICompletion::create([
            'user_id' => $this->userId,
            'completion_id' => $response['id'] ?? null,
            'object' => $response['object'] ?? null,
            'openai_created_at' => isset($response['created']) ? date('Y-m-d H:i:s', $response['created']) : null,
            'model' => $response['model'] ?? null,
            'system_fingerprint' => $response['system_fingerprint'] ?? null,
            'choices' => json_encode($response['choices'] ?? []),
            'usage' => json_encode($response['usage'] ?? []),
            'messages' => json_encode($messages),
        ]);
    }
}