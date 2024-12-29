<?php

namespace App\Console\Commands;

use App\Models\Nfl\OddsApiNfl;
use App\Models\Post;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RetrievePlayerTrendsBatch extends Command
{
    protected $signature = 'batch:retrieve-player-trends {week} {--season=}';
    protected $description = 'Retrieve and process player trends batch results';

    private string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        parent::__construct();
        $this->apiKey = config('services.openai.key');
    }

    public function handle(): int
    {
        try {
            $week = (int)$this->argument('week');
            $season = $this->option('season') ?? now()->year;

            $filePath = storage_path("app/batch_info_{$week}_{$season}_player_trends.json");
            if (!file_exists($filePath)) {
                $this->error("No batch info found for Week {$week}, Season {$season}.");
                return 1;
            }

            $batchInfo = json_decode(file_get_contents($filePath), true);
            $batchResults = $this->retrieveBatchResults($batchInfo['batch_id']);

            if ($batchResults['status'] !== 'completed') {
                $this->warn("Batch processing is not completed. Current status: {$batchResults['status']}");
                return 0;
            }

            // -----------------------------------------
            // FIX: Extract actual event IDs before querying
            // -----------------------------------------
            $allCustomIds = array_keys($batchResults['results']);

            // Strip off '-0', '-1', etc., to get the real event_id
            $eventIds = collect($allCustomIds)
                ->map(function ($customId) {
                    return explode('-', $customId)[0];
                })
                ->unique() // In case of duplicates
                ->values()
                ->toArray();

            // Query the DB using the stripped IDs
            $events = OddsApiNfl::whereIn('event_id', $eventIds)->get()->keyBy('event_id');
            // -----------------------------------------

            $this->processResults($batchResults['results'], $events, $week, $season);

            $this->info('Batch results processed successfully.');
            return 0;
        } catch (Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            Log::error('Retrieve Batch Error', ['exception' => $e]);
            return 1;
        }
    }

    private function retrieveBatchResults(string $batchId): array
    {
        $response = Http::withToken($this->apiKey)->get("{$this->baseUrl}/batches/{$batchId}");
        if (!$response->successful()) {
            throw new Exception('Failed to fetch batch status: ' . $response->body());
        }

        $batch = $response->json();
        if ($batch['status'] !== 'completed') {
            return [
                'status' => $batch['status'],
                'results' => null,
            ];
        }

        $outputResponse = Http::withToken($this->apiKey)->get("{$this->baseUrl}/files/{$batch['output_file_id']}/content");
        if (!$outputResponse->successful()) {
            throw new Exception('Failed to download batch results: ' . $outputResponse->body());
        }

        $results = [];
        $lines = explode("\n", trim($outputResponse->body()));
        foreach ($lines as $line) {
            $result = json_decode($line, true);
            if ($result) {
                $results[$result['custom_id']] = $result;
            }
        }

        return [
            'status' => 'completed',
            'results' => $results,
        ];
    }

    private function processResults(array $results, $events, int $week, int $season): void
    {
        $this->info('Processing results...');
        $bar = $this->output->createProgressBar(count($results));
        $bar->start();

        // Log available event IDs in the database
        $availableEventIds = $events->keys();
        Log::info('Available event IDs in database:', $availableEventIds->toArray());

        foreach ($results as $result) {
            $customId = $result['custom_id'] ?? null;

            // Extract the event_id from the custom_id
            $extractedEventId = $customId ? explode('-', $customId)[0] : null;

            // Log the processing details
            Log::info('Processing custom_id:', [
                'custom_id' => $customId,
                'extracted_event_id' => $extractedEventId,
                'full_result' => $result, // Log the entire result for deeper inspection
            ]);

            // Check for matching event in database
            if (!$extractedEventId || !$events->has($extractedEventId)) {
                $this->warn('No matching game for result');
                Log::warning('No matching game for result', [
                    'custom_id' => $customId,
                    'extracted_event_id' => $extractedEventId,
                    'full_result' => $result, // Log the full result to understand why it failed
                ]);
                continue;
            }

            $event = $events[$extractedEventId];
            $this->processEventResult($customId, $result, $event, $week, $season);
            $bar->advance();
        }

        $bar->finish();
    }

    private function processEventResult(string $eventId, array $result, OddsApiNfl $event, int $week, int $season): void
    {
        if (isset($result['error'])) {
            $this->warn("Error in batch result for event {$eventId}: " . json_encode($result['error']));
            return;
        }

        $analysis = $result['response']['body']['choices'][0]['message']['content'] ?? 'Unable to generate analysis.';
        $this->createOrUpdatePost($eventId, $event, $analysis, $week, $season);
    }

    private function createOrUpdatePost(string $eventId, OddsApiNfl $event, string $analysis, int $week, int $season): void
    {
        Post::updateOrCreate(
            ['game_id' => $eventId],
            [
                'title' => "NFL Week {$week} Player Trends: {$event->away_team} vs {$event->home_team}",
                'slug' => Str::slug("NFL Week {$week} Player Trends: {$event->away_team} vs {$event->home_team}"),
                'content' => $analysis,
                'week' => $week,
                'season' => $season,
                'away_team' => $event->away_team,
                'home_team' => $event->home_team,
                'game_date' => $event->datetime->format('Y-m-d'),
                'game_time' => $event->datetime->format('H:i:s'),
                'published' => true,
                'user_id' => 1 // Admin user
            ]
        );

        // Example: you might want to update a record in the database, etc.
        // $event->update([...]);
    }

}
