<?php

namespace App\Services;

use App\Models\Nfl\NflEloRating;
use App\Models\Nfl\NflTeamSchedule;
use App\Models\NflEloPrediction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class EloRatingService
{
    protected $kFactor;
    protected $startingRating;
    protected $homeFieldAdvantage;
    protected $restAdvantagePerDay;
    protected $maxRestDays;
    protected $seasonStartDate;
    protected $teamRatings = [];
    protected $lastGameDates = [];

    public function __construct()
    {
        $this->initializeEloSettings();
    }

    private function initializeEloSettings()
    {
        $this->kFactor = config('elo.k_factor', 40);
        $this->startingRating = config('elo.starting_rating', 1500);
        $this->homeFieldAdvantage = config('elo.home_field_advantage', 1.2);
        $this->restAdvantagePerDay = config('elo.rest_advantage_per_day', 0.5);
        $this->maxRestDays = config('elo.max_rest_days', 7);
        $this->seasonStartDate = Carbon::parse(config('elo.season_start_date', '2024-09-01'));
    }

    public function fetchTeams(): Collection
    {
        return NflTeamSchedule::select('home_team')
            ->union(NflTeamSchedule::select('away_team'))
            ->distinct()
            ->pluck('home_team');
    }

    public function processTeamPredictions($team, $year, $weeks, $today)
    {
        // Calculate Elo, predictions, and wins for the team
        $result = $this->calculateTeamEloForSeason($team, $year);

        // Store predictions for the team
        foreach ($result['predictions'] as $prediction) {
            $week = $prediction['week'];

            // Check if the week exists in the configuration
            if (!isset($weeks[$week])) {
                $this->logMissingWeek($week, $year);
                continue;
            }

            // Get the week end date from configuration
            $weekEnd = Carbon::parse($weeks[$week]['end']);

            // Store the prediction if applicable
            $this->storePredictionIfNeeded($team, $prediction, $year, $weekEnd, $today);
        }

        // Display the final Elo and total expected wins
        $totalWins = $result['actual_wins'] + $result['predicted_wins'];
        Log::info("Team: $team | Final Elo for $year season: {$result['final_elo']} | Actual Wins: {$result['actual_wins']} | Predicted Wins: {$result['predicted_wins']} | Total Expected Wins: $totalWins");

        return $result['final_elo'];
    }

    public function calculateTeamEloForSeason($teamAbv, $seasonYear)
    {
        // Fetch regular season games for the team
        $games = $this->fetchGamesForTeam($teamAbv, $seasonYear);
        if ($games->isEmpty()) {
            Log::warning("No games found for team: $teamAbv in season $seasonYear.");
            return ['final_elo' => $this->startingRating, 'predictions' => [], 'actual_wins' => 0, 'predicted_wins' => 0];
        }

        $currentElo = $this->getInitialTeamRating($teamAbv);
        $predictions = [];
        $actualWins = 0; // Track actual wins for completed games
        $predictedWins = 0; // Track predicted wins for future games

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

            // Track wins: If the game is completed, calculate actual wins
            if ($game->game_status === 'Completed') {
                $result = $this->getGameResult($game, $teamAbv);
                $actualWins += $result; // Increment actual wins (1 for win, 0 for loss)

                // Update Elo ratings based on the result
                $eloUpdate = $this->calculateEloForGame($currentElo, $opponentElo, $result, $game);
                $currentElo = $eloUpdate['team1_new_rating'];
                $this->teamRatings[$opponentTeamAbv] = $eloUpdate['team2_new_rating'];
            } else {
                // Predict wins for future games
                $predictedWins += $prediction['expected_outcome']; // Add the expected outcome (a probability between 0 and 1)
            }

            // Store the updated Elo for the team
            $this->teamRatings[$teamAbv] = $currentElo;
        }

        // Calculate total expected wins (actual + predicted)
        $totalExpectedWins = $actualWins + $predictedWins;

        // Store final Elo and total expected wins
        $this->storeFinalElo($teamAbv, $seasonYear, $currentElo, $totalExpectedWins);

        return [
            'final_elo' => round($currentElo),
            'predictions' => $predictions,
            'actual_wins' => $actualWins,
            'predicted_wins' => $predictedWins,
            'total_expected_wins' => $totalExpectedWins, // Include total expected wins in the return
        ];
    }

    private function fetchGamesForTeam(string $teamAbv, int $seasonYear): Collection
    {
        return NflTeamSchedule::where(function ($query) use ($teamAbv) {
            $query->where('home_team', $teamAbv)
                ->orWhere('away_team', $teamAbv);
        })
            ->whereYear('game_date', $seasonYear)
            ->where('season_type', 'Regular Season')
            ->orderBy('game_date', 'asc')
            ->get();
    }

    private function getInitialTeamRating(string $teamAbv): float
    {
        return $this->teamRatings[$teamAbv] ?? $this->startingRating;
    }

    private function getOpponentTeamAbv($teamAbv, $game)
    {
        return $game->home_team === $teamAbv ? $game->away_team : $game->home_team;
    }

    private function makePrediction(string $teamAbv, string $opponentTeamAbv, NflTeamSchedule $game, float $currentElo, float $opponentElo): array
    {
        $teamRestDays = $this->calculateRestDays($this->lastGameDates[$teamAbv] ?? null, $game->game_date);
        $opponentRestDays = $this->calculateRestDays($this->lastGameDates[$opponentTeamAbv] ?? null, $game->game_date);

        $netRestAdvantage = min($teamRestDays, $this->maxRestDays) * $this->restAdvantagePerDay
            - min($opponentRestDays, $this->maxRestDays) * $this->restAdvantagePerDay;

        $expectedOutcome = $this->calculateExpectedOutcome($currentElo, $opponentElo);
        $predictedSpread = $this->predictSpread($currentElo, $opponentElo, $game, $teamAbv, $netRestAdvantage);

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

    private function calculateRestDays(?Carbon $lastGameDate, string $currentGameDate): int
    {
        $currentGameDate = Carbon::parse($currentGameDate);

        if (!$lastGameDate) {
            return $currentGameDate->diffInDays($this->seasonStartDate);
        }

        $restDays = $lastGameDate->diffInDays($currentGameDate);
        if ($restDays < 0) {
            Log::warning("Negative rest days calculated for game date: {$currentGameDate->toDateString()}.");
            return 0;
        }

        return $restDays;
    }

    private function calculateExpectedOutcome(float $teamElo, float $opponentElo): float
    {
        return 1 / (1 + pow(10, ($opponentElo - $teamElo) / 400));
    }

    private function predictSpread(float $teamElo, float $opponentElo, NflTeamSchedule $game, string $teamAbv, float $restAdvantage): float
    {
        $expectedOutcome = $this->calculateExpectedOutcome($teamElo, $opponentElo);
        $eloDifference = -400 * log10((1 / $expectedOutcome) - 1);
        $predictedSpread = $eloDifference * 0.03 + ($game->home_team === $teamAbv ? $this->homeFieldAdvantage : -$this->homeFieldAdvantage) + $restAdvantage;

        return round(max(min($predictedSpread, 50), -50), 1);
    }

    private function getGameResult(NflTeamSchedule $game, string $teamAbv): float
    {
        return $game->home_pts === $game->away_pts ? 0.5 : ($game->home_team === $teamAbv ? ($game->home_pts > $game->away_pts ? 1 : 0) : ($game->away_pts > $game->home_pts ? 1 : 0));
    }

    private function calculateEloForGame(float $team1Elo, float $team2Elo, float $result, NflTeamSchedule $game): array
    {
        $movMultiplier = log(abs($game->home_pts - $game->away_pts) + 1) * (2.2 / (($team1Elo - $team2Elo) * 0.0047 + 2.2));
        $expectedOutcome = $this->calculateExpectedOutcome($team1Elo, $team2Elo);

        return [
            'team1_new_rating' => $team1Elo + $this->kFactor * ($result - $expectedOutcome) * $movMultiplier,
            'team2_new_rating' => $team2Elo + $this->kFactor * ((1 - $result) - (1 - $expectedOutcome)) * $movMultiplier,
        ];
    }

    private function storeFinalElo(string $teamAbv, int $seasonYear, float $currentElo, float $totalExpectedWins): void
    {
        NflEloRating::updateOrCreate(
            ['team' => $teamAbv, 'year' => $seasonYear],
            [
                'final_elo' => round($currentElo),
                'expected_wins' => $totalExpectedWins // Store total expected wins
            ]
        );
    }


    private function logMissingWeek(int $week, int $year): void
    {
        Log::warning("Week configuration missing for week: $week in year $year");
    }

    public function storePredictionIfNeeded(string $team, array $prediction, int $year, Carbon $weekEnd, Carbon $today): void
    {
        $game = $this->findGameForPrediction($team, $prediction['opponent'], $year);

        if (!$game) {
            Log::warning("Game not found for team {$team} vs {$prediction['opponent']} in year $year");
            return;
        }

        $existingPrediction = NflEloPrediction::where('game_id', $game->game_id)
            ->where('year', $year)
            ->first();

        if ($today->isAfter($weekEnd) && $existingPrediction && $existingPrediction->updated_at->greaterThan($today->copy()->subDays(3))) {
            return;
        }

        NflEloPrediction::updateOrCreate(
            ['game_id' => $game->game_id, 'year' => $year, 'week' => $prediction['week']],
            [
                'team' => $game->home_team,
                'opponent' => $game->away_team,
                'team_elo' => $prediction['team_elo'],
                'opponent_elo' => $prediction['opponent_elo'],
                'expected_outcome' => $prediction['expected_outcome'],
                'predicted_spread' => $prediction['predicted_spread'],
            ]
        );

        $this->updateLastGameDate($game->home_team, $game->game_date);
        $this->updateLastGameDate($game->away_team, $game->game_date);
    }

    private function findGameForPrediction(string $teamAbv, string $opponentTeamAbv, int $year): ?NflTeamSchedule
    {
        return NflTeamSchedule::where(function ($query) use ($teamAbv, $opponentTeamAbv) {
            $query->where('home_team', $teamAbv)->where('away_team', $opponentTeamAbv);
        })
            ->orWhere(function ($query) use ($teamAbv, $opponentTeamAbv) {
                $query->where('home_team', $opponentTeamAbv)->where('away_team', $teamAbv);
            })
            ->whereYear('game_date', $year)
            ->where('season_type', 'Regular Season')
            ->first();
    }

    private function updateLastGameDate(string $team, string $gameDate): void
    {
        $gameDate = Carbon::parse($gameDate);

        if (!isset($this->lastGameDates[$team]) || $gameDate->greaterThan($this->lastGameDates[$team])) {
            $this->lastGameDates[$team] = $gameDate;
        }
    }
}
