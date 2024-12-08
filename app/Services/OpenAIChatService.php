<?php

namespace App\Services;

use App\OpenAIFunctions\OpenAIFunctionRepository;
use Exception;
use GuzzleHttp\Client;
use Log;

class OpenAIChatService
{
    protected Client $client;

    /**
     * Constructor with dependency injection for the Guzzle client.
     */
    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.openai.com',
        ]);
    }

    /**
     * Get a chat completion from OpenAI, including support for function calling and evals.
     *
     * @param array $messages The conversation messages.
     *     Each message should have 'role' (system|user|assistant) and 'content'.
     * @param array $options Optional parameters:
     *     'model' => string - The OpenAI model to use (default: from config)
     *     'function_call' => string - 'auto', 'none', or a function name (default: 'auto')
     *     'temperature' => float - The sampling temperature (default: from config)
     *     'store' => bool - Whether to enable storing the request (default: true)
     *     Additional parameters can be added as needed.
     *
     * @return array The OpenAI response as an associative array.
     *
     * @throws Exception if the request fails for any reason.
     */
    public function getChatCompletion(array $messages, array $options = []): array
    {
        // Load parameters from config or options
        $model = $options['model'] ?? config('services.openai.model', 'gpt-4');
        $functionCall = $options['function_call'] ?? 'auto';
        $temperature = $options['temperature'] ?? config('services.openai.temperature', 0.7);
        $maxTokens = $options['max_tokens'] ?? 2048;
        $store = $options['store'] ?? true; // Enable storing user interactions by default

        try {
            $response = $this->client->post('/v1/chat/completions', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . config('services.openai.key'),
                    'OpenAI-Project' => 'proj_7fYt17BipO9v8sDYLe4wnYt9',
                    'OpenAI-Organization' => 'org-O2K4sDaQtL5qT9in4CWQMGLn',
                ],
                'json' => [
                    'model' => $model,
                    'messages' => $messages,
                    'functions' => $options['functions'] ?? OpenAIFunctionRepository::getFunctions(),
                    'function_call' => $functionCall,
                    'temperature' => $temperature,
                    'max_tokens' => $maxTokens,
                    'store' => $store, // Include store flag for dataset generation
                ],
            ]);

            return json_decode((string)$response->getBody(), true);
        } catch (Exception $e) {
            Log::error('OpenAI request error', [
                'error' => $e->getMessage(),
                'model' => $model,
                'messages' => $messages,
            ]);
            Log::error('Error processing chat request', [
                'exception' => $e->getMessage(),
                'response' => isset($response) ? json_encode($response) : 'No response received',
            ]);


            throw new Exception('OpenAI Chat Completion request failed', 0, $e);
        }
    }


}
