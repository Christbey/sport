<?php

namespace App\Console\Commands;

use App\Models\Nfl\NflTeamSchedule;
use App\Models\Post;
use App\Services\OpenAIBatchService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RetrieveBatchAnalysis extends Command
{
    protected $signature = 'batch:retrieve {week} {--season=}';
    protected $description = 'Retrieve and process batch analysis results';

    private OpenAIBatchService $openAIBatchService;

    public function __construct(OpenAIBatchService $openAIBatchService)
    {
        parent::__construct();
        $this->openAIBatchService = $openAIBatchService;
    }

    public function handle(): int
    {
        try {
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

            // Get batch results using service
            $batchResults = $this->openAIBatchService->retrieveBatchResults($batchId);
            $this->info("Batch status: {$batchResults['status']}");

            // If not completed, show progress and exit
            if ($batchResults['status'] !== 'completed') {
                $counts = $batchResults['request_counts'];
                $this->info('Batch in progress:');
                $this->table(
                    ['Total', 'Succeeded', 'Failed', 'Pending'],
                    [[
                        $counts['total'] ?? 0,
                        $counts['succeeded'] ?? 0,
                        $counts['failed'] ?? 0,
                        ($counts['total'] ?? 0) - ($counts['succeeded'] ?? 0) - ($counts['failed'] ?? 0)
                    ]]
                );
                return 0;
            }

            // Get games for result processing
            $games = NflTeamSchedule::where('game_week', $week)
                ->where('season', $season)
                ->get()
                ->keyBy('game_id');

            // Process results
            if (!empty($batchResults['results'])) {
                $this->processResults($batchResults['results'], $games, $week, $season);
            }

            $this->newLine();
            $this->info('Processing completed.');
            $counts = $batchResults['request_counts'];
            $this->table(
                ['Total', 'Succeeded', 'Failed'],
                [[
                    $counts['total'] ?? 0,
                    $counts['succeeded'] ?? 0,
                    $counts['failed'] ?? 0
                ]]
            );

            return 0;

        } catch (Exception $e) {
            $this->error("Error retrieving batch results: {$e->getMessage()}");
            Log::error('Batch Retrieval Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    private function processResults(array $results, $games, int $week, int $season): void
    {
        $this->info('Processing results...');
        $bar = $this->output->createProgressBar(count($results));
        $bar->start();

        foreach ($results as $gameId => $result) {
            try {
                if (!isset($games[$gameId])) {
                    $this->error("Game not found for ID: {$gameId}");
                    continue;
                }

                $game = $games[$gameId];

                if (isset($result['error'])) {
                    $this->error("Error for game {$gameId}: " . json_encode($result['error']));
                    continue;
                }

                $analysis = $result['response']['body']['choices'][0]['message']['content']
                    ?? 'Unable to generate analysis.';

                // Extract and process prediction data
                $predictionData = $this->extractPredictionData($analysis);

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
                        'prediction' => json_encode($predictionData),
                        'predicted_winner' => $predictionData['winner'],
                        'predicted_score' => $predictionData['final_score'],
                        'predicted_spread' => $predictionData['spread'],
                        'predicted_over_under' => $predictionData['over_under'],
                        'prediction_confidence' => $predictionData['confidence'],
                        'published' => true,
                        'user_id' => 1 // Admin user
                    ]
                );

                Log::info('Processed game', [
                    'game_id' => $gameId,
                    'prediction' => $predictionData
                ]);

                $bar->advance();

            } catch (Exception $e) {
                $this->error("Error processing game {$gameId}: {$e->getMessage()}");
                Log::error('Result Processing Error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'game_id' => $gameId,
                    'data' => $result ?? null
                ]);
            }
        }

        $bar->finish();
    }

    private function extractPredictionData($analysis): array
    {
        $predictionData = [
            'winner' => null,
            'final_score' => null,
            'spread' => null,
            'over_under' => null,
            'confidence' => null
        ];

        // Extract the entire prediction section
        if (preg_match('/#### Game Prediction\s*\n(.*?)(?=\s*Supporting Analysis:)/s', $analysis, $matches)) {
            $predictionSection = $matches[1];

            // Extract Winner
            if (preg_match('/Winner:\s*(\w+)/i', $predictionSection, $winnerMatch)) {
                $predictionData['winner'] = trim($winnerMatch[1]);
            }

            // Extract Final Score
            if (preg_match('/Final Score:\s*([^\\n]+)/i', $predictionSection, $scoreMatch)) {
                $predictionData['final_score'] = trim($scoreMatch[1]);
            }

            // Extract Spread
            if (preg_match('/Spread:\s*([^\\n]+)/i', $predictionSection, $spreadMatch)) {
                $predictionData['spread'] = trim($spreadMatch[1]);
            }

            // Extract Over/Under
            if (preg_match('/Over\/Under:\s*([^\\n]+)/i', $predictionSection, $ouMatch)) {
                $predictionData['over_under'] = trim($ouMatch[1]);
            }

            // Extract Confidence
            if (preg_match('/Confidence:\s*([^\\n]+)/i', $predictionSection, $confMatch)) {
                $predictionData['confidence'] = trim($confMatch[1]);
            }
        }

        return $predictionData;
    }
}