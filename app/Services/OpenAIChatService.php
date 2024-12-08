<?php

namespace App\Services;

use App\OpenAIFunctions\OpenAIFunctionRepository;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Log;

class OpenAIChatService
{
    protected Client $client;

    /**
     * Constructor with dependency injection for the Guzzle client.
     * This allows for easier testing (you can mock the client),
     * and centralized configuration in a service provider.
     *
     */
    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.openai.com',
        ]);
    }

    /**
     * Get a chat completion from OpenAI, including support for function calling.
     *
     * @param array $messages The conversation messages.
     *     Each message should have 'role' (system|user|assistant) and 'content'.
     * @param array $options Optional parameters:
     *     'model' => string - The OpenAI model to use (default: from config)
     *     'function_call' => string - 'auto', 'none', or a function name (default: 'auto')
     *     'temperature' => float - The sampling temperature (default: from config)
     *     Additional parameters can be added as needed.
     *
     * @return array The OpenAI response as an associative array.
     *
     * @throws Exception if the request fails for any reason.
     */
    public function getChatCompletion(array $messages, array $options = []): array
    {
        // Load parameters from config or options
        $model = $options['model'] ?? config('services.openai.model', 'gpt-4o-mini');
        $functionCall = $options['function_call'] ?? 'auto';
        $temperature = $options['temperature'] ?? config('services.openai.temperature', 0.7);

        // Load function definitions from the repository
        $functions = OpenAIFunctionRepository::getFunctions();

        // OPTIONAL: Validate messages format if desired
        // foreach ($messages as $msg) {
        //     if (!isset($msg['role'], $msg['content'])) {
        //         throw new \InvalidArgumentException('Each message must have a role and content.');
        //     }
        // }

        try {
            $response = $this->client->post('/v1/chat/completions', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . config('services.openai.key'),
                ],
                'json' => [
                    'model' => $model,
                    'messages' => $messages,
                    'functions' => $functions,
                    'function_call' => $functionCall,
                    'temperature' => $temperature,
                ],
            ]);

            return json_decode((string)$response->getBody(), true);

        } catch (GuzzleException $e) {
            Log::error('OpenAI request error', [
                'exception' => $e,
                'model' => $model,
                'function_call' => $functionCall,
                'temperature' => $temperature,
                'messages' => $messages,
            ]);

            throw new Exception('OpenAI Chat Completion request failed', 0, $e);
        }
    }
}
