<?php

namespace App\Services;

class EnhancedFootballAnalytics
{
    public function calculateEfficiencyMetrics(array $homeStats, array $awayStats): array
    {
        return [
            'offensive_efficiency' => [
                'home' => $this->calculateOffensiveEfficiency($homeStats),
                'away' => $this->calculateOffensiveEfficiency($awayStats)
            ],
            'defensive_efficiency' => [
                'home' => $this->calculateDefensiveEfficiency($homeStats),
                'away' => $this->calculateDefensiveEfficiency($awayStats)
            ],
            'overall_efficiency' => [
                'home' => $this->calculateOverallEfficiency($homeStats),
                'away' => $this->calculateOverallEfficiency($awayStats)
            ]
        ];
    }

    private function calculateOffensiveEfficiency(array $stats): float
    {
        $ppa = $stats['offense_ppa'] ?? 0;
        $successRate = $stats['offense_success_rate'] ?? 0;
        $explosiveness = $stats['offense_explosiveness'] ?? 0;

        return round(($ppa * 0.4) + ($successRate * 0.4) + ($explosiveness * 0.2), 3);
    }

    private function calculateDefensiveEfficiency(array $stats): float
    {
        $ppa = $stats['defense_ppa'] ?? 0;
        $successRate = $stats['defense_success_rate'] ?? 0;
        $explosiveness = $stats['defense_explosiveness'] ?? 0;

        return round(($ppa * 0.4) + ($successRate * 0.4) + ($explosiveness * 0.2), 3);
    }

    private function calculateOverallEfficiency(array $stats): float
    {
        $offensive = $this->calculateOffensiveEfficiency($stats);
        $defensive = $this->calculateDefensiveEfficiency($stats);

        return round(($offensive + $defensive) / 2, 3);
    }

    public function calculateScoringPrediction(array $homeStats, array $awayStats): array
    {
        return [
            'home_predicted_range' => [
                'low' => $this->calculateLowScorePrediction($homeStats, $awayStats, 'home'),
                'high' => $this->calculateHighScorePrediction($homeStats, $awayStats, 'home')
            ],
            'away_predicted_range' => [
                'low' => $this->calculateLowScorePrediction($homeStats, $awayStats, 'away'),
                'high' => $this->calculateHighScorePrediction($homeStats, $awayStats, 'away')
            ]
        ];
    }

    private function calculateLowScorePrediction(array $homeStats, array $awayStats, string $team): float
    {
        if ($team === 'home') {
            $offensePPA = $homeStats['offense_ppa'] ?? 0;
            $offenseSuccess = $homeStats['offense_success_rate'] ?? 0;
            $defenseAdjustment = $awayStats['defense_success_rate'] ?? 0;
            $defensePPA = $awayStats['defense_ppa'] ?? 0;
        } else {
            $offensePPA = $awayStats['offense_ppa'] ?? 0;
            $offenseSuccess = $awayStats['offense_success_rate'] ?? 0;
            $defenseAdjustment = $homeStats['defense_success_rate'] ?? 0;
            $defensePPA = $homeStats['defense_ppa'] ?? 0;
        }

        // Calculate base score using conservative metrics
        $baseScore = (($offensePPA * 0.7) + ($offenseSuccess * 0.3)) * 100;

        // Apply defensive adjustment
        $defenseMultiplier = 1 - (($defenseAdjustment + abs($defensePPA)) / 2);

        // Calculate conservative score prediction
        $predictedScore = $baseScore * $defenseMultiplier;

        // Add baseline points and ensure minimum reasonable score
        return max(round($predictedScore + 10, 1), 7);
    }

