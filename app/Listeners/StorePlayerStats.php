<?php

namespace App\Listeners;

use App\Events\BoxScoreFetched;
use App\Models\Nfl\NflPlayerStat;
use App\Models\Nfl\NflTeamSchedule;
use Illuminate\Support\Facades\Log;

class StorePlayerStats
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
            Log::warning('Invalid game data received for player stats: "gameID" is missing.');
            return;
        }

        // Fetch game schedule to determine opponent IDs
        $gameSchedule = NflTeamSchedule::where('game_id', $gameData['gameID'])->first();
        if (!$gameSchedule) {
            Log::error("No schedule found for game_id {$gameData['gameID']}");
            return;
        }

        // Store or update player stats
        if (isset($gameData['playerStats'])) {
            $playerStatsData = [];
            foreach ($gameData['playerStats'] as $playerID => $playerStats) {
                // Determine the opponent ID
                $teamId = isset($playerStats['teamID']) ? (int)$playerStats['teamID'] : null;
                $opponentId = ($teamId === $gameSchedule->home_team_id) ? $gameSchedule->away_team_id : $gameSchedule->home_team_id;

                $playerStatsData[] = [
                    'player_id' => (int)$playerID,
                    'game_id' => $gameData['gameID'],
                    'team_id' => $teamId,
                    'opponent_id' => $opponentId,
                    'team_abv' => isset($playerStats['teamAbv']) ? trim($playerStats['teamAbv']) : null,
                    'long_name' => isset($playerStats['longName']) ? trim($playerStats['longName']) : null,
                    'receiving' => isset($playerStats['Receiving']) ? json_encode($playerStats['Receiving']) : null,
                    'rushing' => isset($playerStats['Rushing']) ? json_encode($playerStats['Rushing']) : null,
                    'kicking' => isset($playerStats['Kicking']) ? json_encode($playerStats['Kicking']) : null,
                    'punting' => isset($playerStats['Punting']) ? json_encode($playerStats['Punting']) : null,
                    'defense' => isset($playerStats['Defense']) ? json_encode($playerStats['Defense']) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Specify the columns to update to prevent duplicates
            $playerUpdateColumns = [
                'team_id',
                'opponent_id',
                'team_abv',
                'long_name',
                'receiving',
                'rushing',
                'kicking',
                'punting',
                'defense',
                'updated_at',
            ];

            NflPlayerStat::upsert($playerStatsData, ['player_id', 'game_id'], $playerUpdateColumns);

            Log::info("Player stats for game {$event->gameID} stored successfully.");
        }
    }
}
