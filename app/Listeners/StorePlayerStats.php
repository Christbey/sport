<?php

namespace App\Listeners;

use App\Events\BoxScoreFetched;
use App\Models\Nfl\NflPlayerStat;
use App\Models\Nfl\NflTeamSchedule;
use Exception;
use Illuminate\Support\Facades\DB;
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
        try {
            DB::beginTransaction();

            $gameData = $event->boxScoreData['body'] ?? [];

            if (empty($gameData) || !isset($gameData['gameID'])) {
                Log::warning('Invalid game data received for player stats: "gameID" is missing.');
                return;
            }

            // Fetch game schedule to determine opponent IDs
            $gameSchedule = NflTeamSchedule::where('game_id', $gameData['gameID'])->firstOrFail();

            if (!isset($gameData['playerStats'])) {
                Log::info("No player stats found for game {$gameData['gameID']}");
                return;
            }

            // First, delete existing records for this game to prevent duplicates
            NflPlayerStat::where('game_id', $gameData['gameID'])->delete();

            $playerStatsData = $this->preparePlayerStats($gameData['playerStats'], $gameData['gameID'], $gameSchedule);

            // Use insert instead of upsert since we've already cleared existing records
            NflPlayerStat::insert($playerStatsData);

            DB::commit();

            Log::info("Player stats for game {$event->gameID} stored successfully.", [
                'player_count' => count($playerStatsData)
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error processing player stats for game {$event->gameID}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Prepare player stats data for database insertion
     *
     * @param array $playerStats
     * @param string $gameId
     * @param NflTeamSchedule $gameSchedule
     * @return array
     */
    private function preparePlayerStats(array $playerStats, string $gameId, NflTeamSchedule $gameSchedule): array
    {
        $statTypes = ['Receiving', 'Rushing', 'Kicking', 'Punting', 'Defense'];
        $playerStatsData = [];
        $now = now();

        foreach ($playerStats as $playerId => $stats) {
            $teamId = (int)($stats['teamID'] ?? null);
            $opponentId = ($teamId === $gameSchedule->home_team_id)
                ? $gameSchedule->away_team_id
                : $gameSchedule->home_team_id;

            $playerData = [
                'player_id' => (int)$playerId,
                'game_id' => $gameId,
                'team_id' => $teamId,
                'opponent_id' => $opponentId,
                'team_abv' => trim($stats['teamAbv'] ?? ''),
                'long_name' => trim($stats['longName'] ?? ''),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Add stat types
            foreach ($statTypes as $type) {
                $playerData[strtolower($type)] = isset($stats[$type])
                    ? json_encode($stats[$type], JSON_THROW_ON_ERROR)
                    : null;
            }

            $playerStatsData[] = $playerData;
        }

        return $playerStatsData;
    }
}