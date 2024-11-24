<?php

namespace App\Services;

use App\DataTransferObjects\GameRatingsDTO;
use App\Enums\Division;
use App\Models\CollegeFootball\{CollegeFootballGame, CollegeFootballHypothetical};
use App\Repositories\RatingsRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\{Cache, DB, Log};

class HypotheticalSpreadService
{
    private const CACHE_PREFIX = 'hypothetical_spread_';
    private const CACHE_TTL = 3600; // 1 hour
    private const BATCH_SIZE = 100;

    private const SPREAD_CONSTANTS = [
        'CONFERENCE_MULTIPLIER' => 1.4,
        'ELO_DIVISOR' => 40,
        'FPI_DIVISOR' => 1.4,
        'SAGARIN_DIVISOR' => 1.1,
        'ADVANCED_STATS_MULTIPLIER' => 1.6,
        'PPA_MULTIPLIER' => 0.01
    ];

    private const REQUIRED_ADVANCED_STATS = [
        'offense_ppa',
        'defense_ppa',
        'offense_success_rate',
        'defense_success_rate',
        'offense_explosiveness',
        'defense_explosiveness',
        'offense_rushing_ppa',
        'defense_rushing_ppa',
        'offense_passing_ppa',
        'defense_passing_ppa',
        'offense_line_yards',
        'defense_line_yards'
    ];

    public function __construct(
        private readonly RatingsRepository $ratingsRepository
    )
    {
    }

