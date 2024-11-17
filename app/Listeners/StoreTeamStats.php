<?php

namespace App\Listeners;

use App\Events\BoxScoreFetched;
use App\Models\Nfl\NflTeamStat;
use Exception;
use Illuminate\Support\Facades\DB;
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
        try {
            DB::beginTransaction();

            $gameData = $event->boxScoreData['body'] ?? [];

            if (empty($gameData) || !isset($gameData['gameID'])) {
                Log::warning('Invalid game data received for team stats: "gameID" is missing.');
                return;
            }

            if (!isset($gameData['teamStats'])) {
                Log::info("No team stats found for game {$gameData['gameID']}");
                return;
            }

            // First, delete existing records for this game to prevent duplicates
            NflTeamStat::where('game_id', $gameData['gameID'])->delete();

            $teamStatsData = $this->prepareTeamStats($gameData['teamStats'], $gameData['gameID']);

            // Use insert instead of upsert since we've already cleared existing records
            NflTeamStat::insert($teamStatsData);

            DB::commit();

            Log::info("Team stats for game {$event->gameID} stored successfully.", [
                'team_count' => count($teamStatsData)
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error processing team stats for game {$event->gameID}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Prepare team stats data for database insertion
     *
     * @param array $teamStats
     * @param string $gameId
     * @return array
     */
    private function prepareTeamStats(array $teamStats, string $gameId): array
    {
        $teamStatsData = [];
        $now = now();

        foreach ($teamStats as $stats) {
            $teamStatsData[] = [
                'team_id' => isset($stats['teamID']) ? (int)$stats['teamID'] : null,
                'game_id' => $gameId,
                'team_abv' => isset($stats['teamAbv']) ? trim($stats['teamAbv']) : null,
                'total_yards' => isset($stats['totalYards']) ? (int)$stats['totalYards'] : null,
                'rushing_yards' => isset($stats['rushingYards']) ? (int)$stats['rushingYards'] : null,
                'passing_yards' => isset($stats['passingYards']) ? (int)$stats['passingYards'] : null,
                'points_allowed' => isset($stats['ptsAllowed']) ? (int)$stats['ptsAllowed'] : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $teamStatsData;
    }
}