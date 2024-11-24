<?php

namespace App\Services\Nfl\Stats;

use Illuminate\Support\Collection;

class ConsistencyCalculator
{
    /**
     * Calculate consistency metrics from game data
     *
     * @param Collection $games
     * @return array
     */

    private const VOLATILITY_THRESHOLDS = [
        10 => 'Very Stable',
        20 => 'Stable',
        30 => 'Moderate',
        40 => 'Volatile'
    ];
    private const QUARTERS = ['q1', 'q2', 'q3', 'q4'];
    private const SCORING_THRESHOLDS = [
        'high' => [20, 30, 40],
        'low' => [10]
    ];
    private const YARDAGE_THRESHOLDS = [
        'high' => [300, 400],
        'low' => [200]
    ];

    /**
     * Get volatility rating based on coefficient of variation
     *
     * @param float $cv
     * @return string
     */
    private const BASELINE_THRESHOLD = 0.8;
    private const ABOVE_BASELINE_THRESHOLD = 0.9;
    private const BELOW_BASELINE_THRESHOLD = 0.7;

    /**
     * Calculate consistency metrics from game data
     */
    public function calculate(Collection $games): array
    {
        if ($games->isEmpty()) {
            return $this->getEmptyStats();
        }

        // Cache frequently used collections to avoid recalculation
        $totalYards = $games->pluck('total_yards')->filter();
        $points = $games->pluck('points_scored')->filter();

        $metricStats = $this->calculateMetricsStats($games);
        $scoringStats = $this->calculateScoringStats($games, $points);
        $gameControlStats = $this->calculateGameControlStats($games);
        $reliabilityStats = $this->calculateReliabilityStats($games, $totalYards, $metricStats['total_yards']['coefficient_of_variation']);

        return [
            'total_yards' => $metricStats['total_yards'],
            'rushing_yards' => $metricStats['rushing_yards'],
            'passing_yards' => $metricStats['passing_yards'],
            'scoring' => $scoringStats,
            'game_control' => $gameControlStats,
            'performance_reliability' => $reliabilityStats
        ];
    }

    /**
     * Get empty statistics structure
     *
     * @return array
     */
    protected function getEmptyStats(): array
    {
        return [
            'total_yards' => $this->getEmptyMetricStats(),
            'rushing_yards' => $this->getEmptyMetricStats(),
            'passing_yards' => $this->getEmptyMetricStats(),
            'scoring' => [
                'points_per_game' => [
                    'mean' => 0,
                    'std_dev' => 0,
                    'coefficient_of_variation' => 0
                ],
                'scoring_distribution' => [
                    'games_20_plus' => 0,
                    'games_30_plus' => 0,
                    'games_40_plus' => 0,
                    'games_under_10' => 0,
                    'scoreless_quarters' => 0
                ],
                'quarter_consistency' => [
                    'q1' => ['mean' => 0, 'std_dev' => 0, 'coefficient_of_variation' => 0, 'scoreless_games' => 0],
                    'q2' => ['mean' => 0, 'std_dev' => 0, 'coefficient_of_variation' => 0, 'scoreless_games' => 0],
                    'q3' => ['mean' => 0, 'std_dev' => 0, 'coefficient_of_variation' => 0, 'scoreless_games' => 0],
                    'q4' => ['mean' => 0, 'std_dev' => 0, 'coefficient_of_variation' => 0, 'scoreless_games' => 0]
                ]
            ],
            'game_control' => [
                'time_of_possession' => ['mean' => 0, 'std_dev' => 0],
                'lead_percentage' => ['mean' => 0, 'games_leading_half' => 0],
                'drive_success' => ['three_and_out_percentage' => 0, 'scoring_drive_percentage' => 0]
            ],
            'performance_reliability' => [
                'consistency_score' => 0,
                'performance_baseline' => [
                    'games_above_300' => 0,
                    'games_above_400' => 0,
                    'games_below_200' => 0
                ],
                'streak_analysis' => [
                    'current_streak_type' => 'none',
                    'current_streak_length' => 0,
                    'longest_consistent_streak' => 0
                ]
            ]
        ];
    }

    /**
     * Get empty metric statistics structure
     *
     * @return array
     */
    protected function getEmptyMetricStats(): array
    {
        return [
            'mean' => 0,
            'median' => 0,
            'std_dev' => 0,
            'coefficient_of_variation' => 0,
            'range' => [
                'min' => 0,
                'max' => 0,
                'spread' => 0
            ],
            'baseline_performance' => [
                'value' => 0,
                'games_above' => 0,
                'percentage_above' => 0
            ],
            'volatility_rating' => 'Not Available'
        ];
    }