    public function processCurrentWeekGames(bool $forceRecalculate = false): void
    {
        try {
            $this->fetchRelevantGames(null, $forceRecalculate)
                ->chunk(self::BATCH_SIZE)
                ->each(fn($batch) => $batch->each(fn($game) => $this->processGame($game)));
        } catch (Exception $e) {
            Log::error('Error processing weekly games', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    // Update the process current week games method to allow force recalculation

    public function fetchRelevantGames(?int $week = null, bool $forceRecalculate = false): Collection
    {
        // Remove cache for forced recalculations
        if ($forceRecalculate) {
            $this->clearGamesCache();
        }

        $query = CollegeFootballGame::query()
            ->where('home_division', Division::FBS->value)
            ->where('away_division', Division::FBS->value)
            ->where('season', config('college_football.season'));

        if ($week) {
            $query->where('week', $week);
        } else {
            $query->where('start_date', '>=', Carbon::today())
                ->whereNull('spread');
        }

        Log::info('Fetching games with criteria', [
            'week' => $week,
            'force_recalculate' => $forceRecalculate,
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);

        $games = $query->with(['homeTeam', 'awayTeam'])->get();

        if ($games->isEmpty()) {
            Log::warning('No games found with criteria', [
                'week' => $week,
                'season' => config('college_football.season'),
                'force_recalculate' => $forceRecalculate
            ]);
        } else {
            Log::info('Found games to process', [
                'count' => $games->count(),
                'games' => $games->map(fn($game) => [
                    'id' => $game->id,
                    'home' => $game->homeTeam->school,
                    'away' => $game->awayTeam->school,
                    'week' => $game->week
                ])
            ]);
        }

        return $games;
    }

    // Add a method specifically for recalculating a specific week

    public function clearGamesCache(): void
    {
        Cache::forget(self::CACHE_PREFIX . 'current_week_games');
        Cache::forget(self::CACHE_PREFIX . 'current_week');
        Log::info('Games cache cleared');
    }

    public function processGame(CollegeFootballGame $game): void
    {
        if (!$this->validateGame($game)) return;

        try {
            DB::beginTransaction();

            $ratings = $this->ratingsRepository->getRatings($game);

            if (!$this->validateRatings($ratings)) {
                Log::warning('Incomplete ratings', ['game_id' => $game->id]);
                DB::rollBack();
                return;
            }

            $spread = $this->calculateSpread($game, $ratings);
            $this->storeResults($game, $spread, $ratings);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error processing game', [
                'game_id' => $game->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function validateGame(CollegeFootballGame $game): bool
    {
        return $game->homeTeam?->exists() && $game->awayTeam?->exists();
    }

    private function validateRatings(GameRatingsDTO $ratings): bool
    {
        return !is_null($ratings->elo['home']) &&
            !is_null($ratings->elo['away']) &&
            !is_null($ratings->fpi['home']) &&
            !is_null($ratings->fpi['away']);
    }

    private function calculateSpread(CollegeFootballGame $game, GameRatingsDTO $ratings): float
    {
        $components = $this->calculateBasicComponents($ratings);
        $weight = 2; // Base weight for ELO and FPI

        if (isset($ratings->sagarin['home'], $ratings->sagarin['away'])) {
            $components['sagarin'] = $this->calculateRatingSpread(
                $ratings->sagarin['home'],
                $ratings->sagarin['away'],
                self::SPREAD_CONSTANTS['SAGARIN_DIVISOR']
            );
            $weight++;
        }

        if ($this->validateAdvancedStats($ratings->advancedStats)) {
            $components['advanced'] = $this->calculateAdvancedStatsSpread($ratings->advancedStats);
            $weight += 2;
        }

        $multiplier = $game->homeTeam->conference === $game->awayTeam->conference
            ? self::SPREAD_CONSTANTS['CONFERENCE_MULTIPLIER']
            : 1.0;

        $spread = (array_sum($components) / $weight) * $multiplier;

        Log::info('Spread calculation', [
            'game_id' => $game->id,
            'components' => $components,
            'weight' => $weight,
            'multiplier' => $multiplier,
            'final_spread' => $spread
        ]);

        return round($spread, 2);
    }

    private function calculateBasicComponents(GameRatingsDTO $ratings): array
    {
        return [
            'fpi' => $this->calculateRatingSpread(
                $ratings->fpi['home'],
                $ratings->fpi['away'],
                self::SPREAD_CONSTANTS['FPI_DIVISOR']
            ),
            'elo' => $this->calculateRatingSpread(
                $ratings->elo['home'],
                $ratings->elo['away'],
                self::SPREAD_CONSTANTS['ELO_DIVISOR']
            )
        ];
    }

    private function calculateRatingSpread(float $home, float $away, float $divisor): float
    {
        return ($home - $away) / $divisor;
    }

    private function validateAdvancedStats(array $stats): bool
    {
        if (!isset($stats['home'], $stats['away'])) return false;

        foreach (self::REQUIRED_ADVANCED_STATS as $property) {
            if (!property_exists($stats['home'], $property) ||
                !property_exists($stats['away'], $property) ||
                is_null($stats['home']->$property) ||
                is_null($stats['away']->$property)) {
                return false;
            }
        }

        return true;
    }

    private function calculateAdvancedStatsSpread(array $stats): float
    {
        $components = [];
        $home = $stats['home'];
        $away = $stats['away'];

        foreach (self::REQUIRED_ADVANCED_STATS as $stat) {
            $multiplier = str_contains($stat, 'ppa')
                ? self::SPREAD_CONSTANTS['PPA_MULTIPLIER']
                : self::SPREAD_CONSTANTS['ADVANCED_STATS_MULTIPLIER'];

            $components[] = $this->calculateMatchupComponent($home->$stat, $away->$stat, $multiplier);
        }

        return array_sum($components);
    }

    private function calculateMatchupComponent(float $offense, float $defense, float $multiplier): float
    {
        return ($offense - $defense) * $multiplier;
    }

    private function storeResults(CollegeFootballGame $game, float $spread, GameRatingsDTO $ratings): void
    {
        CollegeFootballHypothetical::updateOrCreate(
            ['game_id' => $game->id],
            [
                'week' => $game->week,
                'home_team_id' => $game->homeTeam->id,
                'away_team_id' => $game->awayTeam->id,
                'home_team_school' => $game->homeTeam->school,
                'away_team_school' => $game->awayTeam->school,
                'home_elo' => $ratings->elo['home'],
                'away_elo' => $ratings->elo['away'],
                'home_fpi' => $ratings->fpi['home'],
                'away_fpi' => $ratings->fpi['away'],
                'home_sagarin' => $ratings->sagarin['home'],
                'away_sagarin' => $ratings->sagarin['away'],
                'home_sos' => $ratings->strengthOfSchedule['home'],
                'away_sos' => $ratings->strengthOfSchedule['away'],
                'home_special_teams' => $ratings->fpi['home_special_teams'],
                'away_special_teams' => $ratings->fpi['away_special_teams'],
                'hypothetical_spread' => $spread,
            ]
        );

        Log::info('Spread calculated', [
            'game' => "{$game->awayTeam->school} @ {$game->homeTeam->school}",
            'spread' => $spread
        ]);
    }

    public function recalculateWeek(int $week): void
    {
        $this->clearGamesCache();
        $games = $this->fetchRelevantGames($week, true);

        $games->chunk(self::BATCH_SIZE)
            ->each(fn($batch) => $batch->each(fn($game) => $this->processGame($game)));
    }

    public function refreshGames(): Collection
    {
        $this->clearGamesCache();
        return $this->fetchRelevantGames();
    }

    private function determineCurrentWeek(): ?int
    {
        return Cache::remember(self::CACHE_PREFIX . 'current_week', self::CACHE_TTL, function () {
            $today = Carbon::today();

            foreach (config('college_football.weeks', []) as $week => $dates) {
                if ($today->between(Carbon::parse($dates['start']), Carbon::parse($dates['end']))) {
                    Log::info('Determined current week', ['week' => $week]);
                    return (int)$week;
                }
            }

            Log::warning('Could not determine current week');
            return null;
        });
    }

    private function logGamesFetch(Collection $games, ?int $week, int $season): void
    {
        Log::info($games->isEmpty() ? 'No games found matching criteria' : 'Retrieved games for spread calculation', [
            'count' => $games->count(),
            'week' => $week,
            'season' => $season
        ]);
    }
}