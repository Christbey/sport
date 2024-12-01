<?php

namespace App\Repositories\Nfl;

use App\Models\Nfl\NflBoxScore;
use App\Models\Nfl\NflPlayerStat;

class NflPlayerStatRepository
{
    /**
     * Update or create player stats from the API data.
     *
     * @param array $playerStats
     */
    public function updateOrCreateFromApi(array $playerStats): void
    {
        foreach ($playerStats as $playerID => $stats) {
            $data = [
                'game_id' => $stats['gameID'],
                'player_id' => $playerID,
                'team_id' => $stats['teamID'],
                'team_abv' => $stats['teamAbv'],
                'long_name' => $stats['longName'],
                'rushing' => $stats['Rushing'] ?? null,
                'receiving' => $stats['Receiving'] ?? null,
                'kicking' => $stats['Kicking'] ?? null,
                'punting' => $stats['Punting'] ?? null,
                'defense' => $stats['Defense'] ?? null,
                'opponent_id' => $this->getOpponentId($stats['gameID'], $stats['teamID']),
            ];

            // Save or update the record
            NflPlayerStat::updateOrCreate(
                ['game_id' => $stats['gameID'], 'player_id' => $playerID],
                $data
            );
        }
    }

    /**
     * Get the opponent ID for a game.
     *
     * @param string $gameId
     * @param string $teamId
     * @return string|null
     */
    private function getOpponentId(string $gameId, string $teamId): ?string
    {
        $boxScore = NflBoxScore::where('game_id', $gameId)->first();
        if (!$boxScore) {
            return null;
        }

        return $boxScore->home_team === $teamId ? $boxScore->away_team : $boxScore->home_team;
    }
}
