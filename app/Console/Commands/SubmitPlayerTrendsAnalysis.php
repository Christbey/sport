<?php

namespace App\Console\Commands;

use App\Models\Nfl\OddsApiNfl;
use App\Models\Nfl\PlayerTrend;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SubmitPlayerTrendsAnalysis extends Command
{
    protected $signature = 'batch:player-trends {week} {--season=}';
    protected $description = 'Submit player trends analysis for NFL games to OpenAI';

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

            // Fetch games with player trends
            $this->info("Fetching NFL games for Week {$week}, Season {$season}...");
            $games = OddsApiNfl::whereHas('playerTrends', function ($query) use ($season, $week) {
                $query->season($season)->week($week);
            })->get();

            if ($games->isEmpty()) {
                $this->error("No games with player trends found for Week {$week}, Season {$season}.");
                return 1;
            }

            // Prepare requests
            $requests = $this->prepareAnalysisRequests($games, $week, $season);

            if (empty($requests)) {
                $this->error('No valid analysis requests could be prepared.');
                return 1;
            }

            // Submit batch
            $batchId = $this->submitBatch($requests);
            $this->info("Batch submitted with ID: {$batchId}");

            // Save batch information
            Storage::put(
                "batch_info_{$week}_{$season}_player_trends.json",
                json_encode([
                    'batch_id' => $batchId,
                    'week' => $week,
                    'season' => $season,
                    'created_at' => now()->toDateTimeString(),
                ])
            );

            $this->info('Player trends analysis batch submitted successfully.');
            return 0;
        } catch (Exception $e) {
            $this->error("Error submitting batch: {$e->getMessage()}");
            Log::error('Submit Batch Error', ['exception' => $e]);
            return 1;
        }
    }

    private function prepareAnalysisRequests($games, int $week, int $season): array
    {
        $requests = [];
        foreach ($games as $index => $game) {
            $playerTrends = PlayerTrend::where('odds_api_id', $game->event_id)
                ->where('season', $season)
                ->where('week', $week)
                ->get();

            if ($playerTrends->isEmpty()) {
                $this->warn("No player trends data found for {$game->home_team} vs {$game->away_team}.");
                continue;
            }

            $playersToAvoid = $playerTrends->filter(fn($trend) => $trend->over_count / ($trend->over_count + $trend->under_count) < 0.5)->take(2);
            $playerToConsider = $playerTrends->filter(fn($trend) => $trend->over_count / ($trend->over_count + $trend->under_count) >= 0.5 && $trend->over_count / ($trend->over_count + $trend->under_count) < 0.7)->first();
            $playerToBetOn = $playerTrends->filter(fn($trend) => $trend->over_count / ($trend->over_count + $trend->under_count) >= 0.7)->first();

            $content = $this->generatePrompt($game->home_team, $playersToAvoid, $playerToConsider, $playerToBetOn, $week);

            $requests[] = [
                'custom_id' => "{$game->event_id}-{$index}",
                'method' => 'POST',
                'url' => '/v1/chat/completions',
                'body' => [
                    'model' => 'gpt-3.5-turbo-0125',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a sports journalist providing betting insights.'],
                        ['role' => 'user', 'content' => $content],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 1500,
                    'presence_penalty' => 0.3,
                    'frequency_penalty' => 0.3,
                ],
            ];
        }

        return $requests;
    }

    private function generatePrompt(string $team, $playersToAvoid, $playerToConsider, $playerToBetOn, int $week): string
    {
        $avoidList = $playersToAvoid->map(fn($player) => "- **{$player->player}** ({$player->market}): {$player->point} points predicted.\n")->implode("\n");
        $consider = $playerToConsider
            ? "**{$playerToConsider->player}** ({$playerToConsider->market}): {$playerToConsider->point} points predicted.\n"
            : 'No players to consider this week.';
        $betOn = $playerToBetOn
            ? "**{$playerToBetOn->player}** ({$playerToBetOn->market}): {$playerToBetOn->point} points predicted.\n"
            : 'No players to bet on this week.';

        return <<<PROMPT
### NFL Week {$week} Player Trends: {$team}

#### Players to Avoid:
{$avoidList}

#### Player to Consider:
{$consider}

#### Player to Bet On:
{$betOn}

Use these insights to make smarter betting decisions this week!
PROMPT;
    }

    private function submitBatch(array $requests): string
    {
        $filePath = storage_path('app/batch_input.jsonl');
        file_put_contents($filePath, implode("\n", array_map('json_encode', $requests)));

        // Step 1: Upload the file
        $response = Http::withToken($this->apiKey)
            ->attach('file', fopen($filePath, 'r'), 'batch_input.jsonl')
            ->post("{$this->baseUrl}/files", [
                'purpose' => 'batch'
            ]);

        if (!$response->successful()) {
            throw new Exception('File upload failed: ' . $response->body());
        }

        $fileId = $response->json('id');

        // Step 2: Create the batch
        $batchResponse = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/batches", [
                'input_file_id' => $fileId,
                'endpoint' => '/v1/chat/completions',
                'completion_window' => '24h'
            ]);

        if (!$batchResponse->successful()) {
            throw new Exception('Batch creation failed: ' . $batchResponse->body());
        }

        return $batchResponse->json('id');
    }

}
