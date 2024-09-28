<?php

namespace App\Services;

use App\Models\nfl\NflTeamSchedule;
use Illuminate\Support\Facades\Log;

class EloRatingService
{
    protected $kFactor;
    protected $startingRating;
    protected $homeFieldAdvantage = 1.3; // Added home-field advantage
    protected $teamRatings = []; // Store teams' dynamic Elo ratings during the season

    public function __construct()
    {
        $this->kFactor = config('elo.k_factor', 32);
        $this->startingRating = config('elo.starting_rating', 1500);
    }

    /**
     * Calculate Elo for a given team over a season.
     *
     * @param string $teamAbv Team abbreviation (e.g., "NE" for New England Patriots)
     * @param int $seasonYear The year of the season
     * @return int Final Elo rating for the season
     */
    public function calculateTeamEloForSeason($teamAbv, $seasonYear)
    {
        $games = $this->fetchGamesForTeam($teamAbv, $seasonYear);

        if ($games->isEmpty()) {
            Log::warning("No games found for team: $teamAbv in season $seasonYear.");
            return $this->startingRating; // Return starting rating if no games
        }

        $currentElo = $this->getInitialTeamRating($teamAbv);

        foreach ($games as $game) {
            if (in_array($game->game_status, ['Completed', 'Final'])) {
                $this->adjustKFactorForGame($game);
                $opponentTeamAbv = $this->getOpponentTeamAbv($teamAbv, $game);
                $opponentElo = $this->getInitialTeamRating($opponentTeamAbv);
                $actualResult = $this->getGameResult($game, $teamAbv);

                // Incorporate home-field advantage if team is playing at home
                if ($game->home_team === $teamAbv) {
                    $currentElo += $this->homeFieldAdvantage;
                }

                $marginOfVictory = abs($game->home_pts - $game->away_pts);
                $team1SOS = $this->calculateStrengthOfSchedule($teamAbv, $seasonYear);
                $team2SOS = $this->calculateStrengthOfSchedule($opponentTeamAbv, $seasonYear);
                $newRatings = $this->calculateEloForGameWithSOS($teamAbv, $opponentTeamAbv, $currentElo, $opponentElo, $actualResult, $marginOfVictory, $team1SOS, $team2SOS);
                $currentElo = $newRatings['team1_new_rating'];
                $this->teamRatings[$opponentTeamAbv] = $newRatings['team2_new_rating'];
            } else {
                $this->logPredictedFutureElo($teamAbv, $game, $currentElo);
            }

            $this->logEloChanges($teamAbv, $game->game_week, $currentElo);
            $this->teamRatings[$teamAbv] = $currentElo;
        }

        Log::info("Final Elo for team $teamAbv for season $seasonYear: $currentElo");

        return round($currentElo);
    }

    /**
     * Print expected wins and predicted spreads for a team for a season based on Elo ratings.
     *
     * @param string $teamAbv Team abbreviation
     * @param int $seasonYear The year of the season
     */
    public function printExpectedWinsAndSpreadForTeam($teamAbv, $seasonYear)
    {
        echo "Calculating expected wins and predicted spreads for team: $teamAbv in season: $seasonYear" . PHP_EOL;

        // Fetch all games that are scheduled for the team in the season
        $games = $this->fetchGamesForTeam($teamAbv, $seasonYear);

        if ($games->isEmpty()) {
            echo "No scheduled games found for team: $teamAbv in season $seasonYear." . PHP_EOL;
            return;
        }

        echo 'Found ' . count($games) . " games for team: $teamAbv in season $seasonYear" . PHP_EOL;

        $totalExpectedWins = 0;
        $currentElo = $this->getInitialTeamRating($teamAbv);

        foreach ($games as $game) {
            $opponentTeamAbv = $this->getOpponentTeamAbv($teamAbv, $game);
            $opponentElo = $this->getInitialTeamRating($opponentTeamAbv);

            // Incorporate home-field advantage if team is playing at home
            if ($game->home_team === $teamAbv) {
                $currentElo += $this->homeFieldAdvantage;
            }

            // Calculate the expected outcome (win probability) based on Elo
            $winProbability = $this->calculateExpectedOutcome($currentElo, $opponentElo);

            // Predict the spread for the game
            $predictedSpread = $this->predictSpread($currentElo, $opponentElo);

            echo "Expected win probability: $winProbability for team: $teamAbv vs $opponentTeamAbv" . PHP_EOL;
            echo "Predicted spread: $predictedSpread points for team: $teamAbv vs $opponentTeamAbv" . PHP_EOL;

            $totalExpectedWins += $winProbability;
        }

        // Print the total expected wins for the season
        echo "Expected total wins for team $teamAbv in season $seasonYear: $totalExpectedWins" . PHP_EOL;
    }

