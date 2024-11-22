<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CollegeFootballResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'week' => $this->week,
            'game_id' => $this->game_id,
            'game' => [
                'start_date' => $this->start_date,
                'completed' => $this->completed,
                'formatted_spread' => $this->formatted_spread,
                'home_points' => $this->home_points,
                'away_points' => $this->away_points,
            ],
            'teams' => [
                'home' => [
                    'id' => $this->home_team_id,
                    'elo' => $this->home_elo,
                    'fpi' => $this->home_fpi,
                ],
                'away' => [
                    'id' => $this->away_team_id,
                    'elo' => $this->away_elo,
                    'fpi' => $this->away_fpi,
                ],
            ],
            'analysis' => [
                'home_winning_percentage' => $this->home_winning_percentage,
                'winner_color' => $this->winner_color,
            ],
        ];
    }
}


