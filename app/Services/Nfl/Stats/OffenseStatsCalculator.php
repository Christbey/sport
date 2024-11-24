<?php

namespace App\Services\Nfl\Stats;

use Illuminate\Support\Collection;

class OffenseStatsCalculator
{
    /**
     * Calculate offensive statistics from game data
     *
     * @param Collection $games
     * @return array
     */
    public function calculate(Collection $games): array
    {
        if ($games->isEmpty()) {
            return $this->getEmptyStats();
        }

        return [
            'yards_per_game' => $this->calculateYardsPerGame($games),
            'efficiency' => $this->calculateEfficiency($games),
            'play_distribution' => $this->calculatePlayDistribution($games),
            'scoring' => $this->calculateScoring($games),
            'drive_metrics' => $this->calculateDriveMetrics($games),
            'explosiveness' => $this->calculateExplosiveness($games)
        ];
    }

    /**
     * Get empty stats structure
     *
     * @return array
     */
    protected function getEmptyStats(): array
    {
        return [
            'yards_per_game' => [
                'total' => 0,
                'rushing' => 0,
                'passing' => 0,
                'first_downs' => 0,
                'red_zone' => 0
            ],
            'efficiency' => [
                'yards_per_play' => 0,
                'rushing_yards_per_attempt' => 0,
                'passing_yards_per_attempt' => 0,
                'first_down_rate' => 0,
                'third_down_rate' => 0,
                'red_zone_rate' => 0
            ],
            'play_distribution' => [
                'rushing_percentage' => 0,
                'passing_percentage' => 0
            ],
            'scoring' => [
                'points_per_game' => 0,
                'touchdowns_per_game' => 0,
                'field_goals_per_game' => 0,
                'points_per_drive' => 0,
                'red_zone_touchdown_rate' => 0
            ],
            'drive_metrics' => [
                'avg_drive_time' => 0,
                'avg_plays_per_drive' => 0,
                'avg_yards_per_drive' => 0,
                'three_and_out_rate' => 0,
                'turnover_rate' => 0
            ],
            'explosiveness' => [
                'plays_20_plus_yards' => 0,
                'plays_40_plus_yards' => 0,
                'explosive_play_rate' => 0,
                'avg_yards_per_completion' => 0,
                'big_play_touchdown_rate' => 0
            ]
        ];
    }

    /**
     * Calculate yards per game metrics
     *
     * @param Collection $games
     * @return array
     */
    protected function calculateYardsPerGame(Collection $games): array
    {
        return [
            'total' => round($games->avg('total_yards'), 1),
            'rushing' => round($games->avg('rushing_yards'), 1),
            'passing' => round($games->avg('passing_yards'), 1),
            'first_downs' => round($games->avg('first_downs'), 1),
            'red_zone' => round($games->avg('red_zone_yards'), 1)
        ];
    }

    /**
     * Calculate efficiency metrics
     *
     * @param Collection $games
     * @return array
     */
    protected function calculateEfficiency(Collection $games): array
    {
        $totalPlays = $games->sum('rushing_attempts') + $games->sum('passing_attempts');
        $totalYards = $games->sum('total_yards');

        return [
            'yards_per_play' => $totalPlays > 0 ?
                round($totalYards / $totalPlays, 2) : 0,

            'rushing_yards_per_attempt' => $games->sum('rushing_attempts') > 0 ?
                round($games->sum('rushing_yards') / $games->sum('rushing_attempts'), 2) : 0,

            'passing_yards_per_attempt' => $games->sum('passing_attempts') > 0 ?
                round($games->sum('passing_yards') / $games->sum('passing_attempts'), 2) : 0,

            'first_down_rate' => $totalPlays > 0 ?
                round(($games->sum('first_downs') / $totalPlays) * 100, 1) : 0,

            'third_down_rate' => $games->sum('third_down_attempts') > 0 ?
                round(($games->sum('third_down_conversions') / $games->sum('third_down_attempts')) * 100, 1) : 0,

            'red_zone_rate' => $games->sum('red_zone_attempts') > 0 ?
                round(($games->sum('red_zone_scores') / $games->sum('red_zone_attempts')) * 100, 1) : 0
        ];
    }

    /**
     * Calculate play distribution percentages
     *
     * @param Collection $games
     * @return array
     */
    protected function calculatePlayDistribution(Collection $games): array
    {
        $totalPlays = $games->sum('rushing_attempts') + $games->sum('passing_attempts');

        if ($totalPlays === 0) {
            return ['rushing_percentage' => 0, 'passing_percentage' => 0];
        }

        $rushingPercentage = ($games->sum('rushing_attempts') / $totalPlays) * 100;

        return [
            'rushing_percentage' => round($rushingPercentage, 1),
            'passing_percentage' => round(100 - $rushingPercentage, 1)
        ];
    }

    /**
     * Calculate scoring metrics
     *
     * @param Collection $games
     * @return array
     */
    protected function calculateScoring(Collection $games): array
    {
        return [
            'points_per_game' => round($games->avg('points_scored'), 1),
            'touchdowns_per_game' => round($games->avg('touchdowns'), 1),
            'field_goals_per_game' => round($games->avg('field_goals'), 1),
            'points_per_drive' => $games->sum('total_drives') > 0 ?
                round($games->sum('points_scored') / $games->sum('total_drives'), 2) : 0,
            'red_zone_touchdown_rate' => $games->sum('red_zone_attempts') > 0 ?
                round(($games->sum('red_zone_touchdowns') / $games->sum('red_zone_attempts')) * 100, 1) : 0
        ];
    }

    /**
     * Calculate drive-related metrics
     *
     * @param Collection $games
     * @return array
     */
    protected function calculateDriveMetrics(Collection $games): array
    {
        return [
            'avg_drive_time' => round($games->avg('average_drive_time'), 1),
            'avg_plays_per_drive' => round($games->avg('plays_per_drive'), 1),
            'avg_yards_per_drive' => round($games->avg('yards_per_drive'), 1),
            'three_and_out_rate' => $games->sum('total_drives') > 0 ?
                round(($games->sum('three_and_outs') / $games->sum('total_drives')) * 100, 1) : 0,
            'turnover_rate' => $games->sum('total_drives') > 0 ?
                round(($games->sum('turnovers') / $games->sum('total_drives')) * 100, 1) : 0
        ];
    }

    /**
     * Calculate explosiveness metrics
     *
     * @param Collection $games
     * @return array
     */
    protected function calculateExplosiveness(Collection $games): array
    {
        $totalPlays = $games->sum('rushing_attempts') + $games->sum('passing_attempts');

        return [
            'plays_20_plus_yards' => round($games->avg('plays_20_plus_yards'), 1),
            'plays_40_plus_yards' => round($games->avg('plays_40_plus_yards'), 1),
            'explosive_play_rate' => $totalPlays > 0 ?
                round(($games->sum('plays_20_plus_yards') / $totalPlays) * 100, 1) : 0,
            'avg_yards_per_completion' => $games->sum('completions') > 0 ?
                round($games->sum('passing_yards') / $games->sum('completions'), 1) : 0,
            'big_play_touchdown_rate' => $games->sum('touchdowns') > 0 ?
                round(($games->sum('touchdowns_20_plus_yards') / $games->sum('touchdowns')) * 100, 1) : 0
        ];
    }

}