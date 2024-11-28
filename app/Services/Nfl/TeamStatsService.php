<?php

namespace App\Services\Nfl;

use App\Repositories\Nfl\TeamStatsRepository;
use App\Services\Nfl\Stats\{ConsistencyCalculator, DefenseStatsCalculator, OffenseStatsCalculator};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TeamStatsService
{
    // Cache Configuration
    public const CACHE_DURATION = 60; // minutes

    // Games Analysis Configuration


    public const GAMES_BACK_OPTIONS = [
        'LAST_3' => 3,
        'LAST_5' => 5,
        'LAST_10' => 10,
        'LAST_15' => 15,  // Added this option
        'LAST_16' => 16,
        'FULL_SEASON' => 17,
        'DEFAULT' => 10
    ];

    public const RANGE_TYPES = [
        'CUSTOM' => 'custom',
        'GAMES_BACK' => 'games_back',
        'SEASON' => 'season',
        'ALL' => 'all',
        'LAST_15' => 'last_15',
    ];

    // Trend Analysis Thresholds
    public const TREND_THRESHOLDS = [
        'STRONG_UPWARD' => 5.0,
        'MODERATE_UPWARD' => 2.0,
        'SLIGHT_UPWARD' => 0.0,
        'STRONG_DOWNWARD' => -5.0,
        'MODERATE_DOWNWARD' => -2.0,
        'SLIGHT_DOWNWARD' => 0.0
    ];

    // Momentum Analysis Thresholds
    public const MOMENTUM_THRESHOLDS = [
        'STRONG_POSITIVE' => 15.0,
        'POSITIVE' => 5.0,
        'STRONG_NEGATIVE' => -15.0,
        'NEGATIVE' => -5.0
    ];

    // Moving Average Windows
    public const MOVING_AVERAGES = [
        'THREE_GAME' => 3,
        'FIVE_GAME' => 5
    ];

    // Statistical Analysis Configuration
    public const STATS_CONFIG = [
        'DECIMAL_PLACES' => [
            'PERCENTAGE' => 1,
            'YARDS' => 1,
            'EFFICIENCY' => 2,
            'TREND' => 3
        ],
        'METRICS' => [
            'TOTAL_YARDS',
            'RUSHING_YARDS',
            'PASSING_YARDS'
        ]
    ];

    // Analytics Display Fields
    public const ANALYTICS_FIELDS = [
        'Team',
        'Games',
        'Pass YPG',
        'Yards/Play',
        'Rush Y/A',
        'Pass Y/A',
        'Rush %',
        'Pass %',
        'Total CV',
        'Rush CV',
        'Pass CV',
        'Total Trend',
        'Rush Trend',
        'Pass Trend',
        'Rating',
        'SOS'
    ];

    // Dynamic Configuration Properties
    protected int $currentGamesBack;
    protected array $trendThresholds;
    protected array $momentumThresholds;
    protected array $movingAverageWindows;
    protected array $decimalPlaces;
    protected bool $includeDefensiveStats = true;
    protected bool $includeConsistencyMetrics = true;
    protected string $cachePrefix = 'team_analytics_';

    // Service Dependencies
    protected TeamStatsRepository $repository;
    protected array $calculators;

    public function __construct(
        TeamStatsRepository    $repository,
        OffenseStatsCalculator $offenseCalc,
        DefenseStatsCalculator $defenseCalc,
        ConsistencyCalculator  $consistencyCalc
    )
    {
        $this->repository = $repository;
        $this->calculators = [
            'offense' => $offenseCalc,
            'defense' => $defenseCalc,
            'consistency' => $consistencyCalc
        ];

        // Initialize with default values
        $this->initializeDefaults();
    }

    protected function initializeDefaults(): void
    {
        $this->currentGamesBack = self::GAMES_BACK_OPTIONS['DEFAULT'];
        $this->trendThresholds = self::TREND_THRESHOLDS;
        $this->momentumThresholds = self::MOMENTUM_THRESHOLDS;
        $this->movingAverageWindows = self::MOVING_AVERAGES;
        $this->decimalPlaces = self::STATS_CONFIG['DECIMAL_PLACES'];
    }

    // Configuration Setters
    public function setGamesBack(int $games): self
    {
        $this->currentGamesBack = min(max($games, 1), self::GAMES_BACK_OPTIONS['FULL_SEASON']);
        return $this;
    }

    public function setTrendThresholds(array $thresholds): self
    {
        $this->trendThresholds = array_merge(self::TREND_THRESHOLDS, $thresholds);
        return $this;
    }

    public function setMomentumThresholds(array $thresholds): self
    {
        $this->momentumThresholds = array_merge(self::MOMENTUM_THRESHOLDS, $thresholds);
        return $this;
    }

    public function setMovingAverageWindows(array $windows): self
    {
        $this->movingAverageWindows = array_merge(self::MOVING_AVERAGES, $windows);
        return $this;
    }

    public function setDecimalPlaces(array $places): self
    {
        $this->decimalPlaces = array_merge(self::STATS_CONFIG['DECIMAL_PLACES'], $places);
        return $this;
    }

    public function toggleDefensiveStats(bool $include): self
    {
        $this->includeDefensiveStats = $include;
        return $this;
    }

    public function toggleConsistencyMetrics(bool $include): self
    {
        $this->includeConsistencyMetrics = $include;
        return $this;
    }

    public function setCachePrefix(string $prefix): self
    {
        $this->cachePrefix = $prefix;
        return $this;
    }

    // Configuration Getters

    public function getStatsByType(string $type, ?string $teamFilter = null, array $range = []): array
    {
        // Process the range before using it
        $range = $this->processRange($range);

        return match ($type) {
            'average_points' => $this->repository->getAveragePoints($teamFilter, $range),
            'quarter_scoring' => $this->repository->getQuarterScoring($teamFilter, $range),
            'half_scoring' => $this->repository->getHalfScoring($teamFilter, $range),
            'score_margins' => $this->repository->getScoreMargins($teamFilter),
            'quarter_comebacks' => $this->repository->getQuarterComebacks($teamFilter),
            'scoring_streaks' => $this->repository->getScoringStreaks($teamFilter),
            'bestReceivers' => $this->repository->getBestReceivers($teamFilter),
            'bestRushers' => $this->repository->getBestRushers($teamFilter),
            'bestTacklers' => $this->repository->getBestTacklers($teamFilter),
            'big_playmakers' => $this->repository->getBigPlaymakers($teamFilter),
            'defensive_playmakers' => $this->repository->getDefensivePlaymakers($teamFilter),
            'dual_threat' => $this->repository->getDualThreatPlayers($teamFilter),
            'offensive_consistency' => $this->repository->getOffensiveConsistency($teamFilter),
            'nfl_team_stats' => $this->repository->getNflTeamStats($teamFilter),
            'team_analytics' => $this->getTeamAnalytics($teamFilter),
            'over_under_analysis' => $this->repository->getOverUnderAnalysis($teamFilter),
            'team_matchup_edge' => $this->repository->getTeamMatchupEdge($teamFilter),
            'first_half_trends' => $this->repository->getFirstHalfTendencies($teamFilter),
            'team_vs_conference' => $this->repository->getTeamVsConference($teamFilter),
            'team_vs_division' => $this->repository->getTeamVsDivision($teamFilter),
            'player_vs_conference' => $this->repository->getPlayerVsConference($teamFilter),
            'player_vs_division' => $this->repository->getPlayerVsDivision($teamFilter),
            'conference_stats' => $this->repository->getConferenceStats($teamFilter),
            'division_stats' => $this->repository->getDivisionStats($teamFilter),
            default => ['data' => [], 'headings' => []]
        };
    }

    protected function processRange(array $range): array
    {
        // Check if games_back is set
        if (isset($range['games_back']) && is_numeric($range['games_back'])) {
            return [
                'type' => 'games_back',
                'limit' => (int)$range['games_back']
            ];

            
        }

        // Process other range types
        $rangeType = $range['range_type'] ?? self::RANGE_TYPES['ALL'];

        return match ($rangeType) {
            self::RANGE_TYPES['CUSTOM'] => [
                'type' => 'custom',
                'start_date' => $range['start_date'] ?? null,
                'end_date' => $range['end_date'] ?? null,
            ],
            self::RANGE_TYPES['GAMES_BACK'] => [
                'type' => 'games_back',
                'limit' => $this->validateGamesBack($range['limit'] ?? self::GAMES_BACK_OPTIONS['DEFAULT']),
            ],
            self::RANGE_TYPES['SEASON'] => [
                'type' => 'season',
                'season' => $range['season'] ?? date('Y'),
            ],
            default => [
                'type' => 'all',
            ]
        };
    }

    protected function validateGamesBack(int|string $games): int
    {
        return min(
            max((int)$games, 1),
            self::GAMES_BACK_OPTIONS[]
        );
    }

    public function getTeamAnalytics(?string $teamFilter = null): array
    {
        $cacheKey = $this->cachePrefix . ($teamFilter ?? 'all') . '_' . $this->currentGamesBack;

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($teamFilter) {
            return $teamFilter ?
                $this->getSingleTeamAnalytics($teamFilter) :
                $this->getAllTeamsAnalytics();
        });

        return [
            'data' => $data,
            'headings' => self::ANALYTICS_FIELDS,
            'config' => $this->getCurrentConfig()
        ];
    }

    protected function getSingleTeamAnalytics(string $teamFilter): array
    {
        $teamId = DB::table('nfl_teams')
            ->where('team_abv', $teamFilter)
            ->value('id');

        if (!$teamId) {
            return [];
        }

        $teamStats = $this->calculateTeamAnalytics($teamId);
        return [$this->formatTeamStats($teamStats)];
    }


    public function calculateTeamAnalytics(int $teamId): array
    {
        $games = $this->repository->getRecentGames($teamId, $this->currentGamesBack);

        if ($games->isEmpty()) {
            return $this->getEmptyAnalytics();
        }

        $stats = [
            'team_abv' => $games->first()->team_abv,
            'sample_size' => $games->count(),
            'offensive_stats' => $this->calculators['offense']->calculate($games),
        ];

        if ($this->includeDefensiveStats) {
            $stats['defensive_stats'] = $this->calculators['defense']->calculate($games);
        }

        if ($this->includeConsistencyMetrics) {
            $stats['consistency_metrics'] = $this->calculators['consistency']->calculate($games);
        }

        $stats['performance_trends'] = $this->calculateTrends($games);
        $stats['situation_analysis'] = $this->analyzeSituationalPerformance($teamId);
        $stats['opponent_adjusted'] = $this->calculateOpponentAdjustedStats($teamId);

        return $stats;
    }

    protected function getEmptyAnalytics(): array
    {
        return [
            'sample_size' => 0,
            'offensive_stats' => [
                'yards_per_game' => ['total' => 0, 'rushing' => 0, 'passing' => 0],
                'efficiency' => ['yards_per_play' => 0, 'rushing_yards_per_attempt' => 0, 'passing_yards_per_attempt' => 0],
                'play_distribution' => ['rushing_percentage' => 0, 'passing_percentage' => 0],
            ],
            'consistency_metrics' => [
                'total_yards' => ['mean' => 0, 'median' => 0, 'std_dev' => 0, 'coefficient_of_variation' => 0],
                'rushing_yards' => ['mean' => 0, 'median' => 0, 'std_dev' => 0, 'coefficient_of_variation' => 0],
                'passing_yards' => ['mean' => 0, 'median' => 0, 'std_dev' => 0, 'coefficient_of_variation' => 0],
            ],
            'performance_trends' => [
                'total_yards' => ['trend' => 'insufficient_data'],
                'rushing_yards' => ['trend' => 'insufficient_data'],
                'passing_yards' => ['trend' => 'insufficient_data']
            ],
            'situation_analysis' => [
                'home_performance' => ['rating' => 'insufficient_data'],
                'away_performance' => ['rating' => 'insufficient_data']
            ],
            'opponent_adjusted' => [
                'strength_of_schedule' => ['difficulty' => 'insufficient_data']
            ]
        ];
    }

    protected function calculateTrends(Collection $games): array
    {
        if ($games->count() < 2) {
            return ['trend' => 'insufficient_data'];
        }

        $trends = [];
        foreach (self::STATS_CONFIG['METRICS'] as $metric) {
            $values = $games->pluck(strtolower($metric))->toArray();
            $trends[strtolower($metric)] = [
                'moving_averages' => $this->calculateMovingAverages($values),
                'regression' => $this->calculateLinearRegression($values),
                'momentum' => $this->calculateMomentum($values)
            ];
        }

        return $trends;
    }

    protected function calculateMovingAverages(array $values): array
    {
        $windows = $this->movingAverageWindows;
        $averages = [];

        foreach ($windows as $window) {
            if (count($values) >= $window) {
                $averages["{$window}_game"] = round(
                    array_sum(array_slice($values, 0, $window)) / $window,
                    $this->decimalPlaces['YARDS']
                );
            } else {
                $averages["{$window}_game"] = null;
            }
        }

        return $averages;
    }

    protected function calculateLinearRegression(array $values): array
    {
        $n = count($values);
        $x = range(1, $n);

        $sumX = array_sum($x);
        $sumY = array_sum($values);
        $sumXY = array_sum(array_map(function ($xi, $yi) {
            return $xi * $yi;
        }, $x, $values));
        $sumX2 = array_sum(array_map(fn($xi) => $xi * $xi, $x));

        $slope = (($n * $sumXY) - ($sumX * $sumY)) / (($n * $sumX2) - ($sumX * $sumX));

        return [
            'slope' => round($slope, $this->decimalPlaces['TREND']),
            'trend_direction' => $this->getTrendDirection($slope)
        ];
    }

    protected function getTrendDirection(float $slope): string
    {
        $thresholds = $this->trendThresholds;

        return match (true) {
            $slope > $thresholds['STRONG_UPWARD'] => 'strong_upward',
            $slope > $thresholds['MODERATE_UPWARD'] => 'moderate_upward',
            $slope > $thresholds['SLIGHT_UPWARD'] => 'slight_upward',
            $slope < $thresholds['STRONG_DOWNWARD'] => 'strong_downward',
            $slope < $thresholds['MODERATE_DOWNWARD'] => 'moderate_downward',
            $slope < $thresholds['SLIGHT_DOWNWARD'] => 'slight_downward',
            default => 'stable'
        };
    }

    protected function calculateMomentum(array $values): string
    {
        if (count($values) < 2) {
            return 'insufficient_data';
        }

        $recentAvg = array_sum(array_slice($values, 0, 2)) / 2;
        $previousAvg = array_sum(array_slice($values, -2)) / 2;
        $percentChange = (($recentAvg - $previousAvg) / $previousAvg) * 100;

        $thresholds = $this->momentumThresholds;

        return match (true) {
            $percentChange > $thresholds['STRONG_POSITIVE'] => 'strong_positive',
            $percentChange > $thresholds['POSITIVE'] => 'positive',
            $percentChange < $thresholds['STRONG_NEGATIVE'] => 'strong_negative',
            $percentChange < $thresholds['NEGATIVE'] => 'negative',
            default => 'neutral'
        };
    }

    protected function analyzeSituationalPerformance(int $teamId): array
    {
        return $this->repository->getSituationalPerformance($teamId, $this->currentGamesBack);
    }

    protected function calculateOpponentAdjustedStats(int $teamId): array
    {
        return $this->repository->getOpponentAdjustedStats($teamId, $this->currentGamesBack);
    }

    protected function formatTeamStats(array $stats): array
    {
        $dp = $this->decimalPlaces;

        return [
            'Team' => $stats['team_abv'] ?? 'N/A',
            'Games' => $stats['sample_size'],
            'Pass YPG' => number_format($stats['offensive_stats']['yards_per_game']['passing'], $dp['YARDS']),
            'Yards/Play' => number_format($stats['offensive_stats']['efficiency']['yards_per_play'], $dp['EFFICIENCY']),
            'Rush Y/A' => number_format($stats['offensive_stats']['efficiency']['rushing_yards_per_attempt'], $dp['EFFICIENCY']),
            'Pass Y/A' => number_format($stats['offensive_stats']['efficiency']['passing_yards_per_attempt'], $dp['EFFICIENCY']),
            'Rush %' => number_format($stats['offensive_stats']['play_distribution']['rushing_percentage'], $dp['PERCENTAGE']) . '%',
            'Pass %' => number_format($stats['offensive_stats']['play_distribution']['passing_percentage'], $dp['PERCENTAGE']) . '%',
            'Total CV' => number_format($stats['consistency_metrics']['total_yards']['coefficient_of_variation'], $dp['PERCENTAGE']) . '%',
            'Rush CV' => number_format($stats['consistency_metrics']['rushing_yards']['coefficient_of_variation'], $dp['PERCENTAGE']) . '%',
            'Pass CV' => number_format($stats['consistency_metrics']['passing_yards']['coefficient_of_variation'], $dp['PERCENTAGE']) . '%',
            'Total Trend' => $stats['performance_trends']['total_yards']['regression']['trend_direction'] ?? 'N/A',
            'Rush Trend' => $stats['performance_trends']['rushing_yards']['regression']['trend_direction'] ?? 'N/A',
            'Pass Trend' => $stats['performance_trends']['passing_yards']['regression']['trend_direction'] ?? 'N/A',
            'Rating' => $stats['situation_analysis']['home_performance']['performance_rating'] ?? 'Developing',
            'SOS' => $stats['opponent_adjusted']['strength_of_schedule']['difficulty'] ?? 'N/A'
        ];
    }

    protected function getAllTeamsAnalytics(): array
    {
        return DB::table('nfl_team_stats')
            ->select('team_id', 'team_abv')
            ->distinct('team_id')
            ->whereNotNull('team_id')
            ->get()
            ->map(function ($team) {
                $teamStats = $this->calculateTeamAnalytics($team->team_id);
                return $teamStats['sample_size'] > 0 ?
                    $this->formatTeamStats($teamStats) :
                    null;
            })
            ->filter()
            ->values()
            ->toArray();
    }

    public function getCurrentConfig(): array
    {
        return [
            'games_back' => $this->currentGamesBack,
            'trend_thresholds' => $this->trendThresholds,
            'momentum_thresholds' => $this->momentumThresholds,
            'moving_average_windows' => $this->movingAverageWindows,
            'decimal_places' => $this->decimalPlaces,
            'include_defensive_stats' => $this->includeDefensiveStats,
            'include_consistency_metrics' => $this->includeConsistencyMetrics,
            'cache_prefix' => $this->cachePrefix
        ];
    }

    protected function validateThresholds(array $thresholds, string $type): void
    {
        $required = match ($type) {
            'trend' => array_keys(self::TREND_THRESHOLDS),
            'momentum' => array_keys(self::MOMENTUM_THRESHOLDS),
            default => []
        };

        foreach ($required as $key) {
            if (!isset($thresholds[$key])) {
                throw new InvalidArgumentException("Missing required threshold: {$key}");
            }
        }
    }

    protected function applyRangeToQuery($query, array $range): void
    {
        match ($range['type'] ?? 'all') {
            'custom' => $query->when(isset($range['start_date']), fn($q) => $q->whereDate('s.game_date', '>=', $range['start_date']))
                ->when(isset($range['end_date']), fn($q) => $q->whereDate('s.game_date', '<=', $range['end_date'])),

            'season' => $query->when(isset($range['season']), fn($q) => $q->whereYear('s.game_date', $range['season'])),

            'games_back' => $query->when(isset($range['limit']), fn($q) => $q->orderBy('s.game_date', 'desc')
                ->limit($range['limit'])),

            default => null
        };
    }

}