    /**
     * Calculate metrics statistics for yards categories
     */
    private function calculateMetricsStats(Collection $games): array
    {
        $metrics = ['total_yards', 'rushing_yards', 'passing_yards'];
        $stats = [];

        foreach ($metrics as $metric) {
            $values = $games->pluck($metric)->filter()->values();

            if ($values->isEmpty()) {
                $stats[$metric] = $this->getEmptyMetricStats();
                continue;
            }

            $basicStats = $this->calculateBasicStats($values);
            $mean = $basicStats['mean'];

            $stats[$metric] = array_merge($basicStats, [
                'median' => round($values->median(), 1),
                'range' => [
                    'min' => $values->min(),
                    'max' => $values->max(),
                    'spread' => $values->max() - $values->min()
                ],
                'baseline_performance' => $this->calculateBaselinePerformance($values, $mean),
                'volatility_rating' => $this->getVolatilityRating($basicStats['coefficient_of_variation'])
            ]);
        }

        return $stats;
    }

    /**
     * Calculate basic statistical metrics
     */
    private function calculateBasicStats(Collection $values): array
    {
        if ($values->isEmpty()) {
            return [
                'mean' => 0,
                'std_dev' => 0,
                'coefficient_of_variation' => 0
            ];
        }

        $mean = $values->avg();
        if ($mean === null) {
            return [
                'mean' => 0,
                'std_dev' => 0,
                'coefficient_of_variation' => 0
            ];
        }

        $stdDev = $this->calculateStdDev($values, (float)$mean);
        $cv = $mean > 0 ? ($stdDev / $mean) * 100 : 0;

        return [
            'mean' => round($mean, 1),
            'std_dev' => round($stdDev, 1),
            'coefficient_of_variation' => round($cv, 1)
        ];
    }

    /**
     * Calculate standard deviation
     *
     * @param Collection $values
     * @param float $mean
     * @return float
     */
    private function calculateStdDev(Collection $values, float $mean): float
    {
        if ($values->isEmpty()) {
            return 0.0;
        }

        $validValues = $values->filter(fn($value) => is_numeric($value) && !is_null($value)
        );

        if ($validValues->isEmpty()) {
            return 0.0;
        }

        $sumSquaredDiff = $validValues->reduce(function ($carry, $value) use ($mean) {
            $diff = (float)$value - $mean;
            return $carry + ($diff * $diff);
        }, 0.0);

        return sqrt($sumSquaredDiff / $validValues->count());
    }

    /**
     * Calculate baseline performance metrics
     */
    private function calculateBaselinePerformance(Collection $values, float $mean): array
    {
        $baselineValue = $mean * self::BASELINE_THRESHOLD;
        $gamesAbove = $values->filter(fn($value) => $value >= $baselineValue)->count();

        return [
            'value' => round($baselineValue, 1),
            'games_above' => $gamesAbove,
            'percentage_above' => round(($gamesAbove / $values->count()) * 100, 1)
        ];
    }

    /**
     * Get volatility rating based on coefficient of variation
     */
    private function getVolatilityRating(float $cv): string
    {
        foreach (self::VOLATILITY_THRESHOLDS as $threshold => $rating) {
            if ($cv <= $threshold) {
                return $rating;
            }
        }
        return 'Highly Volatile';
    }

    /**
     * Calculate scoring statistics
     */
    private function calculateScoringStats(Collection $games, Collection $points): array
    {
        // Handle empty games collection
        if ($games->isEmpty()) {
            return $this->getEmptyScoringStats();
        }

        $basicStats = $this->calculateBasicStats($points);

        return [
            'points_per_game' => $basicStats,
            'scoring_distribution' => $this->calculateScoringDistribution($points, $games),
            'quarter_consistency' => $this->calculateQuarterConsistency($games),
            'scoring_reliability' => $this->calculateScoringReliability($points)
        ];
    }

