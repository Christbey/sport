<?php

namespace App\Services;

use App\OpenAIFunctions\OpenAIFunctionRepository;
use GuzzleHttp\Client;

class OpenAIChatService
{
    protected Client $client;

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
     * @param string $model The OpenAI model to use (default: gpt-4-0613).
     * @param string $functionCall When to invoke functions ('auto', 'none', or specific function name).
     * @return array The OpenAI response as an array.
     */
    public function getChatCompletion(array $messages, string $model = 'gpt-3.5-turbo', string $functionCall = 'auto'): array
    {

        // Load function definitions from the repository
        $functions = OpenAIFunctionRepository::getFunctions();

        // Make the API request
        $response = $this->client->post('/v1/chat/completions', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.openai.key'),
            ],
            'json' => [
                'model' => $model,
                'messages' => $messages,
                'functions' => $functions,
                'function_call' => $functionCall, // Let OpenAI decide function calls
                'temperature' => 0.7,
            ],
        ]);

        // Decode and return the response
        return json_decode((string)$response->getBody(), true);
    }
}
