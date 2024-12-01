<?php

namespace App\Repositories\Nfl;

use App\Models\Nfl\NflTeamStat;

class NflTeamStatRepository
{
    /**
     * Update or create team stats from the API data.
     *
     * @param string $gameId The game ID.
     * @param array $teamStats The team stats data (away/home).
     */
    public function updateOrCreateFromApi(string $gameId, array $teamStats): void
    {
        foreach (['away', 'home'] as $key) {
            if (isset($teamStats[$key])) {
                $teamData = $teamStats[$key];

                $data = [
                    'game_id' => $gameId,
                    'team_id' => $teamData['teamID'],
                    'team_abv' => $teamData['teamAbv'],
                    'total_yards' => isset($teamData['totalYards']) ? (int)$teamData['totalYards'] : null,
                    'rushing_yards' => isset($teamData['rushingYards']) ? (int)$teamData['rushingYards'] : null,
                    'passing_yards' => isset($teamData['passingYards']) ? (int)$teamData['passingYards'] : null,
                    'points_allowed' => isset($teamStats[$key === 'away' ? 'home' : 'away']['totalPts']) ? (int)$teamStats[$key === 'away' ? 'home' : 'away']['totalPts'] : null,
                    // New fields
                    'rushing_attempts' => isset($teamData['rushingAttempts']) ? (int)$teamData['rushingAttempts'] : null,
                    'fumbles_lost' => isset($teamData['fumblesLost']) ? (int)$teamData['fumblesLost'] : null,
                    'penalties' => $teamData['penalties'] ?? null,
                    'total_plays' => isset($teamData['totalPlays']) ? (int)$teamData['totalPlays'] : null,
                    'possession' => $teamData['possession'] ?? null,
                    'safeties' => isset($teamData['safeties']) ? (int)$teamData['safeties'] : null,
                    'pass_completions_and_attempts' => $teamData['passCompletionsAndAttempts'] ?? null,
                    'passing_first_downs' => isset($teamData['passingFirstDowns']) ? (int)$teamData['passingFirstDowns'] : null,
                    'interceptions_thrown' => isset($teamData['interceptionsThrown']) ? (int)$teamData['interceptionsThrown'] : null,
                    'sacks_and_yards_lost' => $teamData['sacksAndYardsLost'] ?? null,
                    'third_down_efficiency' => $teamData['thirdDownEfficiency'] ?? null,
                    'yards_per_play' => isset($teamData['yardsPerPlay']) ? (float)$teamData['yardsPerPlay'] : null,
                    'red_zone_scored_and_attempted' => $teamData['redZoneScoredAndAttempted'] ?? null,
                    'defensive_interceptions' => isset($teamData['defensiveInterceptions']) ? (int)$teamData['defensiveInterceptions'] : null,
                    'defensive_or_special_teams_tds' => isset($teamData['defensiveOrSpecialTeamsTds']) ? (int)$teamData['defensiveOrSpecialTeamsTds'] : null,
                    'total_drives' => isset($teamData['totalDrives']) ? (int)$teamData['totalDrives'] : null,
                    'rushing_first_downs' => isset($teamData['rushingFirstDowns']) ? (int)$teamData['rushingFirstDowns'] : null,
                    'first_downs' => isset($teamData['firstDowns']) ? (int)$teamData['firstDowns'] : null,
                    'first_downs_from_penalties' => isset($teamData['firstDownsFromPenalties']) ? (int)$teamData['firstDownsFromPenalties'] : null,
                    'fourth_down_efficiency' => $teamData['fourthDownEfficiency'] ?? null,
                    'yards_per_rush' => isset($teamData['yardsPerRush']) ? (float)$teamData['yardsPerRush'] : null,
                    'turnovers' => isset($teamData['turnovers']) ? (int)$teamData['turnovers'] : null,
                    'yards_per_pass' => isset($teamData['yardsPerPass']) ? (float)$teamData['yardsPerPass'] : null,
                ];

                // Save or update the record
                NflTeamStat::updateOrCreate(
                    ['game_id' => $gameId, 'team_id' => $teamData['teamID']],
                    $data
                );
            }
        }
    }
}
