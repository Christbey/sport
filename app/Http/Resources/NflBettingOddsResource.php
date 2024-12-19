<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NflBettingOddsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'game_date' => $this->game_date->toDateTimeString(), // Formatting the datetime
            'away_team' => $this->away_team,
            'home_team' => $this->home_team,
            'away_team_id' => $this->away_team_id,
            'home_team_id' => $this->home_team_id,
            'source' => $this->source,
            'spread_home' => $this->spread_home,
            'spread_away' => $this->spread_away,
            'total_over' => $this->total_over,
            'total_under' => $this->total_under,
            'moneyline_home' => $this->moneyline_home,
            'moneyline_away' => $this->moneyline_away,
            'implied_total_home' => $this->implied_total_home,
            'implied_total_away' => $this->implied_total_away,
            'matchup' => $this->matchup, // Accessor defined in the model
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
