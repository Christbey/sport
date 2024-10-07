<?php

namespace App\Services;

use App\Models\Nfl\NflEloRating;
use App\Models\Nfl\NflTeamSchedule;
use App\Models\NflEloPrediction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class EloRatingService
{
    protected $kFactor;
    protected $startingRating;
    protected $homeFieldAdvantage = 1.2; // Points for home team advantage
    protected $teamRatings = [];

    public function __construct()
    {
        $this->kFactor = config('elo.k_factor', 40);
        $this->startingRating = config('elo.starting_rating', 1500);
    }

    public function fetchTeams()
    {
        return NflTeamSchedule::select('home_team')
            ->union(NflTeamSchedule::select('away_team'))
            ->distinct()
            ->pluck('home_team');
    }

    public function processTeamPredictions($team, $year, $weeks, $today)
    {
        // Calculate Elo and predictions for the team
        $predictions = $this->calculateTeamEloForSeason($team, $year);

        foreach ($predictions['predictions'] as $prediction) {
            $week = $prediction['week'];

            // Check if the week exists in the configuration
            if (!isset($weeks[$week])) {
                $this->logMissingWeek($week, $year);
                continue;
            }

            // Get the week end date from configuration
            $weekEnd = Carbon::parse($weeks[$week]['end']);

            // Store prediction if applicable
            $this->storePredictionIfNeeded($team, $prediction, $year, $weekEnd, $today);
        }

        // Return the final Elo for the team
        return $predictions['final_elo'];
    }

    public function calculateTeamEloForSeason($teamAbv, $seasonYear)
    {
        // Fetch regular season games for the team
        $games = $this->fetchGamesForTeam($teamAbv, $seasonYear);
        if ($games->isEmpty()) {
            Log::warning("No games found for team: $teamAbv in season $seasonYear.");
            return ['final_elo' => $this->startingRating, 'predictions' => []];
        }

        $currentElo = $this->getInitialTeamRating($teamAbv);
        $predictions = [];

        foreach ($games as $game) {
            $opponentTeamAbv = $this->getOpponentTeamAbv($teamAbv, $game);
            $opponentElo = $this->getInitialTeamRating($opponentTeamAbv);

            // Adjust Elo for home-field advantage
            if ($game->home_team === $teamAbv) {
                $currentElo += $this->homeFieldAdvantage;
            }

            // Calculate and store prediction
            $prediction = $this->makePrediction($teamAbv, $opponentTeamAbv, $game, $currentElo, $opponentElo);
            $predictions[] = $prediction;

            // If the game is completed, update Elo ratings based on the result
            if ($game->game_status === 'Completed') {
                $result = $this->getGameResult($game, $teamAbv);
                $eloUpdate = $this->calculateEloForGame($currentElo, $opponentElo, $result, $game);
                $currentElo = $eloUpdate['team1_new_rating'];
                $this->teamRatings[$opponentTeamAbv] = $eloUpdate['team2_new_rating'];
            }

            // Store the updated Elo for the team
            $this->teamRatings[$teamAbv] = $currentElo;
        }

        // Store final Elo for the season
        $this->storeFinalElo($teamAbv, $seasonYear, $currentElo);

        return ['final_elo' => round($currentElo), 'predictions' => $predictions];
    }

    private function fetchGamesForTeam($teamAbv, $seasonYear)
    {
        // Fetch regular season games for the team
        return NflTeamSchedule::where(function ($query) use ($teamAbv) {
            $query->where('home_team', $teamAbv)->orWhere('away_team', $teamAbv);
        })
            ->whereYear('game_date', $seasonYear)
            ->where('season_type', 'Regular Season') // Filter to only regular season games
            ->orderBy('game_date', 'asc')
            ->get();
    }

    private function getInitialTeamRating($teamAbv)
    {
        return $this->teamRatings[$teamAbv] ?? $this->startingRating;
    }

    private function getOpponentTeamAbv($teamAbv, $game)
    {
        return $game->home_team === $teamAbv ? $game->away_team : $game->home_team;
    }

    private function makePrediction($teamAbv, $opponentTeamAbv, $game, $currentElo, $opponentElo)
    {
        $expectedOutcome = $this->calculateExpectedOutcome($currentElo, $opponentElo);
        $predictedSpread = $this->predictSpread($currentElo, $opponentElo);

        Log::info("Predicted result: $teamAbv vs $opponentTeamAbv, Spread: $predictedSpread");

        return [
            'week' => $game->game_week,
            'team' => $teamAbv,
            'opponent' => $opponentTeamAbv,
            'team_elo' => $currentElo,
            'opponent_elo' => $opponentElo,
            'expected_outcome' => $expectedOutcome,
            'predicted_spread' => $predictedSpread,
        ];
    }

    private function calculateExpectedOutcome($teamElo, $opponentElo)
    {
        return 1 / (1 + pow(10, ($opponentElo - $teamElo) / 400));
    }

    private function predictSpread($teamElo, $opponentElo)
    {
        return round(($teamElo - $opponentElo) / 40, 1);
    }

    private function getGameResult($game, $teamAbv)
    {
        if ($game->home_pts === $game->away_pts) return 0.5;
        return $game->home_team === $teamAbv ? ($game->home_pts > $game->away_pts ? 1 : 0) : ($game->away_pts > $game->home_pts ? 1 : 0);
    }

    // Refactored methods

    private function calculateEloForGame($team1Elo, $team2Elo, $result, $game)
    {
        $movMultiplier = log(abs($game->home_pts - $game->away_pts) + 1) * (2.2 / (($team1Elo - $team2Elo) * 0.0047 + 2.2));
        $expectedOutcome = $this->calculateExpectedOutcome($team1Elo, $team2Elo);

        $newTeam1Rating = $team1Elo + $this->kFactor * ($result - $expectedOutcome) * $movMultiplier;
        $newTeam2Rating = $team2Elo + $this->kFactor * ((1 - $result) - (1 - $expectedOutcome)) * $movMultiplier;

        return ['team1_new_rating' => $newTeam1Rating, 'team2_new_rating' => $newTeam2Rating];
    }

    private function storeFinalElo($teamAbv, $seasonYear, $currentElo)
    {
        NflEloRating::updateOrCreate(
            ['team' => $teamAbv, 'year' => $seasonYear],
            ['final_elo' => round($currentElo)]
        );
    }

    public function logMissingWeek($week, $year)
    {
        Log::warning("Week configuration not found for week: $week in year $year");
    }

    public function storePredictionIfNeeded($team, $prediction, $year, $weekEnd, $today)
    {
        // Find the game in the nfl_team_schedules table
        $game = $this->findGameForPrediction($team, $prediction['opponent'], $year);

        if (!$game) {
            Log::warning("Regular season game not found for team: {$prediction['team']} vs opponent: {$prediction['opponent']} in year $year");
            return;
        }

        // Check if a prediction already exists
        $existingPrediction = NflEloPrediction::where('team', $prediction['team'])
            ->where('opponent', $prediction['opponent'])
            ->where('year', $year)
            ->where('week', $prediction['week'])
            ->first();

        // Skip updating if the week has passed and the prediction was updated recently
        if ($today->isAfter($weekEnd) && $existingPrediction && $existingPrediction->updated_at->greaterThan($today->subDays(3))) {
            return;
        }

        // Store or update the prediction
        NflEloPrediction::updateOrCreate(
            [
                'team' => $prediction['team'],
                'opponent' => $prediction['opponent'],
                'year' => $year,
                'week' => $prediction['week'],
            ],
            [
                'team_elo' => $prediction['team_elo'],
                'opponent_elo' => $prediction['opponent_elo'],
                'expected_outcome' => $prediction['expected_outcome'],
                'predicted_spread' => $prediction['predicted_spread'],
                'game_id' => $game->game_id, // Store the matched game_id
            ]
        );
    }

    public function findGameForPrediction($teamAbv, $opponentTeamAbv, $year)
    {
        return NflTeamSchedule::where('home_team', $teamAbv)
            ->where('away_team', $opponentTeamAbv)
            ->whereYear('game_date', $year)
            ->where('season_type', 'Regular Season') // Ensure only regular
            // Ensure only regular season games
            ->first();
    }
}
