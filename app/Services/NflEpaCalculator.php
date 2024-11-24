<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

enum PlayType: string
{
    case RUSHING_TOUCHDOWN = 'Rushing Touchdown';
    case PASSING_TOUCHDOWN = 'Passing Touchdown';
    case FIELD_GOAL = 'Field Goal Good';
    case SAFETY = 'Safety';
    case KICKOFF = 'Kickoff';
    case PUNT = 'Punt';
    case SACK = 'Sack';
    case PASS_RECEPTION = 'Pass Reception';
    case PASS_INCOMPLETION = 'Pass Incompletion';
    case RUSH = 'Rush';
    case PENALTY = 'Penalty';
}

class NflEpaCalculator
{
    private const TURNOVER_PENALTY = -4.0;
    private const FIELD_POSITION_THRESHOLDS = [
        'RED_ZONE' => 10,
        'FIELD_GOAL_RANGE' => 30,
        'MIDFIELD' => 70
    ];

    private const SCORING_VALUES = [
        'TOUCHDOWN' => 7.0,
        'FIELD_GOAL' => 3.0,
        'SAFETY' => 2.0
    ];

    private const SPECIAL_TEAMS_VALUES = [
        'KICKOFF' => -0.5,
        'PUNT_BASE' => -1.5,
        'PUNT_YARD_MULTIPLIER' => 0.05,
        'PUNT_THRESHOLD' => 40
    ];

    private const PLAY_TYPE_ADJUSTMENTS = [
        'SACK' => ['type' => 'subtract', 'value' => 1.0],
        'PASS_RECEPTION' => ['type' => 'multiply', 'value' => 1.1],
        'PASS_INCOMPLETION' => ['type' => 'subtract', 'value' => 0.5],
        'RUSH' => ['type' => 'multiply', 'value' => 0.95],
        'PENALTY' => ['type' => 'multiply', 'value' => 0.5]
    ];

    public function calculateEPA(array $play): float
    {
        try {
            $playData = $this->extractPlayData($play);

            if ($playData['isScoring']) {
                return $this->getScoringPlayEPA($playData['playType']);
            }

            if (in_array($playData['playType'], [PlayType::KICKOFF->value, PlayType::PUNT->value])) {
                return $this->getSpecialTeamsEPA($playData['playType'], $playData['startYardLine'], $playData['endYardLine']);
            }

            return $this->calculateStandardEPA($playData);
        } catch (Exception $e) {
            Log::error('EPA Calculation Error', ['error' => $e->getMessage(), 'play' => $play]);
            return 0.0;
        }
    }

    private function extractPlayData(array $play): array
    {
        return [
            'playType' => $play['type']['text'] ?? '',
            'down' => $play['start']['down'] ?? 1,
            'distance' => $play['start']['distance'] ?? 10,
            'startYardLine' => $play['start']['yardLine'] ?? 0,
            'endYardLine' => $play['end']['yardLine'] ?? ($play['start']['yardLine'] ?? 0),
            'isTurnover' => $play['turnover'] ?? false,
            'isScoring' => $play['scoringPlay'] ?? false
        ];
    }

    private function getScoringPlayEPA(string $playType): float
    {
        return match ($playType) {
            PlayType::RUSHING_TOUCHDOWN->value, PlayType::PASSING_TOUCHDOWN->value => self::SCORING_VALUES['TOUCHDOWN'],
            PlayType::FIELD_GOAL->value => self::SCORING_VALUES['FIELD_GOAL'],
            PlayType::SAFETY->value => self::SCORING_VALUES['SAFETY'],
            default => 0.0
        };
    }

    private function getSpecialTeamsEPA(string $playType, int $startYardLine, int $endYardLine): float
    {
        if ($playType === PlayType::KICKOFF->value) {
            return self::SPECIAL_TEAMS_VALUES['KICKOFF'];
        }

        if ($playType === PlayType::PUNT->value) {
            $netYards = $endYardLine - $startYardLine;
            $extraYards = max(0, $netYards - self::SPECIAL_TEAMS_VALUES['PUNT_THRESHOLD']);
            return self::SPECIAL_TEAMS_VALUES['PUNT_BASE'] + ($extraYards * self::SPECIAL_TEAMS_VALUES['PUNT_YARD_MULTIPLIER']);
        }

        return 0.0;
    }

    private function calculateStandardEPA(array $playData): float
    {
        $baseEPA = $this->getFieldPositionValue($playData['endYardLine']) - $this->getFieldPositionValue($playData['startYardLine']);
        $baseEPA = $this->applyDownAndDistanceAdjustment($baseEPA, $playData['down'], $playData['distance']);
        $baseEPA = $this->applyPlayTypeAdjustment($baseEPA, $playData['playType']);

        if ($playData['isTurnover']) {
            $baseEPA += self::TURNOVER_PENALTY;
        }

        return round($baseEPA, 2);
    }

    private function getFieldPositionValue(int $yardLine): float
    {
        $yardsFromGoal = 100 - $yardLine;

        return match (true) {
            $yardsFromGoal <= self::FIELD_POSITION_THRESHOLDS['RED_ZONE'] => 5.0 + (0.2 * (self::FIELD_POSITION_THRESHOLDS['RED_ZONE'] - $yardsFromGoal)),
            $yardsFromGoal <= self::FIELD_POSITION_THRESHOLDS['FIELD_GOAL_RANGE'] => 3.0 + (0.1 * (self::FIELD_POSITION_THRESHOLDS['FIELD_GOAL_RANGE'] - $yardsFromGoal)),
            $yardsFromGoal <= self::FIELD_POSITION_THRESHOLDS['MIDFIELD'] => 2.0 + (0.02 * (self::FIELD_POSITION_THRESHOLDS['MIDFIELD'] - $yardsFromGoal)),
            default => 1.0 + (0.01 * (100 - $yardsFromGoal))
        };
    }

    private function applyDownAndDistanceAdjustment(float $baseEPA, int $down, int $distance): float
    {
        return $baseEPA * $this->getDownMultiplier($down) * $this->getDistanceMultiplier($distance);
    }

    private function getDownMultiplier(int $down): float
    {
        return match ($down) {
            2 => 0.9,
            3 => 0.8,
            4 => 0.7,
            default => 1.0
        };
    }

    private function getDistanceMultiplier(int $distance): float
    {
        return match (true) {
            $distance <= 3 => 1.2,
            $distance <= 7 => 1.0,
            $distance <= 10 => 0.9,
            default => 0.8
        };
    }

    private function applyPlayTypeAdjustment(float $baseEPA, string $playType): float
    {
        $adjustment = self::PLAY_TYPE_ADJUSTMENTS[strtoupper(str_replace(' ', '_', $playType))] ?? null;

        if (!$adjustment) {
            return $baseEPA;
        }

        return match ($adjustment['type']) {
            'subtract' => $baseEPA - $adjustment['value'],
            'multiply' => $baseEPA * $adjustment['value'],
            default => $baseEPA
        };
    }
}