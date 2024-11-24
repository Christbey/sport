<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NFLPlayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Calculate EP before the play
        $epBefore = $this->getEP(
            $this['start']['down'] ?? 1,
            $this['start']['distance'] ?? 10,
            $this['start']['yardsToEndzone'] ?? 50
        );

        // Calculate EP after the play
        $epAfter = $this->getEP(
            $this['end']['down'] ?? 1,
            $this['end']['distance'] ?? 10,
            $this['end']['yardsToEndzone'] ?? 50
        );

        // Calculate EPA
        $epa = $epAfter - $epBefore;

        return [
            'id' => $this['id'],
            'sequence_number' => $this['sequenceNumber'] ?? null,
            'game_state' => [
                'period' => $this['period']['number'] ?? null,
                'clock' => $this['clock']['displayValue'] ?? null,
                'home_score' => $this['homeScore'] ?? null,
                'away_score' => $this['awayScore'] ?? null,
                'wall_clock' => $this['wallclock'] ?? null,
            ],
            'play_details' => [
                'type' => $this['type'],
                'description' => $this['text'],
                'yards_gained' => $this['statYardage'] ?? 0,
                'scoring_play' => $this['scoringPlay'] ?? false,
                'epa' => $epa,
            ],
            'field_position' => [
                'start' => $this['start'],
                'end' => $this['end'],
            ],
            'statistics' => $this->extractPlayerStats(),
            'timestamps' => [
                'modified' => $this['modified'] ?? null,
                'created_at' => $this['created_at'] ?? null,
                'updated_at' => $this['updated_at'] ?? null,
            ],
            'expected_points' => [
                'before_play' => $epBefore,
                'after_play' => $epAfter,
                'epa' => $epa,
            ],
        ];
    }

    private function getEP(int $down, int $distance, int $yardsToEndzone): float
    {
        // Simplified EP model

        $baseEP = 3.0 - ($yardsToEndzone / 100) * 2.0;

        switch ($down) {
            case 1:
                $ep = $baseEP;
                break;
            case 2:
                $ep = $baseEP + 0.5;
                break;
            case 3:
                $ep = $baseEP + 1.0;
                break;
            case 4:
                $ep = $baseEP + 1.5;
                break;
            default:
                $ep = $baseEP;
        }

        if ($distance <= 3) {
            $ep += 0.5;
        } elseif ($distance > 7) {
            $ep -= 0.5;
        }

        $ep = max(0, min(6, $ep));

        return round($ep, 2);
    }

    // Update other methods to use array syntax
    private function extractPlayerStats(): array
    {
        $stats = [];
        $playType = $this['type']['text'] ?? '';
        $yards = $this['statYardage'] ?? 0;

        // Get primary player
        $primaryPlayer = $this->getPrimaryPlayer();

        if ($primaryPlayer) {
            switch ($playType) {
                case 'Pass Reception':
                    $stats['passing'] = [
                        'player_id' => $primaryPlayer['passer']['id'] ?? null,
                        'attempts' => 1,
                        'completions' => 1,
                        'yards' => $yards,
                        'touchdowns' => $this['scoringPlay'] ? 1 : 0,
                    ];
                    $stats['receiving'] = [
                        'player_id' => $primaryPlayer['target']['id'] ?? null,
                        'receptions' => 1,
                        'yards' => $yards,
                        'touchdowns' => $this['scoringPlay'] ? 1 : 0,
                    ];
                    break;

                case 'Rush':
                    $stats['rushing'] = [
                        'player_id' => $primaryPlayer['id'] ?? null,
                        'attempts' => 1,
                        'yards' => $yards,
                        'touchdowns' => $this['scoringPlay'] ? 1 : 0,
                    ];
                    break;
            }
        }

        return $stats;
    }

    private function getPrimaryPlayer(): ?array
    {
        $typeText = $this['type']['text'] ?? '';

        switch ($typeText) {
            case 'Pass Reception':
            case 'Pass Incompletion':
                preg_match('/pass .*? to ([A-Z]\.[A-Za-z-]+)/', $this['text'], $matches);
                $quarterback = $this->extractQuarterback();
                return [
                    'passer' => $quarterback,
                    'target' => isset($matches[1]) ? [
                        'name' => $matches[1],
                        'id' => $this->findPlayerId($matches[1]),
                    ] : null,
                ];

            case 'Rush':
                preg_match('/^([A-Z]\.[A-Za-z-]+)/', $this['text'], $matches);
                return isset($matches[1]) ? [
                    'name' => $matches[1],
                    'id' => $this->findPlayerId($matches[1]),
                    'role' => 'rusher',
                ] : null;

            case 'Sack':
                return $this->extractQuarterback();

            default:
                return null;
        }
    }

    private function extractQuarterback(): ?array
    {
        preg_match('/^[^A-Z]*([A-Z]\.[A-Za-z-]+)/', $this['text'], $matches);
        return isset($matches[1]) ? [
            'name' => $matches[1],
            'id' => $this->findPlayerId($matches[1]),
            'role' => 'quarterback',
        ] : null;
    }

    private function findPlayerId(string $name): ?string
    {
        // This would typically look up the player in your database
        return md5($name);
    }

    // Update other methods similarly...


    private function extractPlayers(): array
    {
        // Extract player information from play description
        preg_match_all('/([A-Z]\.[A-Za-z-]+)/', $this->text, $matches);
        $playerNames = $matches[1] ?? [];

        return array_map(function ($name) {
            return [
                'name' => $name,
                'id' => $this->findPlayerId($name),
                'team_id' => $this->determinePlayerTeam($name),
                'role' => $this->determinePlayerRole($name),
            ];
        }, array_unique($playerNames));
    }

    private function determinePlayerTeam(string $name): ?string
    {
        // Logic to determine player's team based on context
        // This would need to be implemented based on your data structure
        return null;
    }

    private function determinePlayerRole(string $name): string
    {
        // Logic to determine player's role in the play
        if (strpos($this->text, 'pass') !== false && strpos($this->text, $name) === 0) {
            return 'passer';
        }
        if (strpos($this->text, 'to ' . $name) !== false) {
            return 'receiver';
        }
        if (strpos($this->text, $name . ' up the middle') !== false) {
            return 'rusher';
        }
        if (strpos($this->text, $name . ' sacked') !== false) {
            return 'quarterback';
        }

        return 'other';
    }

    private function getSecondaryPlayers(): array
    {
        // Extract tacklers, blockers, and other supporting players
        $players = [];

        // Extract tacklers
        if (preg_match('/\(([A-Z]\.[A-Za-z-]+(?:; [A-Z]\.[A-Za-z-]+)*)\)$/', $this->text, $matches)) {
            $tacklers = explode('; ', $matches[1]);
            foreach ($tacklers as $tackler) {
                $players['tacklers'][] = [
                    'name' => $tackler,
                    'id' => $this->findPlayerId($tackler),
                ];
            }
        }

        return $players;
    }
}