    private function getEmptyScoringStats(): array
    {
        return [
            'points_per_game' => [
                'mean' => 0,
                'std_dev' => 0,
                'coefficient_of_variation' => 0
            ],
            'scoring_distribution' => [
                'games_20_plus' => 0,
                'games_30_plus' => 0,
                'games_40_plus' => 0,
                'games_under_10' => 0,
                'scoreless_quarters' => 0
            ],
            'quarter_consistency' => array_fill_keys(self::QUARTERS, [
                'mean' => 0,
                'std_dev' => 0,
                'coefficient_of_variation' => 0,
                'scoreless_games' => 0
            ]),
            'scoring_reliability' => [
                'games_within_10_of_mean' => 0,
                'games_within_7_of_mean' => 0,
                'consistency_percentage' => 0
            ]
        ];
    }

    /**
     * Calculate scoring distribution metrics
     */
    private function calculateScoringDistribution(Collection $points, Collection $games): array
    {
        return [
            'games_20_plus' => $points->filter(fn($p) => $p >= 20)->count(),
            'games_30_plus' => $points->filter(fn($p) => $p >= 30)->count(),
            'games_40_plus' => $points->filter(fn($p) => $p >= 40)->count(),
            'games_under_10' => $points->filter(fn($p) => $p < 10)->count(),
            'scoreless_quarters' => $games->sum('scoreless_quarters')
        ];
    }

    /**
     * Calculate quarter-by-quarter consistency
     *
     * @param Collection $games
     * @return array
     */
    private function calculateQuarterConsistency(Collection $games): array
    {
        $stats = [];

        foreach (self::QUARTERS as $quarter) {
            $points = $games->pluck($quarter . '_points')->filter();

            if ($points->isEmpty()) {
                $stats[$quarter] = [
                    'mean' => 0,
                    'std_dev' => 0,
                    'coefficient_of_variation' => 0,
                    'scoreless_games' => 0
                ];
                continue;
            }

            // Calculate mean and ensure it's not null
            $mean = $points->avg();
            if ($mean === null) {
                $mean = 0;
            }

            // Calculate standard deviation with validated mean
            $stdDev = $this->calculateStdDev($points, (float)$mean);

            $stats[$quarter] = [
                'mean' => round($mean, 1),
                'std_dev' => round($stdDev, 1),
                'coefficient_of_variation' => $mean > 0 ? round(($stdDev / $mean) * 100, 1) : 0,
                'scoreless_games' => $points->filter(fn($p) => $p == 0)->count()
            ];
        }

        return $stats;
    }

    /**
     * Calculate scoring reliability metrics
     *
     * @param Collection $games
     * @return array
     */
    protected function calculateScoringReliability(Collection $games): array
    {
        $points = $games->pluck('points_scored');
        $mean = $points->avg();

        return [
            'games_within_10_of_mean' => $points->filter(fn($p) => abs($p - $mean) <= 10)->count(),
            'games_within_7_of_mean' => $points->filter(fn($p) => abs($p - $mean) <= 7)->count(),
            'consistency_percentage' => $points->count() > 0 ?
                round(($points->filter(fn($p) => abs($p - $mean) <= 10)->count() / $points->count()) * 100, 1) : 0
        ];
    }

    /**
     * Calculate game control statistics
     */
    private function calculateGameControlStats(Collection $games): array
    {
        $totalDrives = $games->sum('total_drives');
        $timeStats = $this->calculateBasicStats($games->pluck('time_of_possession'));

        return [
            'time_of_possession' => [
                'mean' => $timeStats['mean'],
                'std_dev' => $timeStats['std_dev']
            ],
            'lead_percentage' => [
                'mean' => round($games->avg('time_with_lead_percentage'), 1),
                'games_leading_half' => $games->where('time_with_lead_percentage', '>=', 50)->count()
            ],
            'drive_success' => $this->calculateDriveSuccess($games, $totalDrives)
        ];
    }

    /**
     * Calculate drive success metrics
     */
    private function calculateDriveSuccess(Collection $games, int $totalDrives): array
    {
        if ($totalDrives === 0) {
            return [
                'three_and_out_percentage' => 0,
                'scoring_drive_percentage' => 0
            ];
        }

        return [
            'three_and_out_percentage' => round(($games->sum('three_and_outs') / $totalDrives) * 100, 1),
            'scoring_drive_percentage' => round(($games->sum('scoring_drives') / $totalDrives) * 100, 1)
        ];
    }

