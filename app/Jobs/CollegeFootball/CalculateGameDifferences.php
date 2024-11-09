<?php

namespace App\Jobs\CollegeFootball;

use App\Models\CollegeFootball\CollegeFootballGame;
use App\Notifications\DiscordCommandCompletionNotification;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CalculateGameDifferences implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $totalProcessedGames = 0;
            $correctPredictions = 0;

            // Fetch all games with hypothetical data
            $games = CollegeFootballGame::with(['hypothetical', 'homeTeam', 'awayTeam'])
                ->whereHas('hypothetical')
                ->get();

            foreach ($games as $game) {
                $hypothetical = $game->hypothetical;

                // Ensure points are available
                if (is_null($game->away_points) || is_null($game->home_points)) {
                    continue;
                }

                // Calculate the actual point difference (home_points - away_points)
                $actualPointDifference = $game->home_points - $game->away_points;

                // Get the hypothetical spread value
                $hypotheticalSpread = $hypothetical->hypothetical_spread;

                // Determine if the prediction was correct
                $result = 0;
                if ($hypotheticalSpread >= 0) {
                    // If spread is positive, home team needs to win by MORE than the spread
                    $result = ($actualPointDifference >= $hypotheticalSpread) ? 1 : 0;
                } else {
                    // If spread is negative, home team needs to lose by LESS than the spread
                    $result = ($actualPointDifference >= $hypotheticalSpread) ? 1 : 0;
                }

                // Update the 'correct' field in the hypothetical table
                $hypothetical->correct = $result;
                $hypothetical->save();

                // Increment total processed games
                $totalProcessedGames++;

                // Increment correct predictions if result is 1
                if ($result == 1) {
                    $correctPredictions++;
                }

                // Get team names
                $homeTeamName = $game->homeTeam->name ?? 'Home Team';
                $awayTeamName = $game->awayTeam->name ?? 'Away Team';

                // Adjust spread_open to match the convention of hypotheticalSpread
                $adjustedSpreadOpen = -1 * $game->spread_open;

                // Compute the difference between our hypothetical spread and the adjusted spread_open
                $spreadDifference = $hypotheticalSpread - $adjustedSpreadOpen;

                // Interpretation based on home team
                if ($hypotheticalSpread > 0) {
                    $interpretation = "{$homeTeamName} was projected to **win** by {$hypotheticalSpread} points.";
                } elseif ($hypotheticalSpread < 0) {
                    $interpretation = "{$homeTeamName} was projected to **lose** by " . abs($hypotheticalSpread) . ' points.';
                } else {
                    $interpretation = 'Game was projected to be a tie.';
                }

                // More detailed outcome interpretation
                if ($result == 1) {
                    $outcomeInterpretation = 'Prediction was **CORRECT**. ';
                    if ($hypotheticalSpread >= 0) {
                        $outcomeInterpretation .= "{$homeTeamName} won by enough to cover the spread.";
                    } else {
                        $outcomeInterpretation .= "{$homeTeamName} didn't lose by more than the spread.";
                    }
                } else {
                    $outcomeInterpretation = 'Prediction was **INCORRECT**. ';
                    if ($hypotheticalSpread >= 0) {
                        $outcomeInterpretation .= "{$homeTeamName} didn't win by enough to cover the spread.";
                    } else {
                        $outcomeInterpretation .= "{$homeTeamName} lost by more than the spread.";
                    }
                }

                // Log the information
                Log::info("Game ID {$game->id}: {$awayTeamName} at {$homeTeamName}");
                Log::info("- Hypothetical Spread: {$hypotheticalSpread}");
                Log::info("- Spread Open: {$game->spread_open}");
                Log::info("- Difference Between Spreads: {$spreadDifference}");
                Log::info("- Actual Point Difference: {$actualPointDifference}");
                Log::info("- Projection: {$interpretation}");
                Log::info("- Outcome: {$outcomeInterpretation}");
                Log::info("- Final Score: {$awayTeamName} {$game->away_points} - {$homeTeamName} {$game->home_points}");
                Log::info("- Result: {$result}");
                Log::info('--------------------------------------');
            }

            // Calculate the total correct percentage
            if ($totalProcessedGames > 0) {
                $correctPercentage = ($correctPredictions / $totalProcessedGames) * 100;
                Log::info("Total Processed Games: {$totalProcessedGames}");
                Log::info("Correct Predictions: {$correctPredictions}");
                Log::info('Total Correct Percentage: ' . number_format($correctPercentage, 2) . '%');
            } else {
                Log::info('No games were processed.');
            }

            Log::info('Calculations completed successfully.');

            // Send success notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification('', 'success'));

        } catch (Exception $e) {
            // Send failure notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($e->getMessage(), 'error'));
        }
    }
}