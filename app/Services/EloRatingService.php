<?php

namespace App\Services;

use AllowDynamicProperties;
use App\Models\Nfl\NflEloPrediction;
use App\Models\Nfl\NflEloRating;
use App\Models\Nfl\NflTeamSchedule;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

#[AllowDynamicProperties] class EloRatingService
{
    private const GAME_STATUS_COMPLETED = 'Completed';
    private const SEASON_TYPE_REGULAR = 'Regular Season';
    private const MAX_SPREAD = 19.5;

    private array $settings;
    private array $teamRatings = [];
    private array $lastGameDates = [];

    public function __construct()
    {
        $this->settings = $this->loadSettings();
    }

    /**
     * Load ELO settings from configuration.
     */
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

    /**
     * Fetch unique team list.
     */
    public function fetchTeams(): Collection
    {
        return NflTeamSchedule::distinct()
            ->pluck('home_team')
            ->merge(NflTeamSchedule::distinct()->pluck('away_team'))
            ->unique()
            ->values();
    }

    /**
     * Process team predictions for a season.
     */
    public function processTeamPredictions(string $team, int $year, array $weeks, Carbon $today): float
    {
        $result = $this->calculateTeamEloForSeason($team, $year);
        $this->storePredictions($result['predictions'], $team, $year, $weeks, $today);

        $totalWins = $result['actual_wins'] + $result['predicted_wins'];
        Log::info("Team: $team | Final Elo $year: {$result['final_elo']} | Actual: {$result['actual_wins']} | Predicted: {$result['predicted_wins']} | Total: $totalWins");

        return $result['final_elo'];
    }


    /**
     * Calculate team ELO ratings for a season.
     */
    public function calculateTeamEloForSeason(string $team, int $year): array
    {
        $games = $this->fetchSeasonGames($team, $year);

        if ($games->isEmpty()) {
            return $this->getDefaultSeasonResult();
        }

        return $this->processSeasonGames($team, $games, $year);  // Pass the year parameter here
    }

    /**
     * Fetch games for a team in a season.
     */
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

    /**
     * Get default season result when no games found.
     */
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

    /**
     * Process all games in a season for a team.
     */
    private function processSeasonGames(string $team, Collection $games, int $year): array
    {
        $currentElo = $this->teamRatings[$team] ?? $this->settings['starting_rating'];
        $predictions = [];
        $stats = ['actual_wins' => 0, 'predicted_wins' => 0];

        foreach ($games as $game) {
            $gameData = $this->processGame($team, $game, $currentElo);
            $predictions[] = $gameData['prediction'];
            $currentElo = $gameData['new_elo'];
            $stats[$gameData['win_type']] += $gameData['wins'];

            $this->teamRatings[$team] = $currentElo;
            $this->updateLastGameDate($game);
        }

        $this->storeFinalElo($team, $year, $currentElo, $stats['actual_wins'] + $stats['predicted_wins']);

        return [
            'final_elo' => round($currentElo),
            'predictions' => $predictions,
            'actual_wins' => $stats['actual_wins'],
            'predicted_wins' => $stats['predicted_wins'],
            'total_expected_wins' => $stats['actual_wins'] + $stats['predicted_wins']
        ];
    }

    /**
     * Process a single game.
     */
    private function processGame(string $team, NflTeamSchedule $game, float $currentElo): array
    {
        $isHome = $game->home_team === $team;
        $opponent = $isHome ? $game->away_team : $game->home_team;
        $opponentElo = $this->teamRatings[$opponent] ?? $this->settings['starting_rating'];

        $gameElo = $currentElo + ($isHome ? $this->settings['home_advantage'] : 0);
        $restAdvantage = $this->calculateRestAdvantage($team, $opponent, $game->game_date);
        $expectedOutcome = $this->calculateExpectedOutcome($gameElo, $opponentElo);

        $prediction = [
            'week' => $game->game_week,
            'team' => $team,
            'opponent' => $opponent,
            'team_elo' => $gameElo,
            'opponent_elo' => $opponentElo,
            'expected_outcome' => $expectedOutcome,
            'predicted_spread' => $this->calculateSpread($gameElo, $opponentElo, $isHome, $restAdvantage)
        ];

        if ($game->game_status === self::GAME_STATUS_COMPLETED) {
            $result = $this->getGameResult($game, $team);
            $eloChange = $this->calculateEloChange($game, $gameElo, $opponentElo, $result, $expectedOutcome);
            $currentElo += $eloChange;
            $this->teamRatings[$opponent] = $opponentElo - $eloChange;

            return [
                'prediction' => $prediction,
                'new_elo' => $currentElo,
                'win_type' => 'actual_wins',
                'wins' => $result
            ];
        }

        return [
            'prediction' => $prediction,
            'new_elo' => $currentElo,
            'win_type' => 'predicted_wins',
            'wins' => $expectedOutcome
        ];
    }

    /**
     * Calculate rest advantage between teams.
     */
    private function calculateRestAdvantage(string $team, string $opponent, string $gameDate): float
    {
        $teamRest = $this->calculateRestDays($this->lastGameDates[$team] ?? null, $gameDate);
        $opponentRest = $this->calculateRestDays($this->lastGameDates[$opponent] ?? null, $gameDate);

        return ($this->settings['rest_advantage'] * min($teamRest, $this->settings['max_rest_days'])) -
            ($this->settings['rest_advantage'] * min($opponentRest, $this->settings['max_rest_days']));
    }

    /**
     * Calculate rest days for a team.
     */
    private function calculateRestDays(?Carbon $lastGame, string $gameDate): int
    {
        if (!$lastGame) {
            return Carbon::parse($gameDate)->diffInDays($this->settings['season_start']);
        }
        return max(0, $lastGame->diffInDays(Carbon::parse($gameDate)));
    }

    /**
     * Calculate expected outcome based on ELO ratings.
     */
    private function calculateExpectedOutcome(float $teamElo, float $opponentElo): float
    {
        return 1 / (1 + pow(10, ($opponentElo - $teamElo) / 400));
    }

    /**
     * Calculate predicted spread for a game.
     */
    private function calculateSpread(float $teamElo, float $opponentElo, bool $isHome, float $restAdvantage): float
    {
        $eloDiff = -400 * log10((1 / $this->calculateExpectedOutcome($teamElo, $opponentElo)) - 1);
        $spread = $eloDiff * 0.03 +
            ($isHome ? $this->settings['home_advantage'] : -$this->settings['home_advantage']) +
            $restAdvantage / 10;

        return round(max(min($spread, self::MAX_SPREAD), -self::MAX_SPREAD), 1);
    }

    /**
     * Get game result for a team.
     */
    private function getGameResult(NflTeamSchedule $game, string $team): float
    {
        if ($game->home_pts === $game->away_pts) {
            return 0.5;
        }

        $isHome = $game->home_team === $team;
        return ($isHome && $game->home_pts > $game->away_pts) ||
        (!$isHome && $game->away_pts > $game->home_pts) ? 1 : 0;
    }

    /**
     * Calculate ELO change for a completed game.
     */
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

    /**
     * Calculate movement multiplier for ELO adjustment.
     */
    private function calculateMovementMultiplier(NflTeamSchedule $game, float $teamElo, float $opponentElo): float
    {
        $scoreDiff = abs($game->home_pts - $game->away_pts);
        return log($scoreDiff + 1) * (2.2 / (($teamElo - $opponentElo) * 0.00047 + 2.2));
    }

    /**
     * Update last game date for teams.
     */
    private function updateLastGameDate(NflTeamSchedule $game): void
    {
        $gameDate = Carbon::parse($game->game_date);
        foreach ([$game->home_team, $game->away_team] as $team) {
            if (!isset($this->lastGameDates[$team]) || $gameDate->greaterThan($this->lastGameDates[$team])) {
                $this->lastGameDates[$team] = $gameDate;
            }
        }
    }

    /**
     * Store final ELO rating for a team.
     */
    private function storeFinalElo(string $team, int $year, float $elo, float $expectedWins): void
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

    /**
     * Store predictions for games.
     */
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

    /**
     * Store prediction if needed.
     */
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

    /**
     * Find game for prediction.
     */
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

    /**
     * Check if prediction update should be skipped.
     */
    private function shouldSkipPredictionUpdate(NflTeamSchedule $game, int $year, Carbon $today, Carbon $weekEnd): bool
    {
        $existing = NflEloPrediction::where('game_id', $game->game_id)
            ->where('year', $year)
            ->first();

        return $today->isAfter($weekEnd) &&
            $existing &&
            $existing->updated_at->greaterThan($today->copy()->subDays(3));
    }

    /**
     * Update prediction record.
     */
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
                'predicted_spread' => $prediction['predicted_spread'],
            ]
        );
    }

    public function processBatch(array $teams, int $year): array
    {
        // Initialize tracking properties
        $this->teamRatings = [];
        $this->lastGameDates = [];
        $this->processedGames = [];

        // Initialize all teams with starting ratings
        foreach ($teams as $team) {
            $this->teamRatings[$team] = $this->settings['starting_rating'];
        }

        // Get all games for the season, sorted by date
        $allGames = NflTeamSchedule::whereIn('home_team', $teams)
            ->orWhereIn('away_team', $teams)
            ->whereYear('game_date', $year)
            ->where('season_type', self::SEASON_TYPE_REGULAR)
            ->orderBy('game_date')
            ->get();

        // Process games chronologically
        foreach ($allGames as $game) {
            if (isset($this->processedGames[$game->id])) {
                continue;
            }

            $homeTeam = $game->home_team;
            $awayTeam = $game->away_team;

            $homeElo = $this->teamRatings[$homeTeam] ?? $this->settings['starting_rating'];
            $awayElo = $this->teamRatings[$awayTeam] ?? $this->settings['starting_rating'];

            // Calculate expected outcome
            $expectedOutcome = $this->calculateExpectedOutcome($homeElo, $awayElo);

            if ($game->game_status === self::GAME_STATUS_COMPLETED) {
                $result = $this->getGameResult($game, $homeTeam);
                $movMultiplier = $this->calculateMovementMultiplier($game, $homeElo, $awayElo);
                $eloChange = $this->settings['k_factor'] * ($result - $expectedOutcome) * $movMultiplier;

                // Update both teams' ratings
                $this->teamRatings[$homeTeam] = $homeElo + $eloChange;
                $this->teamRatings[$awayTeam] = $awayElo - $eloChange;

                Log::info('Game processed', [
                    'game_id' => $game->game_id,
                    'home_team' => $homeTeam,
                    'away_team' => $awayTeam,
                    'elo_change' => $eloChange
                ]);
            }

            // Store prediction
            try {
                $this->storePrediction($game, $homeElo, $awayElo, $expectedOutcome);
            } catch (Exception $e) {
                Log::error('Failed to store prediction', [
                    'game_id' => $game->game_id,
                    'error' => $e->getMessage()
                ]);
            }

            $this->processedGames[$game->id] = true;
            $this->updateLastGameDate($game);
        }

        // Store final ratings
        foreach ($teams as $team) {
            if (isset($this->teamRatings[$team])) {
                $this->storeFinalElo($team, $year, $this->teamRatings[$team], 0); // Calculate expected wins properly if needed
            }
        }

        return $this->teamRatings;
    }

    private function storePrediction(NflTeamSchedule $game, float $homeElo, float $awayElo, float $expectedOutcome): void
    {
        $restAdvantage = $this->calculateRestAdvantage($game->home_team, $game->away_team, $game->game_date);
        $predictedSpread = $this->calculateSpread($homeElo, $awayElo, true, $restAdvantage);

        // Parse the game date to get the year
        $gameYear = Carbon::parse($game->game_date)->year;

        NflEloPrediction::updateOrCreate(
            [
                'game_id' => $game->game_id,
                'year' => $gameYear, // Use parsed year instead of accessing directly
                'week' => $game->game_week
            ],
            [
                'team' => $game->home_team,
                'opponent' => $game->away_team,
                'team_elo' => $homeElo,
                'opponent_elo' => $awayElo,
                'expected_outcome' => $expectedOutcome,
                'predicted_spread' => $predictedSpread,
            ]
        );
    }

}