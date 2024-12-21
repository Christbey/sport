<?php

namespace App\Services;

use App\OpenAIFunctions\OpenAIFunctionHandler;
use App\OpenAIFunctions\OpenAIFunctionRepository;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use stdClass;

class OpenAIChatService
{
    public OpenAIFunctionHandler $functionHandler;
    protected Client $client;

    public function __construct(OpenAIFunctionHandler $functionHandler)
    {
        $this->client = new Client(['base_uri' => 'https://api.openai.com']);
        $this->functionHandler = $functionHandler;
    }

    /**
     * Send chat completions to OpenAI with optional function calling (non-streaming)
     */
    public function getChatCompletion(array $messages, array $options = []): array
    {
        Log::info('OpenAIChatService::getChatCompletion called', [
            'messages' => $messages,
            'options' => $options,
        ]);

        $model = $options['model'] ?? config('services.openai.model', 'gpt-4o-mini');
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 3500;

        // Get functions from the repository
        $rawFunctions = OpenAIFunctionRepository::getFunctions();
//        Log::debug('Fetched raw functions from repository', [
//            'rawFunctions' => $rawFunctions
//        ]);

        // Transform functions for OpenAI API format
        $tools = array_map(function ($fn) {
            return [
                'type' => 'function',
                'function' => [
                    'name' => $fn['name'],
                    'description' => $fn['description'],
                    'parameters' => $fn['parameters'] ?? new stdClass(),
                ]
            ];
        }, $rawFunctions);
//        Log::debug('Transformed functions for OpenAI API format', [
//            'tools' => $tools
//        ]);

        try {
            $payload = [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
                'stream' => false, // Non-streaming
                'store' => true,
                'tools' => $tools,
                'tool_choice' => 'auto',
            ];

//            Log::info('Sending non-streaming request to OpenAI', [
//                'endpoint' => '/v1/chat/completions',
//                'payload' => $payload,
//            ]);

            $response = $this->client->post('/v1/chat/completions', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . config('services.openai.key'),
                    'Accept' => 'application/json'
                ],
                'json' => $payload
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new Exception('OpenAI returned status ' . $response->getStatusCode());
            }

            $responseData = json_decode($response->getBody(), true);

            Log::info('Received response from OpenAI', ['responseData' => $responseData]);

            return $responseData;
        } catch (Exception $e) {
            Log::error('OpenAI Request Error', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
