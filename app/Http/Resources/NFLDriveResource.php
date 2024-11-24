<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NFLDriveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $playsArray = NFLPlayResource::collection(collect($this['plays']))->toArray($request);

        $totalEPA = collect($playsArray)->sum(function ($play) {
            return $play['play_details']['epa'] ?? 0;
        });

        return [
            'id' => $this['id'],
            'drive_summary' => [
                'result' => $this['result'],
                'scoring_drive' => $this['isScore'] ?? false,
                'plays_count' => $this['offensivePlays'],
                'total_yards' => $this['yards'],
                'time_elapsed' => $this['timeElapsed'],
                'description' => $this['description'],
                'total_epa' => $totalEPA,
            ],
            'drive_details' => [
                'start' => $this['start'],
                'end' => $this['end'],
            ],
            'team' => $this['team'],
            'plays' => $playsArray,
        ];
    }
}
