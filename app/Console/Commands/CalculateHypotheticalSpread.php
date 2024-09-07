<?php

namespace App\Console\Commands;

use App\Models\CollegeFootballHypothetical;
use App\Models\CollegeFootball\CollegeFootballGame;
use App\Models\CollegeFootball\CollegeFootballElo;
use App\Models\CollegeFootball\CollegeFootballFpi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CalculateHypotheticalSpread extends Command
{
    protected $signature = 'calculate:hypothetical-spreads';
    protected $description = 'Calculate hypothetical spreads for Week 1 games in CollegeFootballGame table where home_division = "fbs"';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Fetch only games from Week 1 where both home and away divisions are 'fbs'
        $games = CollegeFootballGame::where('home_division', 'fbs')
            ->where('away_division', 'fbs')
            ->where('week', 1)  // Filter for Week 1
            ->where('season', 2024)  // Adjust the season if needed
            ->get();

        foreach ($games as $game) {
            $homeTeam = $game->homeTeam;
            $awayTeam = $game->awayTeam;
            $year = $game->year;
            $week = $game->week;

            if (!$homeTeam || !$awayTeam) {
                Log::warning("Missing team data for game ID {$game->id}. Home or away team is null.");
                continue;
            }

            $homeElo = CollegeFootballElo::where('team_id', $homeTeam->id)->where('year', $game->season)->value('elo');
            $awayElo = CollegeFootballElo::where('team_id', $awayTeam->id)->where('year', $game->season)->value('elo');
            $homeFpi = CollegeFootballFpi::where('team_id', $homeTeam->id)->where('year', $game->season)->value('fpi');
            $awayFpi = CollegeFootballFpi::where('team_id', $awayTeam->id)->where('year', $game->season)->value('fpi');

            if ($homeElo === null || $awayElo === null || $homeFpi === null || $awayFpi === null) {
                Log::warning("ELO or FPI data missing for {$homeTeam->school} vs {$awayTeam->school} in $year.");
                continue;
            }

            $spread = $this->calculateHypotheticalSpread($homeFpi, $awayFpi, $homeElo, $awayElo);

            // Use updateOrCreate to store or update the result in the database
            $hypothetical = CollegeFootballHypothetical::updateOrCreate(
                [
                    'game_id' => $game->id,  // Use game_id as the unique identifier for updating or creating
                ],
                [
                    'week' => $week,  // Store the week of the game
                    'home_team_id' => $homeTeam->id,
                    'away_team_id' => $awayTeam->id,
                    'home_team_school' => $homeTeam->school,  // Storing the home team school name
                    'away_team_school' => $awayTeam->school,  // Storing the away team school name
                    'home_elo' => $homeElo,
                    'away_elo' => $awayElo,
                    'home_fpi' => $homeFpi,
                    'away_fpi' => $awayFpi,
                    'hypothetical_spread' => $spread,
                ]
            );

            // Check if the prediction is correct and update 'is_prediction_correct'
            $this->checkAndMarkPredictionResult($hypothetical);

            Log::info("Hypothetical Spread for {$awayTeam->school} @ {$homeTeam->school}: $spread");
        }
    }

    // Function to calculate spread using only ELO and FPI
    private function calculateHypotheticalSpread($homeFpi, $awayFpi, $homeElo, $awayElo): float
    {
        $fpiSpread = $homeFpi && $awayFpi ? ($homeFpi - $awayFpi) / 2 : 0;
        $eloSpread = $homeElo && $awayElo ? ($homeElo - $awayElo) / 25 : 0;

        return round(($fpiSpread + $eloSpread) / 1.4, 2); // Adjust divisor as necessary
    }

    // Function to check if the prediction was correct and mark it in the database
    private function checkAndMarkPredictionResult(CollegeFootballHypothetical $hypothetical)
    {
        // Find the corresponding game from the CollegeFootballGame model
        $game = CollegeFootballGame::find($hypothetical->game_id);

        if ($game && $game->completed === 1) {  // Use the `completed` column to check if the game is finished
            // Calculate the actual points difference
            $actualPointsDifference = $game->home_points - $game->away_points;

            // Log the actual points difference and hypothetical spread
            Log::info("Game ID: {$game->id}, Home Points: {$game->home_pts}, Away Points: {$game->away_pts}");
            Log::info("Hypothetical Spread: {$hypothetical->hypothetical_spread}, Actual Points Difference: {$actualPointsDifference}");

            // Determine if the prediction was correct
            if (($hypothetical->hypothetical_spread > 0 && $actualPointsDifference >= $hypothetical->hypothetical_spread) ||
                ($hypothetical->hypothetical_spread < 0 && $actualPointsDifference <= $hypothetical->hypothetical_spread)) {
                $hypothetical->is_prediction_correct = 'true'; // Prediction is correct
            } else {
                $hypothetical->is_prediction_correct = 'false'; // Prediction is incorrect
            }

            // Save the updated hypothetical result
            Log::info("Before saving: is_prediction_correct = {$hypothetical->is_prediction_correct}");
            $hypothetical->save();
            Log::info("After saving: is_prediction_correct = {$hypothetical->is_prediction_correct}");        } else {
            // If the game is not completed, set the value to 'pending'
            $hypothetical->is_prediction_correct = 'pending';
            $hypothetical->save();
        }
    }
}