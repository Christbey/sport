<?php
// Resource model should include all needed fields:
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollegeBasketballResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'game_date' => $this->game_date,
            'home_team' => $this->home_team,
            'away_team' => $this->away_team,
            'hypothetical_spread' => $this->hypothetical_spread,
            'offense_difference' => $this->offense_difference,
            'defense_difference' => $this->defense_difference,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}