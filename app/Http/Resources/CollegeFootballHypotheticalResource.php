<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollegeFootballHypotheticalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'week' => $this->week,
            'game_id' => $this->game_id,
            'game' => [
                'id' => $this->game->id,
                'start_date' => $this->start_date,
                'completed' => $this->completed,
                'formatted_spread' => $this->formatted_spread,
                'score' => [
                    'home' => $this->home_points,
                    'away' => $this->away_points,
                ],
            ],
            'teams' => [
                'home' => [
                    'id' => $this->home_team_id,
                    'school' => $this->homeTeam->school,
                    'mascot' => $this->homeTeam->mascot,
                    'color' => $this->homeTeam->color,
                    'ratings' => [
                        'elo' => $this->home_elo,
                        'fpi' => $this->home_fpi,
                    ],
                ],
                'away' => [
                    'id' => $this->away_team_id,
                    'school' => $this->awayTeam->school,
                    'mascot' => $this->awayTeam->mascot,
                    'color' => $this->awayTeam->color,
                    'ratings' => [
                        'elo' => $this->away_elo,
                        'fpi' => $this->away_fpi,
                    ],
                ],
            ],
            'predictions' => [
                'home_winning_percentage' => $this->home_winning_percentage,
                'winner_color' => $this->winner_color,
                'hypothetical_spread' => $this->hypothetical_spread,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}