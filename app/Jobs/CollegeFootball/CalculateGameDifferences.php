<?php

namespace App\Jobs\CollegeFootball;

use App\Events\{GameResultProcessed, WeeklyCalculationsCompleted};
use App\Models\CollegeFootball\CollegeFootballGame;
use App\Notifications\DiscordCommandCompletionNotification;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\{Log, Notification};

class CalculateGameDifferences implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $week;
    protected $seasonType;

    /**
     * Create a new job instance.
     *
     * @param int $week
     * @param string $seasonType
     */
    public function __construct(int $week, string $seasonType = 'regular')
    {
        $this->week = $week;
        $this->seasonType = $seasonType;
    }

    public function handle()
    {
        try {
            $weeks = config('college_football.weeks');
            if ($this->seasonType === 'regular' && !isset($weeks[$this->week])) {
                throw new Exception("Invalid week: {$this->week}");
            }

            $query = CollegeFootballGame::with(['hypothetical', 'homeTeam', 'awayTeam'])
                ->whereHas('hypothetical')
                ->where('completed', '1')
                ->where('season_type', $this->seasonType);

            // Apply date range filter only for regular season
            if ($this->seasonType === 'regular') {
                $dateRange = $weeks[$this->week];
                $query->whereBetween('start_date', [$dateRange['start'], $dateRange['end']]);
                Log::info('Starting game calculations', ['week' => $this->week, 'season_type' => $this->seasonType, 'date_range' => $dateRange]);
            } else {
                Log::info('Starting postseason game calculations', ['week' => $this->week, 'season_type' => $this->seasonType]);
            }

            $games = $query->get();

            $totalProcessedGames = 0;
            $correctPredictions = 0;
            $gamesData = collect();

            foreach ($games as $game) {
                // Skip if required fields are missing
                if (is_null($game->away_points) || is_null($game->home_points)) {
                    Log::info('Skipped game due to missing points', ['game_id' => $game->id]);
                    continue;
                }

                // Calculate the actual point difference and compare with the spread
                $actualPointDifference = $game->home_points - $game->away_points;
                $hypotheticalSpread = $game->hypothetical->hypothetical_spread;
                $wasCorrect = ($actualPointDifference >= $hypotheticalSpread);

                // Grade the record by updating the game
                $game->update([
                    'result' => $wasCorrect ? 1 : 0, // 1 for correct, 0 for incorrect
                ]);

                // Dispatch event for each processed game
                GameResultProcessed::dispatch(
                    $game,
                    $wasCorrect,
                    $hypotheticalSpread,
                    $actualPointDifference
                );

                // Increment counters
                $totalProcessedGames++;
                if ($wasCorrect) {
                    $correctPredictions++;
                }

                // Collect game data for reporting
                $gamesData->push([
                    'id' => $game->id,
                    'homeTeam' => $game->homeTeam->school ?? 'Home Team',
                    'awayTeam' => $game->awayTeam->school ?? 'Away Team',
                    'homePoints' => $game->home_points,
                    'awayPoints' => $game->away_points,
                    'spreadOpen' => $game->spread_open,
                    'result' => $wasCorrect ? 1 : 0,
                ]);
            }

            // Log and notify results
            if ($gamesData->isNotEmpty()) {
                $percentage = number_format(($correctPredictions / $totalProcessedGames) * 100, 2);

                WeeklyCalculationsCompleted::dispatch(
                    $gamesData->toArray(),
                    $totalProcessedGames,
                    $correctPredictions,
                    $percentage
                );
            }

            $seasonTypeText = ucfirst($this->seasonType);
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification(
                    "Processed {$seasonTypeText} games for week {$this->week}. Total: {$totalProcessedGames}, Correct: {$correctPredictions}",
                    'success'
                ));

        } catch (Exception $e) {
            Log::error('Error processing games: ' . $e->getMessage());
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($e->getMessage(), 'error'));
        }
    }
}