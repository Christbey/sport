<?php

namespace App\Listeners;

use App\Events\BoxScoreFetched;
use App\Models\Nfl\NflTeamStat;
use Illuminate\Support\Facades\Log;

class StoreTeamStats
{
    /**
     * Handle the event.
     *
     * @param BoxScoreFetched $event
     * @return void
     */
    public function handle(BoxScoreFetched $event)
    {
        $gameData = $event->boxScoreData['body'] ?? [];

        if (empty($gameData) || !isset($gameData['gameID'])) {
            Log::warning('Invalid game data received for team stats: "gameID" is missing.');
            return;
        }

        // Store or update team stats
        if (isset($gameData['teamStats'])) {
            $teamStatsData = [];
            foreach ($gameData['teamStats'] as $teamStats) {
                $teamStatsData[] = [
                    'team_id' => isset($teamStats['teamID']) ? (int)$teamStats['teamID'] : null,
                    'game_id' => $gameData['gameID'],
                    'team_abv' => isset($teamStats['teamAbv']) ? trim($teamStats['teamAbv']) : null,
                    'total_yards' => isset($teamStats['totalYards']) ? (int)$teamStats['totalYards'] : null,
                    'rushing_yards' => isset($teamStats['rushingYards']) ? (int)$teamStats['rushingYards'] : null,
                    'passing_yards' => isset($teamStats['passingYards']) ? (int)$teamStats['passingYards'] : null,
                    'points_allowed' => isset($teamStats['ptsAllowed']) ? (int)$teamStats['ptsAllowed'] : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Specify the columns to update to prevent duplicates
            $teamUpdateColumns = [
                'team_abv',
                'total_yards',
                'rushing_yards',
                'passing_yards',
                'points_allowed',
                'updated_at',
            ];

            NflTeamStat::upsert($teamStatsData, ['team_id', 'game_id'], $teamUpdateColumns);

            Log::info("Team stats for game {$event->gameID} stored successfully.");
        }
    }
}
