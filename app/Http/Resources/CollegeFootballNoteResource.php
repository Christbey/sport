<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollegeFootballNoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'game_id' => $this->game_id,
            'team_id' => $this->team_id,
            'note' => $this->note,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'team' => [
                'id' => $this->team->id,
                'school' => $this->team->school,
                'mascot' => $this->team->mascot,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}