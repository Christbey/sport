<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class NFLDriveCollection extends ResourceCollection
{
    private $playerStats = [];

    public function __construct($resource, $playerStats = [])
    {
        parent::__construct($resource);
        $this->playerStats = $playerStats;
    }

    public function toArray($request)
    {
        // Transform each drive into an array using NFLDriveResource
        $driveArray = $this->collection->map(function ($drive) use ($request) {
            return (new NFLDriveResource($drive))->toArray($request);
        })->all();

        // Calculate total EPA for the game
        $totalEPA = collect($driveArray)->sum(function ($drive) {
            return $drive['drive_summary']['total_epa'] ?? 0;
        });

        $totalPlays = collect($driveArray)->sum(function ($drive) {
            return $drive['drive_summary']['plays_count'] ?? 0;
        });

        $totalYards = collect($driveArray)->sum(function ($drive) {
            return $drive['drive_summary']['total_yards'] ?? 0;
        });

        $scoringDrives = collect($driveArray)->filter(function ($drive) {
            return $drive['drive_summary']['scoring_drive'] ?? false;
        })->count();

        return [
            'data' => $driveArray,
            'meta' => [
                'game_info' => [
                    'total_drives' => count($driveArray),
                    'total_plays' => $totalPlays,
                    'total_yards' => $totalYards,
                    'scoring_drives' => $scoringDrives,
                    'total_epa' => $totalEPA,
                ],
                'player_stats' => $this->playerStats,
                'time_of_possession' => $this->calculateTimeOfPossession(),
            ],
        ];
    }

    private function calculateTimeOfPossession(): array
    {
        // Implement the logic to calculate time of possession
        return [
            'total_time' => '00:00',
            'average_time_per_drive' => '00:00',
        ];
    }
}
