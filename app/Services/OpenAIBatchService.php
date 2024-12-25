<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIBatchService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1';
    private int $maxRetries = 3;
    private int $retryDelay = 5; // seconds
    private int $maxBatchSize = 50000; // OpenAI's limit

    public function __construct()
    {
        $this->apiKey = config('services.openai.key');

    }

    /**
     * Process multiple chat completion requests using OpenAI's Batch API
     *
     * @param array $requests Array of requests to process
     * @param string $model The model to use for processing
     * @return array Array of responses
     */
    public function processBatch(array $requests, string $model = 'gpt-3.5-turbo-0125'): array
    {
        try {
            // 1. Create JSONL file
            $inputFilePath = $this->createBatchFile($requests, $model);

            // 2. Upload file to OpenAI
            $fileId = $this->uploadFile($inputFilePath);

            // 3. Create batch processing job
            $batch = $this->createBatch($fileId);

            // 4. Monitor batch progress
            $completedBatch = $this->monitorBatchProgress($batch['id']);

            // 5. Download and parse results
            return $this->processResults($completedBatch);

        } catch (Exception $e) {
            Log::error('Batch processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Create a JSONL file from the requests
     */
    private function createBatchFile(array $requests, string $model): string
    {
        $batchLines = [];
        foreach ($requests as $index => $request) {
            $batchLines[] = json_encode([
                'custom_id' => $request['metadata']['game_id'] ?? "request-{$index}",
                'method' => 'POST',
                'url' => '/v1/chat/completions',
                'body' => [
                    'model' => $model,
                    'messages' => $request['messages'],
                    'temperature' => $request['options']['temperature'] ?? 0.7,
                    'max_tokens' => $request['options']['max_tokens'] ?? 4000,
                    'presence_penalty' => $request['options']['presence_penalty'] ?? 0.3,
                    'frequency_penalty' => $request['options']['frequency_penalty'] ?? 0.3,
                ]
            ]);
        }

        $filePath = storage_path('app/batch_input.jsonl');
        file_put_contents($filePath, implode("\n", $batchLines));

        return $filePath;
    }

    /**
     * Upload the JSONL file to OpenAI
     */
    private function uploadFile(string $filePath): string
    {
        $response = Http::withToken($this->apiKey)
            ->attach('file', fopen($filePath, 'r'), 'batch_input.jsonl')
            ->post("{$this->baseUrl}/files", [
                'purpose' => 'batch'
            ]);

        if (!$response->successful()) {
            throw new Exception('Failed to upload batch file: ' . $response->body());
        }

        return $response->json('id');
    }

    /**
     * Create a batch processing job
     */
    private function createBatch(string $fileId): array
    {
        $response = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/batches", [
                'input_file_id' => $fileId,
                'endpoint' => '/v1/chat/completions',
                'completion_window' => '24h'
            ]);

        if (!$response->successful()) {
            throw new Exception('Failed to create batch: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Monitor batch processing progress
     */
    private function monitorBatchProgress(string $batchId): array
    {
        $attempts = 0;
        $maxAttempts = $this->maxRetries;
        $delay = $this->retryDelay;

        while ($attempts < $maxAttempts) {
            $response = Http::withToken($this->apiKey)
                ->get("{$this->baseUrl}/batches/{$batchId}");

            if (!$response->successful()) {
                throw new Exception('Failed to check batch status: ' . $response->body());
            }

            $batch = $response->json();
            $status = $batch['status'];

            switch ($status) {
                case 'completed':
                    return $batch;
                case 'failed':
                    throw new Exception('Batch processing failed: ' . json_encode($batch['errors']));
                case 'expired':
                    throw new Exception('Batch processing expired');
                case 'cancelled':
                    throw new Exception('Batch processing was cancelled');
            }

            $attempts++;
            if ($attempts < $maxAttempts) {
                sleep($delay);
                $delay *= 2; // Exponential backoff
            }
        }

        throw new Exception('Batch monitoring timed out');
    }

    /**
     * Process the results from the completed batch
     */
    private function processResults(array $completedBatch): array
    {
        // Download output file
        $outputResponse = Http::withToken($this->apiKey)
            ->get("{$this->baseUrl}/files/{$completedBatch['output_file_id']}/content");

        if (!$outputResponse->successful()) {
            throw new Exception('Failed to download results: ' . $outputResponse->body());
        }

        // Parse JSONL response
        $results = [];
        $lines = explode("\n", trim($outputResponse->body()));
        foreach ($lines as $line) {
            $result = json_decode($line, true);
            if ($result) {
                $results[$result['custom_id']] = $result;
            }
        }

        // Check for errors
        if (isset($completedBatch['error_file_id'])) {
            $errorResponse = Http::withToken($this->apiKey)
                ->get("{$this->baseUrl}/files/{$completedBatch['error_file_id']}/content");

            if ($errorResponse->successful()) {
                $errorLines = explode("\n", trim($errorResponse->body()));
                foreach ($errorLines as $line) {
                    $error = json_decode($line, true);
                    if ($error) {
                        $results[$error['custom_id']]['error'] = $error['error'];
                    }
                }
            }
        }

        return $results;
    }

    public function submitBatch(array $requests, string $model = 'gpt-4o-mini'): array
    {
        try {
            // Create and upload JSONL file
            $inputFilePath = $this->createBatchFile($requests, $model);
            $fileId = $this->uploadFile($inputFilePath);

            // Create batch processing job
            $batchResponse = Http::withToken($this->apiKey)
                ->post("{$this->baseUrl}/batches", [
                    'input_file_id' => $fileId,
                    'endpoint' => '/v1/chat/completions',
                    'completion_window' => '24h'
                ]);

            if (!$batchResponse->successful()) {
                throw new Exception('Failed to create batch: ' . $batchResponse->body());
            }

            $batch = $batchResponse->json();

            return [
                'batch_id' => $batch['id'],
                'file_id' => $fileId,
                'status' => $batch['status'],
                'created_at' => now(),
                'request_count' => count($requests)
            ];

        } catch (Exception $e) {
            Log::error('Batch submission failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Set the maximum number of retries for batch monitoring
     */
    public function setMaxRetries(int $retries): self
    {
        $this->maxRetries = $retries;
        return $this;
    }

    /**
     * Set the delay between retry attempts
     */
    public function setRetryDelay(int $seconds): self
    {
        $this->retryDelay = $seconds;
        return $this;
    }

    /**
     * Retrieve results for a previously submitted batch
     *
     * @param string $batchId The ID of the batch to retrieve
     * @return array Contains batch status and results if completed
     */
    public function retrieveBatchResults(string $batchId): array
    {
        try {
            // Check batch status
            $response = Http::withToken($this->apiKey)
                ->get("{$this->baseUrl}/batches/{$batchId}");

            if (!$response->successful()) {
                throw new Exception('Failed to check batch status: ' . $response->body());
            }

            $batch = $response->json();
            $status = $batch['status'];

            // Return early if not completed
            if ($status !== 'completed') {
                return [
                    'status' => $status,
                    'request_counts' => $batch['request_counts'] ?? [],
                    'results' => null
                ];
            }

            // Download results if completed
            $results = [];

            // Get successful results
            if (isset($batch['output_file_id'])) {
                $outputResponse = Http::withToken($this->apiKey)
                    ->get("{$this->baseUrl}/files/{$batch['output_file_id']}/content");

                if ($outputResponse->successful()) {
                    $lines = explode("\n", trim($outputResponse->body()));
                    foreach ($lines as $line) {
                        $result = json_decode($line, true);
                        if ($result) {
                            $results[$result['custom_id']] = $result;
                        }
                    }
                }
            }

            // Get any errors
            if (isset($batch['error_file_id'])) {
                $errorResponse = Http::withToken($this->apiKey)
                    ->get("{$this->baseUrl}/files/{$batch['error_file_id']}/content");

                if ($errorResponse->successful()) {
                    $errorLines = explode("\n", trim($errorResponse->body()));
                    foreach ($errorLines as $line) {
                        $error = json_decode($line, true);
                        if ($error) {
                            $results[$error['custom_id']]['error'] = $error['error'];
                        }
                    }
                }
            }

            return [
                'status' => $status,
                'request_counts' => $batch['request_counts'] ?? [],
                'results' => $results
            ];

        } catch (Exception $e) {
            Log::error('Batch retrieval failed', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

}