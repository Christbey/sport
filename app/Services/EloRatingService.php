<?php

namespace App\Services;

use AllowDynamicProperties;
use App\Models\Nfl\NflEloPrediction;
use App\Models\Nfl\NflEloRating;
use App\Models\Nfl\NflTeamSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

#[AllowDynamicProperties]
class EloRatingService
{
    private const GAME_STATUS_COMPLETED = 'Completed';
    private const SEASON_TYPE_REGULAR = 'Regular Season';
    private const MAX_SPREAD = 24.5;
    const POINTS_PER_ELO = 25;

    private array $settings;
    private array $teamRatings = [];
    private array $lastGameDates = [];
    private array $processedGames = [];
    private array $actualWins = [];
    private array $predictedWins = [];

    public function __construct()
    {
        $this->settings = $this->loadSettings();
    }

    private function loadSettings(): array
    {
        return [
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
        return NflTeamSchedule::distinct()
            ->pluck('home_team')
            ->merge(NflTeamSchedule::distinct()->pluck('away_team'))
            ->unique()
            ->values();
    }

    public function processTeamPredictions(string $team, int $year, array $weeks, Carbon $today): float
    {
        $this->initializeTeamStats($team);
        $result = $this->calculateTeamEloForSeason($team, $year);

        Log::info("Team: $team | Final Elo: {$result['final_elo']} | " .
            "Actual: {$this->actualWins[$team]} | " .
            "Predicted: {$this->predictedWins[$team]} | " .
            'Total: ' . ($this->actualWins[$team] + $this->predictedWins[$team]));

        $this->storePredictions($result['predictions'], $team, $year, $weeks, $today);
        return $result['final_elo'];
    }

    private function initializeTeamStats(string $team): void
    {
        $this->actualWins[$team] = 0;
        $this->predictedWins[$team] = 0;
    }

    private function calculateTeamEloForSeason(string $team, int $year): array
    {
        $games = $this->fetchSeasonGames($team, $year);
        if ($games->isEmpty()) {
            return $this->getDefaultSeasonResult();
        }

        return $this->processSeasonGames($team, $games, $year);
    }

    private function fetchSeasonGames(string $team, int $year): Collection
    {
        return NflTeamSchedule::where(function ($query) use ($team) {
            $query->where('home_team', $team)
                ->orWhere('away_team', $team);
        })
            ->whereYear('game_date', $year)
            ->where('season_type', self::SEASON_TYPE_REGULAR)
            ->orderBy('game_date')
            ->get();
    }

    private function getDefaultSeasonResult(): array
    {
        return [
            'final_elo' => $this->settings['starting_rating'],
            'predictions' => [],
            'actual_wins' => 0,
            'predicted_wins' => 0,
            'total_expected_wins' => 0
        ];
    }

    private function processSeasonGames(string $team, Collection $games, int $year): array
    {
        $currentElo = $this->getInitialElo($team, $year);
        $predictions = [];

        foreach ($games as $game) {
            if (!isset($this->processedGames[$game->id])) {
                $gameData = $this->processGame($team, $game, $currentElo);
                $predictions[] = $gameData['prediction'];
                $currentElo = $gameData['new_elo'];

                if ($game->game_status === self::GAME_STATUS_COMPLETED) {
                    $this->actualWins[$team] += $gameData['wins'];
                } else {
                    $this->predictedWins[$team] += $gameData['wins'];
                }

                $this->processedGames[$game->id] = true;
                $this->updateLastGameDate($game);
            }
        }

        $this->teamRatings[$team] = $currentElo;
        $totalWins = $this->actualWins[$team] + $this->predictedWins[$team];
        $this->storeFinalElo($team, $year, $currentElo, $totalWins);

        return [
            'final_elo' => round($currentElo),
            'predictions' => $predictions,
            'actual_wins' => $this->actualWins[$team],
            'predicted_wins' => $this->predictedWins[$team]
        ];
    }


    private function getInitialElo(string $team, int $year): float
    {
        return $this->settings['starting_rating'];  // Just return 1500
    }

    private function processGame(string $team, NflTeamSchedule $game, float $currentElo): array
    {
        $isHome = $game->home_team === $team;
        $opponent = $isHome ? $game->away_team : $game->home_team;
        $opponentElo = $this->teamRatings[$opponent] ?? $this->settings['starting_rating'];

        $gameElo = $currentElo;
        if ($isHome) {
            $gameElo += 30; // Fixed home field advantage in ELO points
        }

        $expectedOutcome = $this->calculateExpectedOutcome($gameElo, $opponentElo);
        $predictedSpread = $this->calculateSpread($gameElo, $opponentElo, $isHome, 0);

        if ($game->game_status === self::GAME_STATUS_COMPLETED) {
            $result = $this->getGameResult($game, $team);

            // Calculate margin of victory multiplier
            $scoreDiff = abs($game->home_pts - $game->away_pts);
            $movMultiplier = min(2.5, sqrt($scoreDiff) / 8 + 1);

            // Base K-factor starts higher and reduces slightly each week
            $baseK = max(32, 64 - ($game->game_week * 2));

            $eloChange = $baseK * ($result - $expectedOutcome) * $movMultiplier;
            $currentElo += $eloChange;

            return [
                'prediction' => [
                    'week' => $game->game_week,
                    'team_elo' => $gameElo,
                    'opponent_elo' => $opponentElo,
                    'expected_outcome' => $expectedOutcome,
                    'predicted_spread' => $predictedSpread
                ],
                'new_elo' => $currentElo,
                'wins' => $result
            ];
        }

        return [
            'prediction' => [
                'week' => $game->game_week,
                'team_elo' => $gameElo,
                'opponent_elo' => $opponentElo,
                'expected_outcome' => $expectedOutcome,
                'predicted_spread' => $predictedSpread
            ],
            'new_elo' => $currentElo,
            'wins' => $expectedOutcome
        ];
    }

    // ... [Include rest of your original methods] ...

    private function calculateExpectedOutcome(float $teamElo, float $opponentElo): float
    {
        // Adjusted scaling factor for more rating spread
        return 1 / (1 + pow(10, ($opponentElo - $teamElo) / 300));
    }

    private function calculateSpread(float $teamElo, float $opponentElo, bool $isHome, float $restAdvantage): float
    {
        // Base ELO difference
        $eloDifference = $teamElo - $opponentElo;

        // Home field advantage in ELO points
        if ($isHome) {
            $eloDifference += 30; // Fixed home advantage
        }

        // Add rest advantage
        $eloDifference += $restAdvantage;

        // Convert ELO difference to spread points
        // Using 25 points of ELO = 1 point spread (based on your const POINTS_PER_ELO)
        $spread = $eloDifference / self::POINTS_PER_ELO;

        // Cap at MAX_SPREAD
        return round(max(min($spread, self::MAX_SPREAD), -self::MAX_SPREAD), 1);
    }

    private function getGameResult(NflTeamSchedule $game, string $team): float
    {
        if ($game->home_pts === $game->away_pts) {
            return 0.5;
        }

        $isHome = $game->home_team === $team;
        return ($isHome && $game->home_pts > $game->away_pts) ||
        (!$isHome && $game->away_pts > $game->home_pts) ? 1 : 0;
    }

    private function updateLastGameDate(NflTeamSchedule $game): void
    {
        $gameDate = Carbon::parse($game->game_date);
        foreach ([$game->home_team, $game->away_team] as $team) {
            if (!isset($this->lastGameDates[$team]) || $gameDate->greaterThan($this->lastGameDates[$team])) {
                $this->lastGameDates[$team] = $gameDate;
            }
        }
    }

    private function storeFinalElo(string $team, int $year, float $elo, float $expectedWins): void
    {
        NflEloRating::updateOrCreate(
            ['team' => $team, 'year' => $year],
            [
                'final_elo' => round($elo),
                'expected_wins' => round($expectedWins, 1),
                'predicted_spread' => $this->calculateAverageSpread($team, $year)
            ]
        );
    }

    private function calculateAverageSpread(string $team, int $year): float
    {
        return round(NflEloPrediction::where('team', $team)
            ->where('year', $year)
            ->avg('predicted_spread') ?? 0, 1);
    }

    private function storePredictions(array $predictions, string $team, int $year, array $weeks, Carbon $today): void
    {
        foreach ($predictions as $prediction) {
            $weekEnd = $weeks[$prediction['week']]['end'] ?? null;
            if (!$weekEnd) {
                Log::warning("Week configuration missing for week: {$prediction['week']} in year $year");
                continue;
            }

            $this->storePredictionIfNeeded($team, $prediction, $year, Carbon::parse($weekEnd), $today);
        }
    }

    public function storePredictionIfNeeded(string $team, array $prediction, int $year, Carbon $weekEnd, Carbon $today): void
    {
        $game = $this->findGameForPrediction($team, $prediction, $year);

        if (!$game) {
            Log::warning("Game not found for team {$team} vs {$prediction['opponent']} in year $year");
            return;
        }

        if ($this->shouldSkipPredictionUpdate($game, $year, $today, $weekEnd)) {
            return;
        }

        $this->updatePrediction($game, $prediction, $year);
    }

    private function findGameForPrediction(string $team, array $prediction, int $year): ?NflTeamSchedule
    {
        return NflTeamSchedule::where(function ($query) use ($team, $prediction) {
            $query->where(function ($q) use ($team, $prediction) {
                $q->where('home_team', $team)
                    ->where('away_team', $prediction['opponent']);
            })->orWhere(function ($q) use ($team, $prediction) {
                $q->where('home_team', $prediction['opponent'])
                    ->where('away_team', $team);
            });
        })
            ->whereYear('game_date', $year)
            ->where('season_type', self::SEASON_TYPE_REGULAR)
            ->first();
    }

    private function shouldSkipPredictionUpdate(NflTeamSchedule $game, int $year, Carbon $today, Carbon $weekEnd): bool
    {
        $existing = NflEloPrediction::where('game_id', $game->game_id)
            ->where('year', $year)
            ->first();

        return $today->isAfter($weekEnd) &&
            $existing &&
            $existing->updated_at->greaterThan($today->copy()->subDays(3));
    }

    private function updatePrediction(NflTeamSchedule $game, array $prediction, int $year): void
    {
        NflEloPrediction::updateOrCreate(
            [
                'game_id' => $game->game_id,
                'year' => $year,
                'week' => $prediction['week']
            ],
            [
                'team' => $game->home_team,
                'opponent' => $game->away_team,
                'team_elo' => $prediction['team_elo'],
                'opponent_elo' => $prediction['opponent_elo'],
                'expected_outcome' => $prediction['expected_outcome'],
                'predicted_spread' => $prediction['predicted_spread']
            ]
        );
    }

    public function processBatch(array $teams, int $year): array
    {
        $this->resetBatchProcessing($teams);
        $allGames = $this->getAllGamesForBatch($teams, $year);

        foreach ($allGames as $game) {
            if (!isset($this->processedGames[$game->id])) {
                $this->processBatchGame($game);
            }
        }

        $this->storeFinalRatingsForBatch($teams, $year);
        return $this->teamRatings;
    }

    private function resetBatchProcessing(array $teams): void
    {
        $this->teamRatings = array_fill_keys($teams, $this->settings['starting_rating']);
        $this->lastGameDates = [];
        $this->processedGames = [];
        $this->actualWins = array_fill_keys($teams, 0);
        $this->predictedWins = array_fill_keys($teams, 0);
    }

    private function getAllGamesForBatch(array $teams, int $year): Collection
    {
        return NflTeamSchedule::whereIn('home_team', $teams)
            ->orWhereIn('away_team', $teams)
            ->whereYear('game_date', $year)
            ->where('season_type', self::SEASON_TYPE_REGULAR)
            ->orderBy('game_date')
            ->get();
    }

    private function processBatchGame(NflTeamSchedule $game): void
    {
        $homeTeam = $game->home_team;
        $awayTeam = $game->away_team;

        $homeElo = $this->teamRatings[$homeTeam];
        $awayElo = $this->teamRatings[$awayTeam];

        $restAdvantage = $this->calculateRestAdvantage($homeTeam, $awayTeam, $game->game_date);
        $gameHomeElo = $homeElo + $this->settings['home_advantage'];
        $expectedOutcome = $this->calculateExpectedOutcome($gameHomeElo, $awayElo);

        if ($game->game_status === self::GAME_STATUS_COMPLETED) {
            $result = $this->getGameResult($game, $homeTeam);

            // Use same multipliers as in processGame
            $scoreDiff = abs($game->home_pts - $game->away_pts);
            $marginMultiplier = min(2.0, log($scoreDiff + 1) / log(10));
            $resultDiff = abs($result - $expectedOutcome);
            $unexpectedBonus = $resultDiff > 0.5 ? 1.2 : 1.0;
            $weekMultiplier = 1 + (min($game->game_week, 18) / 18 * 0.15);

            $eloChange = $this->settings['k_factor'] *
                ($result - $expectedOutcome) *
                $marginMultiplier *
                $unexpectedBonus *
                $weekMultiplier;

            $this->teamRatings[$homeTeam] += $eloChange;
            $this->teamRatings[$awayTeam] -= $eloChange;

            $this->actualWins[$homeTeam] += $result;
            $this->actualWins[$awayTeam] += (1 - $result);
        } else {
            $this->predictedWins[$homeTeam] += $expectedOutcome;
            $this->predictedWins[$awayTeam] += (1 - $expectedOutcome);
        }

        $this->storePrediction($game, $homeElo, $awayElo, $expectedOutcome, $restAdvantage);
        $this->processedGames[$game->id] = true;
        $this->updateLastGameDate($game);
    }

    private function calculateRestAdvantage(string $team, string $opponent, string $gameDate): float
    {
        $teamRest = $this->calculateRestDays($this->lastGameDates[$team] ?? null, $gameDate);
        $opponentRest = $this->calculateRestDays($this->lastGameDates[$opponent] ?? null, $gameDate);

        return ($this->settings['rest_advantage'] * min($teamRest, $this->settings['max_rest_days'])) -
            ($this->settings['rest_advantage'] * min($opponentRest, $this->settings['max_rest_days']));
    }

    private function calculateRestDays(?Carbon $lastGame, string $gameDate): int
    {
        if (!$lastGame) {
            return Carbon::parse($gameDate)->diffInDays($this->settings['season_start']);
        }
        return max(0, $lastGame->diffInDays(Carbon::parse($gameDate)));
    }

    private function storePrediction(NflTeamSchedule $game, float $homeElo, float $awayElo, float $expectedOutcome, float $restAdvantage): void
    {
        $predictedSpread = $this->calculateSpread($homeElo, $awayElo, true, $restAdvantage);
        $gameYear = Carbon::parse($game->game_date)->year;

        NflEloPrediction::updateOrCreate(
            [
                'game_id' => $game->game_id,
                'year' => $gameYear,
                'week' => $game->game_week
            ],
            [
                'team' => $game->home_team,
                'opponent' => $game->away_team,
                'team_elo' => $homeElo,
                'opponent_elo' => $awayElo,
                'expected_outcome' => $expectedOutcome,
                'predicted_spread' => $predictedSpread
            ]
        );
    }

    private function storeFinalRatingsForBatch(array $teams, int $year): void
    {
        foreach ($teams as $team) {
            $totalWins = $this->actualWins[$team] + $this->predictedWins[$team];
            $this->storeFinalElo($team, $year, $this->teamRatings[$team], $totalWins);
        }
    }

    private function calculateStreakBonus(string $team): float
    {
        // Get last 5 games
        $recentGames = NflTeamSchedule::where(function ($query) use ($team) {
            $query->where('home_team', $team)
                ->orWhere('away_team', $team);
        })
            ->where('game_status', self::GAME_STATUS_COMPLETED)
            ->orderByDesc('game_date')
            ->limit(5)
            ->get();

        if ($recentGames->isEmpty()) {
            return 1.0;
        }

        $wins = 0;
        $losses = 0;

        foreach ($recentGames as $game) {
            $result = $this->getGameResult($game, $team);
            if ($result === 1) {
                $wins++;
            } elseif ($result === 0) {
                $losses++;
            }
        }

        // Adjust multiplier based on streak (0.8-1.2 range)
        return 1.0 + (($wins - $losses) / 25.0);
    }

    private function calculateEloChange(
        NflTeamSchedule $game,
        float           $teamElo,
        float           $opponentElo,
        float           $result,
        float           $expectedOutcome
    ): float
    {
        $movMultiplier = $this->calculateMovementMultiplier($game, $teamElo, $opponentElo);
        return $this->settings['k_factor'] * ($result - $expectedOutcome) * $movMultiplier;
    }

    private function calculateMovementMultiplier(NflTeamSchedule $game, float $teamElo, float $opponentElo): float
    {
        $scoreDiff = abs($game->home_pts - $game->away_pts);
        return log($scoreDiff + 1) * (2.2 / (($teamElo - $opponentElo) * 0.00047 + 2.2));
    }
}