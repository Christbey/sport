<?php

namespace App\Services;

class EnhancedFootballAnalytics
{
    /**
     * Calculate advanced efficiency metrics
     */
    public function calculateEfficiencyMetrics(array $homeStats, array $awayStats): array
    {
        return [
            'offensive_efficiency' => [
                'home' => $this->calculateOffensiveEfficiency($homeStats),
                'away' => $this->calculateOffensiveEfficiency($awayStats),
                'differential' => round($this->calculateOffensiveEfficiency($homeStats) -
                    $this->calculateOffensiveEfficiency($awayStats), 3)
            ],
            'defensive_efficiency' => [
                'home' => $this->calculateDefensiveEfficiency($homeStats),
                'away' => $this->calculateDefensiveEfficiency($awayStats),
                'differential' => round($this->calculateDefensiveEfficiency($homeStats) -
                    $this->calculateDefensiveEfficiency($awayStats), 3)
            ]
        ];
    }

    private function calculateOffensiveEfficiency(array $stats): float
    {
        return round(
            ($stats['offense_success_rate'] * 0.4) +
            ($stats['offense_explosiveness'] * 0.3) +
            ($stats['offense_ppa'] * 0.3),
            3
        );
    }

    private function calculateDefensiveEfficiency(array $stats): float
    {
        return round(
            ((1 - $stats['defense_success_rate']) * 0.4) +
            ((1 - $stats['defense_explosiveness']) * 0.3) +
            ((1 - $stats['defense_ppa']) * 0.3),
            3
        );
    }

    /**
     * Calculate advanced matchup analysis
     */
    public function calculateMatchupAdvantages(array $homeStats, array $awayStats): array
    {
        return [
            'rushing_advantage' => [
                'offense' => $this->calculateRushingAdvantage($homeStats, $awayStats),
                'defense' => $this->calculateRushingDefenseAdvantage($homeStats, $awayStats)
            ],
            'passing_advantage' => [
                'offense' => $this->calculatePassingAdvantage($homeStats, $awayStats),
                'defense' => $this->calculatePassingDefenseAdvantage($homeStats, $awayStats)
            ],
            'situational_advantages' => $this->calculateSituationalAdvantages($homeStats, $awayStats),
            'line_play' => $this->calculateLinePlayMetrics($homeStats, $awayStats)
        ];
    }

    private function calculateRushingAdvantage(array $homeStats, array $awayStats): float
    {
        return round(
            ($homeStats['offense_rushing_success_rate'] - $awayStats['defense_rushing_success_rate']) +
            ($homeStats['offense_rushing_explosiveness'] - $awayStats['defense_rushing_explosiveness']) +
            ($homeStats['offense_rushing_ppa'] - $awayStats['defense_rushing_ppa']),
            3
        );
    }

    private function calculateRushingDefenseAdvantage(array $homeStats, array $awayStats)
    {
        return round(
            ($homeStats['defense_rushing_success_rate'] - $awayStats['offense_rushing_success_rate']) +
            ($homeStats['defense_rushing_explosiveness'] - $awayStats['offense_rushing_explosiveness']) +
            ($homeStats['defense_rushing_ppa'] - $awayStats['offense_rushing_ppa']),
            3
        );
    }

    private function calculatePassingAdvantage(array $homeStats, array $awayStats): float
    {
        return round(
            ($homeStats['offense_passing_success_rate'] - $awayStats['defense_passing_success_rate']) +
            ($homeStats['offense_passing_explosiveness'] - $awayStats['defense_passing_explosiveness']) +
            ($homeStats['offense_passing_ppa'] - $awayStats['defense_passing_ppa']),
            3
        );
    }

    private function calculatePassingDefenseAdvantage(array $homeStats, array $awayStats)
    {
        return round(
            ($homeStats['defense_passing_success_rate'] - $awayStats['offense_passing_success_rate']) +
            ($homeStats['defense_passing_explosiveness'] - $awayStats['offense_passing_explosiveness']) +
            ($homeStats['defense_passing_ppa'] - $awayStats['offense_passing_ppa']),
            3
        );
    }

