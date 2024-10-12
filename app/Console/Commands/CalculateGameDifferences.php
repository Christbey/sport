<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CollegeFootball\CollegeFootballGame;

class CalculateGameDifferences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calculate:game-differences';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculates game differences and updates the hypothetical spreads';

    /**
     * Execute the console command.
     */
    public function handle()
    {
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

            // Calculate the point difference (home_points - away_points)
            $pointDifference = $game->home_points - $game->away_points;

            // Get the hypothetical spread value
            $hypotheticalSpread = $hypothetical->hypothetical_spread;

            // Compare and determine the result
            $result = ($pointDifference - $hypotheticalSpread) > 0 ? 1 : 0;

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
            $homeTeamName = $game->home_team ?? 'Home Team';
            $awayTeamName = $game->away_team ?? 'Away Team';

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

            // Determine if hypothetical spread prediction was correct
            $adjustedDifference = $pointDifference - $hypotheticalSpread;
            if ($adjustedDifference > 0) {
                $hypotheticalCorrect = 1;
                $outcomeInterpretation = "{$homeTeamName} **outperformed** the projection.";
            } else {
                $hypotheticalCorrect = 0;
                $outcomeInterpretation = "{$homeTeamName} **underperformed** the projection.";
            }

            // Output the information
            $this->info("Game ID {$game->id}: {$awayTeamName} at {$homeTeamName}");
            $this->info("- Hypothetical Spread: {$hypotheticalSpread}");
            $this->info("- Spread Open: {$game->spread_open}");
            $this->info("- Difference Between Spreads: {$spreadDifference}");
            $this->info("- Actual Point Difference: {$pointDifference}");
            $this->info("- Projection: {$interpretation}");
            $this->info("- Outcome: {$outcomeInterpretation}");
            $this->info("- Final Score: {$awayTeamName} {$game->away_points} - {$homeTeamName} {$game->home_points}");
            $this->info("- Result: {$result}");
            $this->info('--------------------------------------');
        }

        // Calculate the total correct percentage
        if ($totalProcessedGames > 0) {
            $correctPercentage = ($correctPredictions / $totalProcessedGames) * 100;
            $this->info("Total Processed Games: {$totalProcessedGames}");
            $this->info("Correct Predictions: {$correctPredictions}");
            $this->info('Total Correct Percentage: ' . number_format($correctPercentage, 2) . '%');
        } else {
            $this->info('No games were processed.');
        }

        $this->info('Calculations completed successfully.');
    }
}
