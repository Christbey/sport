<?php

namespace App\Console\Commands;

use App\Models\Nfl\NflTeamSchedule;
use App\Models\Post;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RetrieveBatchAnalysis extends Command
{
    protected $signature = 'batch:retrieve {week} {--season=}';
    protected $description = 'Retrieve and process batch analysis results';

    public function handle()
    {
        $week = (int)$this->argument('week');
        $season = $this->option('season') ?? now()->year;

        // Get batch info from storage
        $batchInfoFile = storage_path("app/batch_info_{$week}_{$season}.json");
        if (!file_exists($batchInfoFile)) {
            $this->error("No batch info found for week {$week}");
            return 1;
        }

        $batchInfo = json_decode(file_get_contents($batchInfoFile), true);
        $batchId = $batchInfo['batch_id'];

        // Check batch status with proper authorization
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openai.key'),
            'Content-Type' => 'application/json'
        ])->get("https://api.openai.com/v1/batches/{$batchId}");

        if (!$response->successful()) {
            $this->error('Failed to retrieve batch status: ' . $response->body());
            return 1;
        }

        $batch = $response->json();
        $this->info("Batch status: {$batch['status']}");

        if ($batch['status'] !== 'completed') {
            $this->info('Batch not yet completed. Current counts: ' . json_encode($batch['request_counts']));
            return 0;
        }

        // Get games for result processing
        $games = NflTeamSchedule::where('game_week', $week)
            ->where('season', $season)
            ->get()
            ->keyBy('game_id');

        // Download results
        if ($batch['output_file_id']) {
            $outputResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.key'),
                'Content-Type' => 'application/json'
            ])->get("https://api.openai.com/v1/files/{$batch['output_file_id']}/content");

            if ($outputResponse->successful()) {
                $results = collect(explode("\n", $outputResponse->body()))
                    ->filter()
                    ->map(fn($line) => json_decode($line, true))
                    ->keyBy('custom_id');

                // Process results and create posts
                $this->processResults($results, $games, $week, $season);
            }
        }

        // Check for errors
        if ($batch['error_file_id']) {
            $errorResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.key'),
                'Content-Type' => 'application/json'
            ])->get("https://api.openai.com/v1/files/{$batch['error_file_id']}/content");

            if ($errorResponse->successful()) {
                $this->error('Errors encountered:');
                foreach (explode("\n", $errorResponse->body()) as $line) {
                    if ($line) {
                        $this->error(json_encode(json_decode($line, true)));
                    }
                }
            }
        }

        return 0;
    }

    private function processResults($results, $games, $week, $season)
    {
        foreach ($results as $gameId => $result) {
            if (!isset($games[$gameId])) {
                $this->error("Game not found for ID: {$gameId}");
                continue;
            }

            $game = $games[$gameId];

            if (isset($result['error'])) {
                $this->error("Error for game {$gameId}: " . json_encode($result['error']));
                continue;
            }

            try {
                $analysis = $result['response']['body']['choices'][0]['message']['content']
                    ?? 'Unable to generate analysis.';

                // Extract prediction (using the same logic from your original code)
                $prediction = '';
                if (preg_match('/#### Prediction\s*\n([\s\S]+)/i', $analysis, $matches)) {
                    $prediction = trim($matches[1]);
                }

                // Create or update post
                $post = Post::updateOrCreate(
                    ['game_id' => $gameId],
                    [
                        'title' => "NFL Week {$week} Showdown: {$game->away_team} vs {$game->home_team}",
                        'slug' => Str::slug("NFL Week {$week} Showdown: {$game->away_team} vs {$game->home_team}"),
                        'content' => $analysis,
                        'week' => $week,
                        'season' => $season,
                        'away_team' => $game->away_team,
                        'home_team' => $game->home_team,
                        'game_date' => $game->game_date,
                        'game_time' => $game->game_time,
                        'prediction' => $prediction,
                        'published' => false,
                    ]
                );

                $this->info("Post created successfully: {$post->title}");
            } catch (Exception $e) {
                $this->error("Error processing game {$gameId}: {$e->getMessage()}");
                Log::error('Result Processing Error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'game_id' => $gameId
                ]);
            }
        }
    }
}