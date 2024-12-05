<?php

namespace App\Repositories\Nfl;

use App\Models\Nfl\NflPlay;
use App\Models\Nfl\NflPlayPlayer;

class NflPlayByPlayRepository
{
    public function updateOrCreateFromApi(array $data, string $gameId): void
    {
        $driveId = 1;
        $lastTeamId = null;

        foreach ($data['allPlayByPlay'] as $playData) {
            $currentTeamId = $playData['teamID'] ?? null;
            $playDetails = $this->analyzePlayDescription($playData['play']);

            if ($lastTeamId !== null && $currentTeamId !== $lastTeamId) {
                $driveId++;
            }

            $play = NflPlay::updateOrCreate(
                [
                    'game_id' => $gameId,
                    'quarter' => $this->parseQuarter($playData['playPeriod']),
                    'time' => $playData['playClock'],
                    'description' => $playData['play']
                ],
                [
                    'team_id' => $currentTeamId,
                    'drive_id' => $driveId,
                    'down' => isset($playData['downAndDistance']) ?
                        (int)substr($playData['downAndDistance'], 0, 1) : null,
                    'distance' => isset($playData['downAndDistance']) ?
                        $this->extractDistance($playData['downAndDistance']) : null,
                    'yard_line' => isset($playData['downAndDistance']) ?
                        $this->extractYardLine($playData['downAndDistance']) : null,
                    'play_type' => $playDetails['play_type'],
                    'yards_gained' => $playDetails['yards_gained'],
                    'first_down' => $playDetails['first_down'],
                    'touchdown' => $playDetails['touchdown'],
                    'turnover' => $playDetails['turnover']
                ]
            );

            if (isset($playData['playerStats'])) {
                foreach ($playData['playerStats'] as $playerId => $stats) {
                    NflPlayPlayer::updateOrCreate(
                        [
                            'play_id' => $play->id,
                            'player_id' => $playerId
                        ],
                        [
                            'role' => array_key_first($stats),
                            'team_id' => $currentTeamId
                        ]
                    );
                }
            }

            $lastTeamId = $currentTeamId;
        }
    }

    private function analyzePlayDescription(string $description): array
    {
        $result = [
            'play_type' => 'unknown',
            'yards_gained' => 0,
            'first_down' => str_contains($description, 'for a 1ST down'),
            'touchdown' => str_contains(strtoupper($description), 'TOUCHDOWN'),
            'turnover' => str_contains(strtoupper($description), 'INTERCEPTED') ||
                (str_contains(strtoupper($description), 'FUMBLES') &&
                    str_contains(strtoupper($description), 'RECOVERED by')) ||
                str_contains($description, 'field goal is No Good')
        ];
        if (str_contains($description, 'Two-Minute Warning')) {
            $result['play_type'] = 'timeout';
        } elseif (str_contains($description, 'spiked the ball')) {
            $result['play_type'] = 'spike';
        } elseif (str_contains($description, 'kneels')) {
            $result['play_type'] = 'kneel';
            if (preg_match('/for (-?\d+) yards?/', $description, $matches)) {
                $result['yards_gained'] = (int)$matches[1];
            }

        }
        if (str_contains($description, 'PENALTY')) {
            $result['play_type'] = 'penalty';
            if (preg_match('/(\d+) yards/', $description, $matches)) {
                $result['yards_gained'] = -(int)$matches[1]; // Negative yards for penalties
            }
        } elseif (str_contains($description, 'Timeout')) {
            $result['play_type'] = 'timeout';
        } elseif (str_contains($description, 'END QUARTER') ||
            str_contains($description, 'END GAME')) {
            $result['play_type'] = 'end_period';
        } elseif (str_contains($description, 'field goal')) {
            $result['play_type'] = 'field_goal';
        } elseif (preg_match('/pass|sacked/i', $description)) {
            $result['play_type'] = 'pass';
            if (preg_match('/for (-?\d+) yards?/', $description, $matches)) {
                $result['yards_gained'] = (int)$matches[1];
            }
        } elseif (preg_match('/kicks|punt/i', $description)) {
            $result['play_type'] = 'kick';
            if (preg_match('/for (-?\d+) yards?/', $description, $matches)) {
                $result['yards_gained'] = (int)$matches[1];
            }
        } elseif (preg_match('/(?:left|right) (?:end|guard|tackle)|up the middle/i', $description)) {
            $result['play_type'] = 'run';
            if (preg_match('/for (-?\d+) yards?/', $description, $matches)) {
                $result['yards_gained'] = (int)$matches[1];
            } elseif (str_contains($description, 'kneels')) {
                $result['play_type'] = 'kneel';
                if (preg_match('/for (-?\d+) yards?/', $description, $matches)) {
                    $result['yards_gained'] = (int)$matches[1];
                }
            }
        }

        return $result;
    }

    private function parseQuarter(string $period): int
    {
        if (preg_match('/OT(\d+)/', $period, $matches)) {
            return 4 + (int)$matches[1];
        }
        return (int)str_replace('Q', '', $period);
    }

    private function extractDistance(string $text): ?int
    {
        if (preg_match('/& (\d+)/', $text, $matches)) {
            return (int)$matches[1];
        }
        return null;
    }

    private function extractYardLine(string $text): ?string
    {
        if (preg_match('/at ([A-Z]+ \d+)/', $text, $matches)) {
            return $matches[1];
        }
        return null;
    }
}