<?php

namespace App\Services;

use App\Models\Nfl\NflEloPrediction;
use App\Models\Nfl\NflEloRating;
use App\Models\Nfl\NflTeamSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class EloRatingService
{
    protected array $settings;
    protected array $teamRatings = [];
    protected array $lastGameDates = [];

    public function __construct()
    {
        $this->settings = [
            'k_factor' => config('elo.k_factor', 40),
            'starting_rating' => config('elo.starting_rating', 1500),
            'home_advantage' => config('elo.home_field_advantage', 1.2),
            'rest_advantage' => config('elo.rest_advantage_per_day', 0.5),
            'max_rest_days' => config('elo.max_rest_days', 7),
            'season_start' => Carbon::parse(config('elo.season_start_date', '2024-09-01'))
        ];
    }

    public function fetchTeams(): Collection
    {
        return NflTeamSchedule::select('home_team')
            ->union(NflTeamSchedule::select('away_team'))
            ->distinct()
            ->pluck('home_team');
    }

    public function processTeamPredictions($team, $year, $weeks, $today): float
    {
        $result = $this->calculateTeamEloForSeason($team, $year);

        foreach ($result['predictions'] as $prediction) {
            $weekEnd = $weeks[$prediction['week']]['end'] ?? null;
            if (!$weekEnd) {
                Log::warning("Week configuration missing for week: {$prediction['week']} in year $year");
                continue;
            }

            $this->storePredictionIfNeeded($team, $prediction, $year, Carbon::parse($weekEnd), $today);
        }

        $totalWins = $result['actual_wins'] + $result['predicted_wins'];
        Log::info("Team: $team | Final Elo $year: {$result['final_elo']} | Actual: {$result['actual_wins']} | Predicted: {$result['predicted_wins']} | Total: $totalWins");

        return $result['final_elo'];
    }

    public function calculateTeamEloForSeason($team, $year): array
    {
        $games = NflTeamSchedule::where(fn($q) => $q->where('home_team', $team)->orWhere('away_team', $team))
            ->whereYear('game_date', $year)
            ->where('season_type', 'Regular Season')
            ->orderBy('game_date')
            ->get();

        if ($games->isEmpty()) {
            Log::warning("No games found for team: $team in season $year.");
            return ['final_elo' => $this->settings['starting_rating'], 'predictions' => [], 'actual_wins' => 0, 'predicted_wins' => 0];
        }

        $currentElo = $this->teamRatings[$team] ?? $this->settings['starting_rating'];
        $predictions = [];
        $actualWins = $predictedWins = 0;

        foreach ($games as $game) {
            $isHome = $game->home_team === $team;
            $opponent = $isHome ? $game->away_team : $game->home_team;
            $opponentElo = $this->teamRatings[$opponent] ?? $this->settings['starting_rating'];

            // Apply home field advantage
            $gameElo = $currentElo + ($isHome ? $this->settings['home_advantage'] : 0);

            // Calculate rest advantage
            $restAdvantage = $this->calculateRestAdvantage($team, $opponent, $game->game_date);

            // Make prediction
            $expectedOutcome = $this->calculateExpectedOutcome($gameElo, $opponentElo);
            $predictedSpread = $this->calculateSpread($gameElo, $opponentElo, $isHome, $restAdvantage);

            $predictions[] = [
                'week' => $game->game_week,
                'team' => $team,
                'opponent' => $opponent,
                'team_elo' => $gameElo,
                'opponent_elo' => $opponentElo,
                'expected_outcome' => $expectedOutcome,
                'predicted_spread' => $predictedSpread
            ];

            if ($game->game_status === 'Completed') {
                $result = $this->getGameResult($game, $team);
                $actualWins += $result;

                // Update Elos based on actual result
                $movMultiplier = $this->calculateMovementMultiplier($game, $gameElo, $opponentElo);
                $eloChange = $this->settings['k_factor'] * ($result - $expectedOutcome) * $movMultiplier;
                $currentElo += $eloChange;
                $this->teamRatings[$opponent] = $opponentElo - $eloChange;
            } else {
                $predictedWins += $expectedOutcome;
            }

            $this->teamRatings[$team] = $currentElo;
            $this->updateLastGameDate($game);
        }

        $totalWins = $actualWins + $predictedWins;
        $this->storeFinalElo($team, $year, $currentElo, $totalWins);

        return [
            'final_elo' => round($currentElo),
            'predictions' => $predictions,
            'actual_wins' => $actualWins,
            'predicted_wins' => $predictedWins,
            'total_expected_wins' => $totalWins
        ];
    }

    protected function calculateRestAdvantage(string $team, string $opponent, string $gameDate): float
    {
        $teamRest = $this->calculateRestDays($this->lastGameDates[$team] ?? null, $gameDate);
        $opponentRest = $this->calculateRestDays($this->lastGameDates[$opponent] ?? null, $gameDate);

        return ($this->settings['rest_advantage'] * min($teamRest, $this->settings['max_rest_days'])) -
            ($this->settings['rest_advantage'] * min($opponentRest, $this->settings['max_rest_days']));
    }

    protected function calculateRestDays(?Carbon $lastGame, string $gameDate): int
    {
        if (!$lastGame) {
            return Carbon::parse($gameDate)->diffInDays($this->settings['season_start']);
        }
        return max(0, $lastGame->diffInDays(Carbon::parse($gameDate)));
    }

    protected function calculateExpectedOutcome(float $teamElo, float $opponentElo): float
    {
        return 1 / (1 + pow(10, ($opponentElo - $teamElo) / 400));
    }

    protected function calculateSpread(float $teamElo, float $opponentElo, bool $isHome, float $restAdvantage): float
    {
        $eloDiff = -400 * log10((1 / $this->calculateExpectedOutcome($teamElo, $opponentElo)) - 1);
        $spread = $eloDiff * 0.03 +
            ($isHome ? $this->settings['home_advantage'] : -$this->settings['home_advantage']) +
            $restAdvantage / 10;

        return round(max(min($spread, 19.5), -19.5), 1);
    }

    protected function getGameResult(NflTeamSchedule $game, string $team): float
    {
        if ($game->home_pts === $game->away_pts) return 0.5;
        $isHome = $game->home_team === $team;
        return ($isHome && $game->home_pts > $game->away_pts) || (!$isHome && $game->away_pts > $game->home_pts) ? 1 : 0;
    }

    protected function calculateMovementMultiplier(NflTeamSchedule $game, float $teamElo, float $opponentElo): float
    {
        $scoreDiff = abs($game->home_pts - $game->away_pts);
        return log($scoreDiff + 1) * (2.2 / (($teamElo - $opponentElo) * 0.00047 + 2.2));
    }

    protected function updateLastGameDate(NflTeamSchedule $game): void
    {
        $gameDate = Carbon::parse($game->game_date);
        foreach ([$game->home_team, $game->away_team] as $team) {
            if (!isset($this->lastGameDates[$team]) || $gameDate->greaterThan($this->lastGameDates[$team])) {
                $this->lastGameDates[$team] = $gameDate;
            }
        }
    }

    protected function storeFinalElo(string $team, int $year, float $elo, float $expectedWins): void
    {
        NflEloRating::updateOrCreate(
            ['team' => $team, 'year' => $year],
            [
                'final_elo' => round($elo),
                'expected_wins' => $expectedWins,
                'predicted_spread' => 0
            ]
        );
    }

    public function storePredictionIfNeeded(string $team, array $prediction, int $year, Carbon $weekEnd, Carbon $today): void
    {
        $game = NflTeamSchedule::where(function ($q) use ($team, $prediction) {
            $q->where(function ($q) use ($team, $prediction) {
                $q->where('home_team', $team)->where('away_team', $prediction['opponent']);
            })->orWhere(function ($q) use ($team, $prediction) {
                $q->where('home_team', $prediction['opponent'])->where('away_team', $team);
            });
        })
            ->whereYear('game_date', $year)
            ->where('season_type', 'Regular Season')
            ->first();

        if (!$game) {
            Log::warning("Game not found for team {$team} vs {$prediction['opponent']} in year $year");
            return;
        }

        $existing = NflEloPrediction::where('game_id', $game->game_id)
            ->where('year', $year)
            ->first();

        if ($today->isAfter($weekEnd) && $existing && $existing->updated_at->greaterThan($today->copy()->subDays(3))) {
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
    }
}