    private function calculateReliabilityStats(Collection $games, Collection $totalYards, mixed $coefficient_of_variation)
    {
        return [
            'consistency_score' => $this->calculateConsistencyScore($games),
            'performance_baseline' => [
                'games_above_300' => $totalYards->filter(fn($yards) => $yards >= 300)->count(),
                'games_above_400' => $totalYards->filter(fn($yards) => $yards >= 400)->count(),
                'games_below_200' => $totalYards->filter(fn($yards) => $yards < 200)->count()
            ],
            'streak_analysis' => [
                'current_streak_type' => $this->getCurrentStreakType($games),
                'current_streak_length' => $this->getCurrentStreakLength($games),
                'longest_consistent_streak' => $this->getLongestConsistentStreak($games)
            ]
        ];
    }

    /**
     * Calculate overall consistency score
     *
     * @param Collection $games
     * @return float
     */
    protected function calculateConsistencyScore(Collection $games): float
    {
        $yardsCv = $this->calculateMetricConsistency($games, 'total_yards')['coefficient_of_variation'];
        $pointsCv = $this->calculateMetricConsistency($games, 'points_scored')['coefficient_of_variation'];

        $baselinePerformance = $this->calculateMetricConsistency($games, 'total_yards')['baseline_performance']['percentage_above'];

        // Weight different factors
        $score = (
            (100 - $yardsCv) * 0.4 +
            (100 - $pointsCv) * 0.4 +
            $baselinePerformance * 0.2
        );

        return round($score, 1);
    }

    /**
     * Calculate consistency metrics for a specific statistical category
     *
     * @param Collection $games
     * @param string $metric
     * @return array
     */
    protected function calculateMetricConsistency(Collection $games, string $metric): array
    {
        $values = $games->pluck($metric)->filter()->values();

        if ($values->isEmpty()) {
            return $this->getEmptyMetricStats();
        }

        $mean = $values->avg();
        $median = $values->median();
        $stdDev = $this->calculateStdDev($values, $mean);
        $cv = $mean > 0 ? ($stdDev / $mean) * 100 : 0;

        return [
            'mean' => round($mean, 1),
            'median' => round($median, 1),
            'std_dev' => round($stdDev, 1),
            'coefficient_of_variation' => round($cv, 1),
            'range' => [
                'min' => $values->min(),
                'max' => $values->max(),
                'spread' => $values->max() - $values->min()
            ],
            'baseline_performance' => [
                'value' => round($mean * 0.8, 1),
                'games_above' => $values->filter(fn($value) => $value >= $mean * 0.8)->count(),
                'percentage_above' => round(($values->filter(fn($value) => $value >= $mean * 0.8)->count() / $values->count()) * 100, 1)
            ],
            'volatility_rating' => $this->getVolatilityRating($cv)
        ];
    }

    /**
     * Get current streak type
     *
     * @param Collection $games
     * @return string
     */
    protected function getCurrentStreakType(Collection $games): string
    {
        if ($games->isEmpty()) {
            return 'none';
        }

        $lastGame = $games->first();
        $meanYards = $games->pluck('total_yards')->avg();

        if ($lastGame->total_yards >= $meanYards * 0.9) {
            return 'above_baseline';
        } elseif ($lastGame->total_yards <= $meanYards * 0.7) {
            return 'below_baseline';
        }

        return 'average';
    }

    /**
     * Get current streak length
     *
     * @param Collection $games
     * @return int
     */
    protected function getCurrentStreakLength(Collection $games): int
    {
        if ($games->isEmpty()) {
            return 0;
        }

        $streakType = $this->getCurrentStreakType($games);
        $meanYards = $games->pluck('total_yards')->avg();
        $streakLength = 0;

        foreach ($games as $game) {
            if ($streakType === 'above_baseline' && $game->total_yards >= $meanYards * 0.9) {
                $streakLength++;
            } elseif ($streakType === 'below_baseline' && $game->total_yards <= $meanYards * 0.7) {
                $streakLength++;
            } else {
                break;
            }
        }

        return $streakLength;
    }

    /**
     * Get longest consistent streak
     *
     * @param Collection $games
     * @return int
     */
    protected function getLongestConsistentStreak(Collection $games): int
    {
        if ($games->isEmpty()) {
            return 0;
        }

        $meanYards = $games->pluck('total_yards')->avg();
        $currentStreak = 0;
        $longestStreak = 0;

        foreach ($games as $game) {
            if ($game->total_yards >= $meanYards * 0.8) {
                $currentStreak++;
                $longestStreak = max($longestStreak, $currentStreak);
            } else {
                $currentStreak = 0;
            }
        }

        return $longestStreak;
    }


}