    private function calculateHighScorePrediction(array $homeStats, array $awayStats, string $team): float
    {
        if ($team === 'home') {
            $offensePPA = $homeStats['offense_ppa'] ?? 0;
            $offenseSuccess = $homeStats['offense_success_rate'] ?? 0;
            $explosiveness = $homeStats['offense_explosiveness'] ?? 0;
            $defenseAdjustment = $awayStats['defense_success_rate'] ?? 0;
            $redZoneSuccess = $homeStats['offense_power_success'] ?? 0;
        } else {
            $offensePPA = $awayStats['offense_ppa'] ?? 0;
            $offenseSuccess = $awayStats['offense_success_rate'] ?? 0;
            $explosiveness = $awayStats['offense_explosiveness'] ?? 0;
            $defenseAdjustment = $homeStats['defense_success_rate'] ?? 0;
            $redZoneSuccess = $awayStats['offense_power_success'] ?? 0;
        }

        // Calculate optimistic base score using peak performance metrics
        $baseScore = (
                ($offensePPA * 0.4) +
                ($offenseSuccess * 0.3) +
                ($explosiveness * 0.2) +
                ($redZoneSuccess * 0.1)
            ) * 100;

        // Apply more lenient defensive adjustment
        $defenseMultiplier = 1 - ($defenseAdjustment * 0.5);

        // Calculate optimistic score prediction
        $predictedScore = $baseScore * $defenseMultiplier;

        // Add baseline points and ensure reasonable ceiling
        $score = max(round($predictedScore + 17, 1), 14);

        // Ensure high prediction is always higher than low prediction
        return max($score, $this->calculateLowScorePrediction($homeStats, $awayStats, $team) + 7);
    }

    public function calculateDriveMetrics(array $homeStats, array $awayStats): array
    {
        return [
            'scoring_drive_probability' => [
                'home' => $this->calculateScoringProbability($homeStats),
                'away' => $this->calculateScoringProbability($awayStats)
            ],
            'explosive_drive_probability' => [
                'home' => $this->calculateExplosiveProbability($homeStats),
                'away' => $this->calculateExplosiveProbability($awayStats)
            ],
            'red_zone_efficiency' => [
                'home' => $this->calculateRedZoneEfficiency($homeStats),
                'away' => $this->calculateRedZoneEfficiency($awayStats)
            ]
        ];
    }

    private function calculateScoringProbability(array $stats): float
    {
        $successRate = $stats['offense_success_rate'] ?? 0;
        $ppa = $stats['offense_ppa'] ?? 0;

        // Weighted calculation based on success rate and PPA
        return min(($successRate * 0.7 + max($ppa, 0) * 0.3), 1.0);
    }

    private function calculateExplosiveProbability(array $stats): float
    {
        $explosiveness = $stats['offense_explosiveness'] ?? 0;
        $successRate = $stats['offense_success_rate'] ?? 0;

        // Weight explosiveness more heavily for big play probability
        return min(($explosiveness * 0.8 + $successRate * 0.2), 1.0);
    }

    private function calculateRedZoneEfficiency(array $stats): float
    {
        $powerSuccess = $stats['offense_power_success'] ?? 0;
        $successRate = $stats['offense_success_rate'] ?? 0;

        // Combine power success and general success rate for red zone efficiency
        return min(($powerSuccess * 0.6 + $successRate * 0.4), 1.0);
    }

    public function calculateMatchupAdvantages(array $homeStats, array $awayStats): array
    {
        return [
            'rushing_advantage' => [
                'offense' => $this->calculateRushingOffensiveAdvantage($homeStats, $awayStats),
                'defense' => $this->calculateRushingDefensiveAdvantage($homeStats, $awayStats)
            ],
            'passing_advantage' => [
                'offense' => $this->calculatePassingOffensiveAdvantage($homeStats, $awayStats),
                'defense' => $this->calculatePassingDefensiveAdvantage($homeStats, $awayStats)
            ],
            'situational_advantages' => [
                'standard_downs' => $this->calculateStandardDownsAdvantage($homeStats, $awayStats),
                'passing_downs' => $this->calculatePassingDownsAdvantage($homeStats, $awayStats)
            ],
            'line_play' => [
                'offensive_line' => $this->calculateOffensiveLineRating($homeStats, $awayStats),
                'defensive_line' => $this->calculateDefensiveLineRating($homeStats, $awayStats)
            ]
        ];
    }