    private function calculateSituationalAdvantages(array $homeStats, array $awayStats): array
    {
        return [
            'standard_downs' => round(
                $homeStats['offense_standard_downs_success_rate'] -
                $awayStats['defense_standard_downs_success_rate'],
                3
            ),
            'passing_downs' => round(
                $homeStats['offense_passing_downs_success_rate'] -
                $awayStats['defense_passing_downs_success_rate'],
                3
            )
        ];
    }

    private function calculateLinePlayMetrics(array $homeStats, array $awayStats): array
    {
        return [
            'offensive_line' => round(
                ($homeStats['offense_line_yards'] * 0.4) +
                ($homeStats['offense_power_success'] * 0.3) +
                ((1 - $homeStats['offense_stuff_rate']) * 0.3),
                3
            ),
            'defensive_line' => round(
                ($homeStats['defense_line_yards'] * 0.4) +
                ($homeStats['defense_power_success'] * 0.3) +
                ($homeStats['defense_stuff_rate'] * 0.3),
                3
            )
        ];
    }

    /**
     * Calculate predicted scoring ranges
     */
    public function calculateScoringPrediction(array $homeStats, array $awayStats): array
    {
        $homePredicted = $this->predictScore($homeStats, $awayStats['defense_ppa'],
            $homeStats['offense_success_rate'],
            $awayStats['defense_success_rate']);

        $awayPredicted = $this->predictScore($awayStats, $homeStats['defense_ppa'],
            $awayStats['offense_success_rate'],
            $homeStats['defense_success_rate']);

        return [
            'home_predicted_range' => [
                'low' => round($homePredicted * 0.85),
                'high' => round($homePredicted * 1.15)
            ],
            'away_predicted_range' => [
                'low' => round($awayPredicted * 0.85),
                'high' => round($awayPredicted * 1.15)
            ]
        ];
    }

    private function predictScore(array $offenseStats, float $opposingDefensePpa,
                                  float $offenseSuccessRate, float $defenseSuccessRate): float
    {
        $baseScore = $offenseStats['offense_ppa'] * $offenseStats['offense_plays'] *
            ($offenseSuccessRate / $defenseSuccessRate);
        return round($baseScore * (1 - $opposingDefensePpa), 1);
    }

    /**
     * Calculate drive success likelihood
     */
    public function calculateDriveMetrics(array $homeStats, array $awayStats): array
    {
        return [
            'scoring_drive_probability' => [
                'home' => $this->calculateScoringDriveProbability($homeStats, $awayStats),
                'away' => $this->calculateScoringDriveProbability($awayStats, $homeStats)
            ],
            'explosive_drive_probability' => [
                'home' => $this->calculateExplosiveDriveProbability($homeStats, $awayStats),
                'away' => $this->calculateExplosiveDriveProbability($awayStats, $homeStats)
            ],
            'red_zone_efficiency' => [
                'home' => $this->calculateRedZoneEfficiency($homeStats),
                'away' => $this->calculateRedZoneEfficiency($awayStats)
            ]
        ];
    }

    private function calculateScoringDriveProbability(array $offenseStats, array $defenseStats): float
    {
        return round(
            ($offenseStats['offense_success_rate'] * 0.4) +
            ($offenseStats['offense_ppa'] * 0.3) +
            ((1 - $defenseStats['defense_success_rate']) * 0.3),
            3
        );
    }

    private function calculateExplosiveDriveProbability(array $offenseStats, array $defenseStats): float
    {
        return round(
            ($offenseStats['offense_explosiveness'] * 0.5) +
            ($offenseStats['offense_success_rate'] * 0.3) +
            ((1 - $defenseStats['defense_explosiveness']) * 0.2),
            3
        );
    }

    private function calculateRedZoneEfficiency(array $stats): float
    {
        return round(
            ($stats['offense_power_success'] * 0.4) +
            ($stats['offense_success_rate'] * 0.3) +
            ($stats['offense_ppa'] * 0.3),
            3
        );
    }
}