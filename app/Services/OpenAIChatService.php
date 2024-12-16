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
    protected Client $client;
    protected OpenAIFunctionHandler $functionHandler;

    public function __construct(OpenAIFunctionHandler $functionHandler)
    {
        $this->client = new Client(['base_uri' => 'https://api.openai.com']);
        $this->functionHandler = $functionHandler;
    }

    /**
     * Fetch chat completions from OpenAI API with optional functions (tools).
     */
    public function getChatCompletion(array $messages, array $options = []): array
    {
        $model = $options['model'] ?? config('services.openai.model', 'gpt-3.5-turbo');
        $temperature = isset($options['temperature'])
            ? (float)$options['temperature']
            : (float)config('services.openai.temperature', 0.7);

        // Desired maximum completion tokens
        $desiredOutputTokens = $options['max_completion_tokens'] ?? 3500; // Set your desired default

        // Calculate max_completion_tokens
        $maxCompletionTokens = (int)$desiredOutputTokens;

        // Ensure maxCompletionTokens does not exceed the model's limit
        // Adjust based on the model's max token limit
        // For example, if using gpt-4, the limit might be 8192 or higher
        $modelMaxTokens = $this->getModelMaxTokens($model);
        $maxCompletionTokens = min($maxCompletionTokens, $modelMaxTokens - 3000); // Reserve tokens for input and response

        $store = $options['store'] ?? true;
        $userParam = auth()->check() ? 'user-' . auth()->id() : 'guest-user';

        $rawFunctions = $options['functions'] ?? OpenAIFunctionRepository::getFunctions();

        // Transform each function definition for the request
        $tools = array_map(function ($fn) {
            return [
                'type' => 'function',
                'function' => [
                    'name' => $fn['name'],
                    'description' => $fn['description'] ?? '',
                    'parameters' => $fn['parameters'] ?? new stdClass(),
                ]
            ];
        }, $rawFunctions);

        $toolChoice = $options['tool_choice'] ?? null;
        $parallelToolCalls = $options['parallel_tool_calls'] ?? null;

        try {
            $payload = [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_completion_tokens' => $maxCompletionTokens,
                'user' => $userParam,
                'store' => $store,
            ];

            if (!empty($tools)) {
                $payload['tools'] = $tools;

                if (!is_null($toolChoice)) {
                    $payload['tool_choice'] = $toolChoice;
                }

                if (!is_null($parallelToolCalls)) {
                    $payload['parallel_tool_calls'] = (bool)$parallelToolCalls;
                }
            }

            $response = $this->client->post('/v1/chat/completions', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . config('services.openai.key'),
                ],
                'json' => $payload,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            Log::info('OpenAI response:', $result);

            // Check if we have tool calls
            $toolCalls = $result['choices'][0]['message']['tool_calls'] ?? [];
            if (!empty($toolCalls)) {
                // Assume a single tool call for simplicity
                $toolCall = $toolCalls[0];
                $functionName = $toolCall['function']['name'] ?? null;
                $functionArgs = isset($toolCall['function']['arguments'])
                    ? json_decode($toolCall['function']['arguments'], true)
                    : [];

                if ($functionName) {
                    // Invoke the local function
                    try {
                        $functionResult = $this->functionHandler->invokeFunction($functionName, $functionArgs);
                    } catch (Exception $e) {
                        Log::error("Function invocation error for {$functionName}", ['error' => $e->getMessage()]);
                        $functionResult = ['error' => 'Function call failed: ' . $e->getMessage()];
                    }

                    // Add the function result to the messages
                    $messages[] = [
                        'role' => 'function',
                        'name' => $functionName,
                        'content' => json_encode($functionResult),
                    ];

                    // Call getChatCompletion again to let the model use the function's result
                    return $this->getChatCompletion($messages, $options);
                }
            }

            // If we reach here, there's no function call or we have final answer
            return $result;
        } catch (Exception $e) {
            Log::error('Error from OpenAI API', ['error' => $e->getMessage()]);
            throw new Exception('Unable to fetch response from OpenAI.');
        }
    }

    /**
     * Get the maximum tokens allowed for a given model.
     * Adjust these values based on OpenAI's documentation and the specific models you use.
     */
    protected function getModelMaxTokens(string $model): int
    {
        $modelLimits = [
            'gpt-3.5-turbo' => 4096,
            'gpt-4' => 8192,
            // Add other models and their max tokens here
        ];

        return $modelLimits[$model] ?? 4096; // Default to 4096 if model not specified
    }

    /**
     * Estimate the number of tokens in the input messages.
     * This is a basic estimator. For precise counts, consider integrating with OpenAI's tokenizer.
     */
    protected function estimateInputTokens(array $messages): int
    {
        $tokenCount = 0;

        foreach ($messages as $message) {
            if (isset($message['content'])) {
                // Simple word count as a rough estimate
                // For better accuracy, integrate with a tokenizer
                $tokenCount += str_word_count($message['content']);
            }
        }

        return $tokenCount;
    }
}
