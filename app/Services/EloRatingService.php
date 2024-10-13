<?php

namespace App\Services;

use App\Models\Nfl\EloRating;
use App\Models\Nfl\NflEloRating;
use App\Models\Nfl\NflTeamSchedule;
use App\Models\Nfl\EloPrediction;
use App\Models\NflEloPrediction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class EloRatingService
{
    protected $kFactor;
    protected $startingRating;
    protected $homeFieldAdvantage; // Points for home team advantage
    protected $restAdvantagePerDay; // Elo points per rest day
    protected $maxRestDays; // Maximum days to consider for rest advantage
    protected $seasonStartDate; // Season start date
    protected $teamRatings = [];
    protected $lastGameDates = []; // Track last game dates for teams

    public function __construct()
    {
        $this->kFactor = config('elo.k_factor', 40);
        $this->startingRating = config('elo.starting_rating', 1500);
        $this->homeFieldAdvantage = config('elo.home_field_advantage', 1.2);
        $this->restAdvantagePerDay = config('elo.rest_advantage_per_day', 0.5);
        $this->maxRestDays = config('elo.max_rest_days', 7);
        $this->seasonStartDate = Carbon::parse(config('elo.season_start_date', '2024-09-01'));
        $this->lastGameDates = []; // Initialize as empty
    }

    /**
     * Fetch all unique team abbreviations.
     *
     * @return Collection
     */
    public function fetchTeams()
    {
        return NflTeamSchedule::select('home_team')
            ->union(NflTeamSchedule::select('away_team'))
            ->distinct()
            ->pluck('home_team');
    }

    /**
     * Process predictions for a specific team and season.
     *
     * @param string $team
     * @param int $year
     * @param array $weeks
     * @param Carbon $today
     * @return float
     */
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

    /**
     * Calculate Elo ratings and predictions for a team during a season.
     *
     * @param string $teamAbv
     * @param int $seasonYear
     * @return array
     */
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

    /**
     * Fetch regular season games for a team in a given year.
     *
     * @param string $teamAbv
     * @param int $seasonYear
     * @return Collection
     */
    private function fetchGamesForTeam($teamAbv, $seasonYear)
    {
        return NflTeamSchedule::where(function ($query) use ($teamAbv) {
            $query->where('home_team', $teamAbv)
                ->orWhere('away_team', $teamAbv);
        })
            ->whereYear('game_date', $seasonYear)
            ->where('season_type', 'Regular Season') // Filter to only regular season games
            ->orderBy('game_date', 'asc')
            ->get();
    }

    /**
     * Get the initial Elo rating for a team.
     *
     * @param string $teamAbv
     * @return float
     */
    private function getInitialTeamRating($teamAbv)
    {
        return $this->teamRatings[$teamAbv] ?? $this->startingRating;
    }

    /**
     * Get the opponent team's abbreviation.
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
     * Make a prediction for a game.
     *
     * @param string $teamAbv
     * @param string $opponentTeamAbv
     * @param NflTeamSchedule $game
     * @param float $currentElo
     * @param float $opponentElo
     * @return array
     */
    private function makePrediction($teamAbv, $opponentTeamAbv, $game, $currentElo, $opponentElo)
    {
        // Get current game date
        $currentGameDate = Carbon::parse($game->game_date);

        // Calculate rest days for both teams
        $teamRestDays = $this->calculateRestDays($this->lastGameDates[$teamAbv] ?? null, $currentGameDate);
        $opponentRestDays = $this->calculateRestDays($this->lastGameDates[$opponentTeamAbv] ?? null, $currentGameDate);

        // Limit rest days to maxRestDays to avoid excessive advantage
        $teamRestDays = min($teamRestDays, $this->maxRestDays);
        $opponentRestDays = min($opponentRestDays, $this->maxRestDays);

        // Calculate rest advantage
        $teamRestAdvantage = $teamRestDays * $this->restAdvantagePerDay;
        $opponentRestAdvantage = $opponentRestDays * $this->restAdvantagePerDay;

        // Calculate expected outcomes
        $expectedOutcome = $this->calculateExpectedOutcome($currentElo, $opponentElo);

        // Calculate predicted spread with rest advantage
        $predictedSpread = $this->predictSpread(
            $currentElo,
            $opponentElo,
            $game,
            $teamAbv,
            $teamRestAdvantage - $opponentRestAdvantage // Net rest advantage
        );

        Log::info("Predicted result: $teamAbv vs $opponentTeamAbv, Spread: $predictedSpread, Team Rest: $teamRestDays days, Opponent Rest: $opponentRestDays days");

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

    /**
     * Calculate the number of rest days between two dates.
     *
     * @param Carbon|null $lastGameDate
     * @param Carbon $currentGameDate
     * @return int
     */
    private function calculateRestDays($lastGameDate, $currentGameDate)
    {
        if (!$lastGameDate) {
            // If no last game date, calculate rest days from season start date
            return $currentGameDate->diffInDays($this->seasonStartDate);
        }

        // Calculate the difference in days
        $restDays = $lastGameDate->diffInDays($currentGameDate);

        if ($restDays < 0) {
            // If rest days are negative, log a warning and set to zero
            Log::warning("Negative rest days calculated. Last game date: {$lastGameDate->toDateString()}, Current game date: {$currentGameDate->toDateString()}.");
            return 0;
        }

        return $restDays;
    }

    /**
     * Calculate the expected outcome between two teams.
     *
     * @param float $teamElo
     * @param float $opponentElo
     * @return float
     */
    private function calculateExpectedOutcome($teamElo, $opponentElo)
    {
        return 1 / (1 + pow(10, ($opponentElo - $teamElo) / 400));
    }

    /**
     * Predict the point spread between two teams.
     *
     * @param float $teamElo
     * @param float $opponentElo
     * @param NflTeamSchedule $game
     * @param string $teamAbv
     * @param float $restAdvantage
     * @return float
     */
    private function predictSpread($teamElo, $opponentElo, $game, $teamAbv, $restAdvantage)
    {
        $expectedOutcome = $this->calculateExpectedOutcome($teamElo, $opponentElo);

        // Invert the logistic function to estimate Elo difference
        $eloDifference = -400 * log10((1 / $expectedOutcome) - 1);

        // Scaling factor to convert Elo difference to point spread
        $scalingFactor = 0.03; // Adjust based on empirical data
        $predictedSpread = $eloDifference * $scalingFactor;

        // Adjust for home-field advantage
        $homeFieldAdvantagePoints = $this->homeFieldAdvantage;

        $isHomeTeam = $game->home_team === $teamAbv;

        if ($isHomeTeam) {
            $predictedSpread += $homeFieldAdvantagePoints;
        } else {
            $predictedSpread -= $homeFieldAdvantagePoints;
        }

        // Apply net rest advantage
        $predictedSpread += $restAdvantage;

        // Prevent unrealistic spreads by setting reasonable bounds
        $predictedSpread = max(min($predictedSpread, 50), -50); // Example bounds

        return round($predictedSpread, 1);
    }

    /**
     * Get the result of the game from the perspective of the team.
     *
     * @param NflTeamSchedule $game
     * @param string $teamAbv
     * @return float
     */
    private function getGameResult($game, $teamAbv)
    {
        if ($game->home_pts === $game->away_pts) return 0.5;
        return $game->home_team === $teamAbv
            ? ($game->home_pts > $game->away_pts ? 1 : 0)
            : ($game->away_pts > $game->home_pts ? 1 : 0);
    }

    /**
     * Calculate Elo ratings for both teams after a game.
     *
     * @param float $team1Elo
     * @param float $team2Elo
     * @param float $result
     * @param NflTeamSchedule $game
     * @return array
     */
    private function calculateEloForGame($team1Elo, $team2Elo, $result, $game)
    {
        $movMultiplier = log(abs($game->home_pts - $game->away_pts) + 1) * (2.2 / (($team1Elo - $team2Elo) * 0.0047 + 2.2));
        $expectedOutcome = $this->calculateExpectedOutcome($team1Elo, $team2Elo);

        $newTeam1Rating = $team1Elo + $this->kFactor * ($result - $expectedOutcome) * $movMultiplier;
        $newTeam2Rating = $team2Elo + $this->kFactor * ((1 - $result) - (1 - $expectedOutcome)) * $movMultiplier;

        return ['team1_new_rating' => $newTeam1Rating, 'team2_new_rating' => $newTeam2Rating];
    }

    /**
     * Store the final Elo rating for a team for the season.
     *
     * @param string $teamAbv
     * @param int $seasonYear
     * @param float $currentElo
     * @return void
     */
    private function storeFinalElo($teamAbv, $seasonYear, $currentElo)
    {
        NflEloRating::updateOrCreate(
            ['team' => $teamAbv, 'year' => $seasonYear],
            ['final_elo' => round($currentElo)]
        );
    }

    /**
     * Log a warning when a week configuration is missing.
     *
     * @param int $week
     * @param int $year
     * @return void
     */
    public function logMissingWeek($week, $year)
    {
        Log::warning("Week configuration not found for week: $week in year $year");
    }

    /**
     * Store or update a prediction if applicable.
     *
     * @param string $team
     * @param array $prediction
     * @param int $year
     * @param Carbon $weekEnd
     * @param Carbon $today
     * @return void
     */
    public function storePredictionIfNeeded($team, $prediction, $year, $weekEnd, $today)
    {
        // Find the game in the nfl_team_schedules table
        $game = $this->findGameForPrediction($team, $prediction['opponent'], $year);

        if (!$game) {
            Log::warning("Regular season game not found for team: {$prediction['team']} vs opponent: {$prediction['opponent']} in year $year");
            return;
        }

        // Check if a prediction already exists based on 'team', 'year', 'week'
        $existingPrediction = NflEloPrediction::where('team', $prediction['team'])
            ->where('year', $year)
            ->where('week', $prediction['week'])
            ->first();

        // Skip updating if the week has passed and the prediction was updated recently
        if ($today->isAfter($weekEnd) && $existingPrediction && $existingPrediction->updated_at->greaterThan($today->copy()->subDays(3))) {
            return;
        }

        // Store or update the prediction
        NflEloPrediction::updateOrCreate(
            [
                'team' => $prediction['team'],
                'year' => $year,
                'week' => $prediction['week'],
            ],
            [
                'opponent' => $prediction['opponent'], // Update opponent if necessary
                'team_elo' => $prediction['team_elo'],
                'opponent_elo' => $prediction['opponent_elo'],
                'expected_outcome' => $prediction['expected_outcome'],
                'predicted_spread' => $prediction['predicted_spread'],
                'game_id' => $game->game_id, // Store the matched game_id
            ]
        );

        // Update last game dates for both teams
        $gameDate = Carbon::parse($game->game_date);

        // Ensure that the game date is not before the last recorded game date
        if (isset($this->lastGameDates[$prediction['team']])) {
            if ($gameDate->greaterThan($this->lastGameDates[$prediction['team']])) {
                $this->lastGameDates[$prediction['team']] = $gameDate;
            } else {
                Log::warning("Game date {$gameDate->toDateString()} for team {$prediction['team']} is before the last recorded game date {$this->lastGameDates[$prediction['team']]->toDateString()}.");
            }
        } else {
            // If no last game date, initialize it
            $this->lastGameDates[$prediction['team']] = $gameDate;
        }

        if (isset($this->lastGameDates[$prediction['opponent']])) {
            if ($gameDate->greaterThan($this->lastGameDates[$prediction['opponent']])) {
                $this->lastGameDates[$prediction['opponent']] = $gameDate;
            } else {
                Log::warning("Game date {$gameDate->toDateString()} for team {$prediction['opponent']} is before the last recorded game date {$this->lastGameDates[$prediction['opponent']]->toDateString()}.");
            }
        } else {
            // If no last game date, initialize it
            $this->lastGameDates[$prediction['opponent']] = $gameDate;
        }
    }

    /**
     * Find a game between two teams in a given year.
     *
     * @param string $teamAbv
     * @param string $opponentTeamAbv
     * @param int $year
     * @return NflTeamSchedule|null
     */
    public function findGameForPrediction($teamAbv, $opponentTeamAbv, $year)
    {
        return NflTeamSchedule::where(function ($query) use ($teamAbv, $opponentTeamAbv) {
            $query->where('home_team', $teamAbv)
                ->where('away_team', $opponentTeamAbv);
        })
            ->orWhere(function ($query) use ($teamAbv, $opponentTeamAbv) {
                $query->where('home_team', $opponentTeamAbv)
                    ->where('away_team', $teamAbv);
            })
            ->whereYear('game_date', $year)
            ->where('season_type', 'Regular Season')
            ->first();
    }
}