    /**
     * Fetch games for a team in a season.
     *
     * @param string $teamAbv
     * @param int $seasonYear
     * @return \Illuminate\Database\Eloquent\Collection|NflTeamSchedule[]
     */
    private function fetchGamesForTeam($teamAbv, $seasonYear)
    {
        return NflTeamSchedule::where(function ($query) use ($teamAbv) {
            $query->where('home_team', $teamAbv)
                ->orWhere('away_team', $teamAbv);
        })
            ->whereYear('game_date', $seasonYear)
            ->where('game_week', 'not like', '%preseason%')  // Exclude preseason games
            ->where('game_week', 'not like', '%Hall of Fame%') // Exclude Hall of Fame game
            ->orderBy('game_date', 'asc')
            ->get();
    }

    /**
     * Adjust K-factor dynamically for important games like playoffs.
     *
     * @param NflTeamSchedule $game The game record
     */
    private function adjustKFactorForGame(NflTeamSchedule $game)
    {
        $this->kFactor = strpos($game->game_week, 'Playoffs') !== false ? 40 : config('elo.k_factor', 32);
    }

    /**
     * Get the opponent team abbreviation.
     *
     * @param string $teamAbv
     * @param NflTeamSchedule $game
     * @return string
     */
    private function getOpponentTeamAbv($teamAbv, $game)
    {
        return $game->home_team === $teamAbv ? $game->away_team : $game->home_team;
    }

    /**
     * Calculate Elo for a game with MOV and SOS adjustments.
     *
     * @param string $team1Abv
     * @param string $team2Abv
     * @param float $team1Elo
     * @param float $team2Elo
     * @param float $result
     * @param int $marginOfVictory
     * @param float $team1SOS
     * @param float $team2SOS
     * @return array New Elo ratings for both teams
     */
    private function calculateEloForGameWithSOS($team1Abv, $team2Abv, $team1Elo, $team2Elo, $result, $marginOfVictory, $team1SOS, $team2SOS)
    {
        $expectedTeam1 = 1 / (1 + pow(10, ($team2Elo - $team1Elo) / 400));

        // Incorporating MOV (Margin of Victory) scaling factor
        $mov_multiplier = log(abs($marginOfVictory) + 1) * (2.2 / (($team1Elo - $team2Elo) * 0.001 + 2.2));

        // Adjust Elo change based on Strength of Schedule (SOS)
        $sos_adjustment_team1 = $team1SOS / 1500; // Scale the SOS adjustment, assuming 1500 is the base Elo
        $sos_adjustment_team2 = $team2SOS / 1500;

        // Adjust Elo based on MOV, SOS, and the result
        $newTeam1Rating = $team1Elo + $this->kFactor * ($result - $expectedTeam1) * $mov_multiplier * $sos_adjustment_team1;
        $newTeam2Rating = $team2Elo + $this->kFactor * ((1 - $result) - (1 - $expectedTeam1)) * $mov_multiplier * $sos_adjustment_team2;

        return [
            'team1_new_rating' => $newTeam1Rating,
            'team2_new_rating' => $newTeam2Rating,
        ];
    }