    private function calculateRushingOffensiveAdvantage(array $homeStats, array $awayStats): float
    {
        $homePPA = $homeStats['offense_rushing_ppa'] ?? 0;
        $homeSuccess = $homeStats['offense_rushing_success_rate'] ?? 0;
        $awayPPA = $awayStats['defense_rushing_ppa'] ?? 0;
        $awaySuccess = $awayStats['defense_rushing_success_rate'] ?? 0;

        return round(($homePPA - $awayPPA) + ($homeSuccess - $awaySuccess), 3);
    }

    private function calculateRushingDefensiveAdvantage(array $homeStats, array $awayStats): float
    {
        $homePPA = $homeStats['defense_rushing_ppa'] ?? 0;
        $homeSuccess = $homeStats['defense_rushing_success_rate'] ?? 0;
        $awayPPA = $awayStats['offense_rushing_ppa'] ?? 0;
        $awaySuccess = $awayStats['offense_rushing_success_rate'] ?? 0;

        return round(($awayPPA - $homePPA) + ($awaySuccess - $homeSuccess), 3);
    }

    private function calculatePassingOffensiveAdvantage(array $homeStats, array $awayStats): float
    {
        $homePPA = $homeStats['offense_passing_ppa'] ?? 0;
        $homeSuccess = $homeStats['offense_passing_success_rate'] ?? 0;
        $awayPPA = $awayStats['defense_passing_ppa'] ?? 0;
        $awaySuccess = $awayStats['defense_passing_success_rate'] ?? 0;

        return round(($homePPA - $awayPPA) + ($homeSuccess - $awaySuccess), 3);
    }

    private function calculatePassingDefensiveAdvantage(array $homeStats, array $awayStats): float
    {
        $homePPA = $homeStats['defense_passing_ppa'] ?? 0;
        $homeSuccess = $homeStats['defense_passing_success_rate'] ?? 0;
        $awayPPA = $awayStats['offense_passing_ppa'] ?? 0;
        $awaySuccess = $awayStats['offense_passing_success_rate'] ?? 0;

        return round(($awayPPA - $homePPA) + ($awaySuccess - $homeSuccess), 3);
    }

    private function calculateStandardDownsAdvantage(array $homeStats, array $awayStats): float
    {
        $homeAdv = $homeStats['offense_standard_downs_ppa'] ?? 0;
        $awayAdv = $awayStats['defense_standard_downs_ppa'] ?? 0;
        $homeSuccess = $homeStats['offense_standard_downs_success_rate'] ?? 0;
        $awaySuccess = $awayStats['defense_standard_downs_success_rate'] ?? 0;

        return round(($homeAdv - $awayAdv) + ($homeSuccess - $awaySuccess), 3);
    }

    private function calculatePassingDownsAdvantage(array $homeStats, array $awayStats): float
    {
        $homeAdv = $homeStats['offense_passing_downs_ppa'] ?? 0;
        $awayAdv = $awayStats['defense_passing_downs_ppa'] ?? 0;
        $homeSuccess = $homeStats['offense_passing_downs_success_rate'] ?? 0;
        $awaySuccess = $awayStats['defense_passing_downs_success_rate'] ?? 0;

        return round(($homeAdv - $awayAdv) + ($homeSuccess - $awaySuccess), 3);
    }

    private function calculateOffensiveLineRating(array $homeStats, array $awayStats): float
    {
        $lineYards = $homeStats['offense_line_yards'] ?? 0;
        $powerSuccess = $homeStats['offense_power_success'] ?? 0;
        $stuffRate = $homeStats['offense_stuff_rate'] ?? 0;

        // Higher line yards and power success is better, lower stuff rate is better
        return round($lineYards + $powerSuccess - $stuffRate, 3);
    }

    private function calculateDefensiveLineRating(array $homeStats, array $awayStats): float
    {
        $lineYards = $homeStats['defense_line_yards'] ?? 0;
        $powerSuccess = $homeStats['defense_power_success'] ?? 0;
        $stuffRate = $homeStats['defense_stuff_rate'] ?? 0;

        // For defense, lower line yards and power success is better, higher stuff rate is better
        return round(-$lineYards - $powerSuccess + $stuffRate, 3);
    }

    private function predictPoints(float $offensePPA, float $defensePPA): float
    {
        return round(($offensePPA - $defensePPA) * 100, 1);
    }


}