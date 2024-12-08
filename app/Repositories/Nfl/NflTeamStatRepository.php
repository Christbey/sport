<?php

namespace App\Repositories\Nfl;

use App\Models\Nfl\NflTeamStat;
use Exception;
use Illuminate\Support\Collection;

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


    public function queryTeamStats(
        array  $filters = [],
        array  $statFilter = [],
        array  $compare = [],
        string $orderBy = null,
        string $orderDirection = 'desc',
        int    $limit = null
    ): array
    {
        try {
            $query = NflTeamStat::query();

            // Define the columns explicitly
            $columns = [
                'team_abv',
                'total_yards',
                'rushing_yards',
                'passing_yards',
                'points_allowed',
                'rushing_attempts',
                'fumbles_lost',
                'penalties',
                'total_plays',
                'possession',
                'safeties',
                'pass_completions_and_attempts',
                'passing_first_downs',
                'interceptions_thrown',
                'sacks_and_yards_lost',
                'third_down_efficiency',
                'yards_per_play',
                'red_zone_scored_and_attempted',
                'defensive_interceptions',
                'defensive_or_special_teams_tds',
                'total_drives',
                'rushing_first_downs',
                'first_downs',
                'first_downs_from_penalties',
                'fourth_down_efficiency',
                'yards_per_rush',
                'turnovers',
                'yards_per_pass',
            ];

            $query->select($columns);

            // Apply filters
            if (!empty($filters['teamFilter'])) {
                $query->where('team_abv', $filters['teamFilter']);
            }

            if (!empty($filters['locationFilter'])) {
                $query->where('location', $filters['locationFilter']);
            }

            if (!empty($filters['conferenceFilter'])) {
                $query->join('nfl_teams', 'nfl_team_stats.team_id', '=', 'nfl_teams.id')
                    ->where('nfl_teams.conference', $filters['conferenceFilter']);
            }

            if (!empty($filters['divisionFilter'])) {
                $query->join('nfl_teams', 'nfl_team_stats.team_id', '=', 'nfl_teams.id')
                    ->where('nfl_teams.division', $filters['divisionFilter']);
            }

            // Apply stat filter
            if (!empty($statFilter)) {
                $statColumn = $statFilter['stat_column'];
                $orderDirection = $statFilter['stat_type'] === 'most' ? 'desc' : 'asc';
                $query->orderBy($statColumn, $orderDirection);
            }

            // Apply comparison
            if (!empty($compare)) {
                $query->whereIn('team_abv', $compare['team_abv'])
                    ->orderBy($compare['stat_column'], $orderDirection);
            }

            // Apply sorting
            if ($orderBy) {
                $query->orderBy($orderBy, $orderDirection);
            }

            // Apply limit
            if ($limit) {
                $query->limit($limit);
            }

            $results = $query->get();

            // Return results
            return [
                'success' => true,
                'data' => $results
            ];

        } catch (Exception $e) {
            // Handle errors and pass them to the view
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }


    public function compareTeamStats(array $teamAbvs, string $statColumn): Collection
    {
        return NflTeamStat::query()
            ->whereIn('team_abv', $teamAbvs)
            ->orderBy($statColumn, 'desc')
            ->get(['team_abv', $statColumn]);
    }


    /**
     * Map API or database data into the required structure.
     *
     * @param string $gameId
     * @param array $teamData
     * @param array $teamStats
     * @param string $key
     * @return array
     */

    private function mapTeamData(string $gameId, array $teamData, array $teamStats, string $key): Collection
    {
        return collect([
            'game_id' => $gameId,
            'team_id' => $teamData['teamID'] ?? null,
            'team_abv' => $teamData['teamAbv'] ?? null,
            'total_yards' => isset($teamData['totalYards']) ? (int)$teamData['totalYards'] : null,
            'rushing_yards' => isset($teamData['rushingYards']) ? (int)$teamData['rushingYards'] : null,
            'passing_yards' => isset($teamData['passingYards']) ? (int)$teamData['passingYards'] : null,
            'points_allowed' => isset($teamStats[$key === 'away' ? 'home' : 'away']['totalPts']) ? (int)$teamStats[$key === 'away' ? 'home' : 'away']['totalPts'] : null,
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
        ]);
    }

}
