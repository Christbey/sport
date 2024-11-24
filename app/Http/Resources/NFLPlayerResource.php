<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NFLPlayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => [
                'full' => $this->name,
                'first' => $this->first_name,
                'last' => $this->last_name,
                'display' => $this->display_name,
            ],
            'team' => [
                'id' => $this->team_id,
                'name' => $this->team_name,
                'abbreviation' => $this->team_abbreviation,
            ],
            'position' => [
                'primary' => $this->position,
                'depth_chart' => $this->depth_chart_position,
            ],
            'jersey_number' => $this->jersey_number,
            'game_stats' => [
                'passing' => [
                    'attempts' => $this->stats_passing_attempts ?? 0,
                    'completions' => $this->stats_passing_completions ?? 0,
                    'yards' => $this->stats_passing_yards ?? 0,
                    'touchdowns' => $this->stats_passing_touchdowns ?? 0,
                    'interceptions' => $this->stats_passing_interceptions ?? 0,
                ],
                'rushing' => [
                    'attempts' => $this->stats_rushing_attempts ?? 0,
                    'yards' => $this->stats_rushing_yards ?? 0,
                    'touchdowns' => $this->stats_rushing_touchdowns ?? 0,
                ],
                'receiving' => [
                    'targets' => $this->stats_receiving_targets ?? 0,
                    'receptions' => $this->stats_receiving_receptions ?? 0,
                    'yards' => $this->stats_receiving_yards ?? 0,
                    'touchdowns' => $this->stats_receiving_touchdowns ?? 0,
                ],
                'defense' => [
                    'tackles' => $this->stats_defense_tackles ?? 0,
                    'sacks' => $this->stats_defense_sacks ?? 0,
                    'interceptions' => $this->stats_defense_interceptions ?? 0,
                ],
            ],
        ];
    }
}