    /**
     * Get the game result for a team based on nfl_team_schedules data, handling ties.
     *
     * @param NflTeamSchedule $game The game record
     * @param string $teamAbv The team abbreviation
     * @return float Game result (1 for win, 0 for loss, 0.5 for tie)
     */
    private function getGameResult(NflTeamSchedule $game, $teamAbv)
    {
        if ($game->home_pts === $game->away_pts) {
            return 0.5; // Tie game
        }

        return $game->home_team === $teamAbv
            ? ($game->home_pts > $game->away_pts ? 1 : 0)
            : ($game->away_pts > $game->home_pts ? 1 : 0);
    }

    /**
     * Log Elo changes per game week.
     *
     * @param string $teamAbv Team abbreviation
     * @param string $gameWeek Game week
     * @param float $currentElo The current Elo rating after the game
     * @return void
     */
    private function logEloChanges($teamAbv, $gameWeek, $currentElo)
    {
        Log::info("Team: $teamAbv | Week: $gameWeek | Elo: $currentElo");
    }

    /**
     * Get the initial Elo rating for a team, or use the team's current Elo if already set.
     *
     * @param string $teamAbv The team abbreviation
     * @return float The Elo rating for the team
     */
    private function getInitialTeamRating($teamAbv)
    {
        return $this->teamRatings[$teamAbv] ?? $this->startingRating;
    }

    /**
     * Calculate the Strength of Schedule (SOS) based on the average Elo of opponents.
     *
     * @param string $teamAbv
     * @param int $seasonYear
     * @return float The strength of schedule (SOS) for the team
     */
    private function calculateStrengthOfSchedule($teamAbv, $seasonYear)
    {
        $opponents = NflTeamSchedule::where(function ($query) use ($teamAbv) {
            $query->where('home_team', '!=', $teamAbv)
                ->orWhere('away_team', '!=', $teamAbv);
        })
            ->whereYear('game_date', $seasonYear)
            ->pluck('home_team', 'away_team'); // Get all opponents' abbreviations

        $totalElo = 0;
        $numGames = count($opponents);

        foreach ($opponents as $homeTeam => $awayTeam) {
            $opponentAbv = ($homeTeam === $teamAbv) ? $awayTeam : $homeTeam;
            $totalElo += $this->getInitialTeamRating($opponentAbv); // Get opponent's Elo
        }

        return ($numGames > 0) ? $totalElo / $numGames : $this->startingRating; // Default to base Elo if no games
    }

    /**
     * Log the predicted result for a future game based on Elo.
     *
     * @param string $teamAbv Team abbreviation
     * @param NflTeamSchedule $game The game record
     * @param float $currentElo The current Elo rating of the team
     */
    private function logPredictedFutureElo($teamAbv, $game, $currentElo)
    {
        $opponentTeamAbv = $this->getOpponentTeamAbv($teamAbv, $game);
        $opponentElo = $this->getInitialTeamRating($opponentTeamAbv);

        $expectedOutcome = $this->calculateExpectedOutcome($currentElo, $opponentElo);

        Log::info("Predicted result for future game: $teamAbv vs $opponentTeamAbv in Week {$game->game_week}");
        Log::info("Team $teamAbv Elo: $currentElo | Opponent $opponentTeamAbv Elo: $opponentElo | Expected Outcome: $expectedOutcome");
    }

    /**
     * Calculate the expected outcome based on Elo ratings.
     *
     * @param float $teamElo Elo rating for the team
     * @param float $opponentElo Elo rating for the opponent
     * @return float Expected outcome as a probability between 0 and 1
     */
    public function calculateExpectedOutcome($teamElo, $opponentElo)
    {
        return 1 / (1 + pow(10, ($opponentElo - $teamElo) / 400));
    }

    /**
     * Predict the spread between two teams based on their Elo ratings.
     *
     * @param float $teamElo Elo rating for the team
     * @param float $opponentElo Elo rating for the opponent
     * @return float The predicted point spread
     */
    public function predictSpread($teamElo, $opponentElo)
    {
        // Simple formula for predicting spread based on Elo difference
        $eloDifference = $teamElo - $opponentElo;

        // Convert Elo difference into point spread (this scale can be adjusted)
        $predictedSpread = $eloDifference / 25;

        return round($predictedSpread, 1); // Return the spread rounded to 1 decimal point
    }

}
