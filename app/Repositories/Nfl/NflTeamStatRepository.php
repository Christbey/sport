<?php

namespace App\Repositories\Nfl;

use App\Models\Nfl\NflTeamStat;
use DB;
use Exception;
use Log;

class NflTeamStatRepository
{
    /**
     * Update or create team statistics based on API data.
     *
     * @param string $gameId The unique identifier for the game.
     * @param array $teamStats The statistics for each team.
     * @return void
     */
    public function updateOrCreateFromApi(string $gameId, array $teamStats): void
    {
        foreach (['away', 'home'] as $key) {
            if (isset($teamStats[$key])) {
                $teamData = $teamStats[$key];
                $teamID = $teamData['teamID'] ?? null;
                $teamAbv = $teamData['teamAbv'] ?? null;
                $result = $teamData['result'] ?? null; // 'W', 'L', or 'T'

                // Extract points_allowed
                $pointsAllowed = isset($teamData['points_allowed']) ? (int)$teamData['points_allowed'] : null;

                if (is_null($pointsAllowed)) {
                    Log::warning("points_allowed not set for team {$teamAbv} (ID: {$teamID}) in game {$gameId}. Defaulting to 0.");
                    $pointsAllowed = 0;
                }

                // Prepare the data array for updating/creating the record
                $data = [
                    'game_id' => $gameId,
                    'team_id' => $teamID,
                    'team_abv' => $teamAbv,
                    'total_yards' => isset($teamData['totalYards']) ? (int)$teamData['totalYards'] : null,
                    'rushing_yards' => isset($teamData['rushingYards']) ? (int)$teamData['rushingYards'] : null,
                    'passing_yards' => isset($teamData['passingYards']) ? (int)$teamData['passingYards'] : null,
                    'points_allowed' => $pointsAllowed,
                    'result' => $result, // 'W', 'L', or 'T'

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

                // Log the data being saved or updated
                Log::info("Data for team {$teamAbv} in game {$gameId}: " . json_encode($data));

                // Save or update the record in the database
                NflTeamStat::updateOrCreate(
                    ['game_id' => $gameId, 'team_id' => $teamID],
                    $data
                );
            }
        }
    }


    /**
     * Retrieve statistics for a specific team and week.
     *
     * @param string $teamAbv The abbreviation of the team (e.g., KC for Kansas City Chiefs).
     * @param int $week The week number.
     * @return array The team statistics.
     */
    public function getTeamStats(string $teamAbv, int $week): array
    {
        try {
            $stats = NflTeamStat::where('team_abv', strtoupper($teamAbv))
                ->where('game_week', (string)$week)
                ->first();

            if (!$stats) {
                return [
                    'success' => false,
                ];
            }

            return [
                'success' => true,
                'data' => $stats->toArray()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error retrieving team statistics: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Compare specific statistics between multiple teams for a given week.
     *
     * @param array $teamAbvs Array of team abbreviations (e.g., ['KC', 'NE']).
     * @param string $statColumn The statistic to compare (e.g., 'total_yards').
     * @param int $week The week number.
     * @return array The comparison results.
     */
    public function compareTeamsStats(array $teamAbvs, string $statColumn, ?int $week = null): array
    {
        try {
            $teamAbvs = array_map('strtoupper', $teamAbvs);
            $validStatColumns = $this->getValidStatColumns();

            if (!in_array($statColumn, $validStatColumns)) {
                return [
                    'success' => false,
                    'message' => 'Invalid stat column provided.',
                ];
            }

            // Build query with a join to nfl_team_schedules to get game_week
            $query = NflTeamStat::join('nfl_team_schedules', 'nfl_team_stats.game_id', '=', 'nfl_team_schedules.game_id')
                ->whereIn('nfl_team_stats.team_abv', $teamAbvs)
                ->selectRaw('nfl_team_stats.team_abv, AVG(nfl_team_stats.' . $statColumn . ') as avg_' . $statColumn)
                ->groupBy('nfl_team_stats.team_abv');

            // Filter by week if provided
            if ($week) {
                $query->where('nfl_team_schedules.game_week', $week);
            }

            $stats = $query->get();

            if ($stats->isEmpty()) {
                return [
                    'success' => false,
                    'message' => $week
                        ? 'No statistics found for the specified week and teams.'
                        : 'No statistics found for the season and teams.',
                ];
            }

            $comparison = $stats->map(function ($stat) use ($statColumn) {
                return [
                    'team' => $stat->team_abv,
                    'stat' => $stat->{"avg_{$statColumn}"},
                ];
            });

            return [
                'success' => true,
                'data' => $comparison->toArray(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error comparing team statistics: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get a list of valid statistic columns.
     *
     * @return array The valid statistic columns.
     */
    private function getValidStatColumns(): array
    {
        return [
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
            // Add any additional stat columns as needed
        ];
    }

    /**
     * Retrieve the top N teams based on a specific statistic for a given week.
     *
     * @param string $statColumn The statistic to rank teams by (e.g., 'total_yards').
     * @param int $week The week number.
     * @param int $limit The number of top teams to retrieve.
     * @return array The top teams' statistics.
     */
    public function getTopTeamsByStat(string $statColumn, ?int $week = null, int $limit = 5): array
    {
        try {
            $validStatColumns = $this->getValidStatColumns();

            if (!in_array($statColumn, $validStatColumns)) {
                return [
                    'success' => false,
                    'message' => 'Invalid stat column provided.',
                ];
            }

            // Build the query
            $query = NflTeamStat::join('nfl_team_schedules', 'nfl_team_stats.game_id', '=', 'nfl_team_schedules.game_id')
                ->selectRaw('nfl_team_stats.team_abv, AVG(nfl_team_stats.' . $statColumn . ') as avg_' . $statColumn)
                ->groupBy('nfl_team_stats.team_abv')
                ->orderBy('avg_' . $statColumn, 'desc')
                ->limit($limit);

            // Filter by week if provided
            if ($week) {
                $query->where('nfl_team_schedules.game_week', $week);
            }

            $stats = $query->get();

            if ($stats->isEmpty()) {
                return [
                    'success' => false,
                    'message' => $week
                        ? 'No statistics found for the specified week and stat column.'
                        : 'No statistics found for the season and stat column.',
                ];
            }

            return [
                'success' => true,
                'data' => $stats->toArray(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error retrieving top teams: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate league-wide average statistics for a given week.
     *
     * @param int $week The week number.
     * @return array The league averages.
     */
    public function getLeagueAverages(int $week): array
    {
        try {
            // Step 1: Retrieve game_ids for the specified week from nfl_team_schedules
            $gameIds = DB::table('nfl_team_schedules')
                ->where('game_week', $week)
                ->pluck('game_id')
                ->toArray();

            if (empty($gameIds)) {
                return [
                    'success' => false,
                    'message' => 'No games found for the specified week.'
                ];
            }

            // Step 2: Fetch nfl_team_stats records for these game_ids
            $stats = NflTeamStat::whereIn('game_id', $gameIds)->get();

            if ($stats->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No team statistics found for the specified week.'
                ];
            }

            // Step 3: Calculate averages
            $averages = [
                'total_yards_avg' => $stats->avg('total_yards'),
                'rushing_yards_avg' => $stats->avg('rushing_yards'),
                'passing_yards_avg' => $stats->avg('passing_yards'),
                'points_allowed_avg' => $stats->avg('points_allowed'),
                'rushing_attempts_avg' => $stats->avg('rushing_attempts'),
                'fumbles_lost_avg' => $stats->avg('fumbles_lost'),
                'penalties_avg' => $stats->avg('penalties'),
                'total_plays_avg' => $stats->avg('total_plays'),
                'possession_avg' => $stats->avg('possession'),
                'safeties_avg' => $stats->avg('safeties'),
                'pass_completions_and_attempts_avg' => $stats->avg('pass_completions_and_attempts'),
                'passing_first_downs_avg' => $stats->avg('passing_first_downs'),
                'interceptions_thrown_avg' => $stats->avg('interceptions_thrown'),
                'sacks_and_yards_lost_avg' => $stats->avg('sacks_and_yards_lost'),
                'third_down_efficiency_avg' => $stats->avg('third_down_efficiency'),
                'yards_per_play_avg' => $stats->avg('yards_per_play'),
                'red_zone_scored_and_attempted_avg' => $stats->avg('red_zone_scored_and_attempted'),
                'defensive_interceptions_avg' => $stats->avg('defensive_interceptions'),
                'defensive_or_special_teams_tds_avg' => $stats->avg('defensive_or_special_teams_tds'),
                'total_drives_avg' => $stats->avg('total_drives'),
                'rushing_first_downs_avg' => $stats->avg('rushing_first_downs'),
                'first_downs_avg' => $stats->avg('first_downs'),
                'first_downs_from_penalties_avg' => $stats->avg('first_downs_from_penalties'),
                'fourth_down_efficiency_avg' => $stats->avg('fourth_down_efficiency'),
                'yards_per_rush_avg' => $stats->avg('yards_per_rush'),
                'turnovers_avg' => $stats->avg('turnovers'),
                'yards_per_pass_avg' => $stats->avg('yards_per_pass'),
            ];

            return [
                'success' => true,
                'data' => $averages
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error calculating league averages: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Retrieve the total first downs for a team in a specific game.
     *
     * @param string $teamAbv The abbreviation of the team (e.g., KC for Kansas City Chiefs).
     * @param int|null $week The week number (optional).
     * @return array The team first downs statistics.
     */
    public function getFirstDownsAverage(array $teamAbvs, ?int $week = null, ?int $season = null): array
    {
        try {
            // Query to calculate average first downs
            $query = DB::table('nfl_team_stats')
                ->join('nfl_team_schedules', 'nfl_team_stats.game_id', '=', 'nfl_team_schedules.game_id')
                ->whereIn('nfl_team_stats.team_abv', array_map('strtoupper', $teamAbvs));

            // Filter by week if provided
            if ($week) {
                $query->where('nfl_team_schedules.game_week', $week);
            }

            // Filter by season if provided
            if ($season) {
                $query->where('nfl_team_schedules.season', $season);
            }

            // Aggregate average first downs for each team
            $result = $query
                ->selectRaw('nfl_team_stats.team_abv, AVG(nfl_team_stats.first_downs) as avg_first_downs')
                ->groupBy('nfl_team_stats.team_abv')
                ->get();

            if ($result->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No data found for the specified teams and filters.'
                ];
            }

            // Format the results
            $averages = $result->map(function ($row) {
                return [
                    'team' => $row->team_abv,
                    'avg_first_downs' => round($row->avg_first_downs, 2)
                ];
            });

            return [
                'success' => true,
                'data' => $averages->toArray()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error calculating average first downs: ' . $e->getMessage()
            ];
        }
    }

    public function getTeamStatAverage(array $teamAbvs, string $statColumn, ?int $week = null, ?int $season = null): array
    {
        try {
            // Validate the provided statistic column
            $validStatColumns = [
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

            if (!in_array($statColumn, $validStatColumns)) {
                return [
                    'success' => false,
                    'message' => 'Invalid statistic column provided.',
                ];
            }

            // Build the query
            $query = DB::table('nfl_team_stats')
                ->join('nfl_team_schedules', 'nfl_team_stats.game_id', '=', 'nfl_team_schedules.game_id')
                ->whereIn('nfl_team_stats.team_abv', array_map('strtoupper', $teamAbvs));

            // Filter by week if provided
            if ($week) {
                $query->where('nfl_team_schedules.game_week', $week);
            }

            // Filter by season if provided
            if ($season) {
                $query->where('nfl_team_schedules.season', $season);
            }

            // Aggregate average for the provided statistic
            $result = $query
                ->selectRaw('nfl_team_stats.team_abv, AVG(nfl_team_stats.' . $statColumn . ') as avg_' . $statColumn)
                ->groupBy('nfl_team_stats.team_abv')
                ->get();

            if ($result->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No data found for the specified teams and filters.'
                ];
            }

            // Format the results
            $averages = $result->map(function ($row) use ($statColumn) {
                return [
                    'team' => $row->team_abv,
                    'avg_' . $statColumn => round($row->{'avg_' . $statColumn}, 2),
                ];
            });

            return [
                'success' => true,
                'data' => $averages->toArray()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error calculating average: ' . $e->getMessage()
            ];
        }
    }

    public function getSummedTeamStats(string $teamAbv, int $startWeek, int $endWeek, int $season = 2024): array
    {

        try {
            $stats = DB::table('nfl_team_stats')
                ->join('nfl_team_schedules', 'nfl_team_stats.game_id', '=', 'nfl_team_schedules.game_id')
                ->where('nfl_team_stats.team_abv', strtoupper($teamAbv))
                ->whereBetween('nfl_team_schedules.game_week', [$startWeek, $endWeek])
                ->where('nfl_team_schedules.season', $season)
                ->selectRaw('
                SUM(total_yards) as total_yards,
                SUM(rushing_yards) as rushing_yards,
                SUM(passing_yards) as passing_yards,
                SUM(points_allowed) as points_allowed,
                SUM(rushing_attempts) as rushing_attempts,
                SUM(fumbles_lost) as fumbles_lost,
                SUM(penalties) as penalties,
                SUM(total_plays) as total_plays,
                SUM(possession) as possession,
                SUM(safeties) as safeties,
                SUM(pass_completions_and_attempts) as pass_completions_and_attempts,
                SUM(passing_first_downs) as passing_first_downs,
                SUM(interceptions_thrown) as interceptions_thrown,
                SUM(sacks_and_yards_lost) as sacks_and_yards_lost,
                SUM(third_down_efficiency) as third_down_efficiency,
                SUM(yards_per_play) as yards_per_play,
                SUM(red_zone_scored_and_attempted) as red_zone_scored_and_attempted,
                SUM(defensive_interceptions) as defensive_interceptions,
                SUM(defensive_or_special_teams_tds) as defensive_or_special_teams_tds,
                SUM(total_drives) as total_drives,
                SUM(rushing_first_downs) as rushing_first_downs,
                SUM(first_downs) as first_downs,
                SUM(first_downs_from_penalties) as first_downs_from_penalties,
                SUM(fourth_down_efficiency) as fourth_down_efficiency,
                SUM(yards_per_rush) as yards_per_rush,
                SUM(turnovers) as turnovers,
                SUM(yards_per_pass) as yards_per_pass
            ')
                ->first();

            if (!$stats) {
                return [
                    'success' => false,
                    'message' => 'No statistics found for the specified team and weeks.'
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'total_yards' => $stats->total_yards ?? 0,
                    'rushing_yards' => $stats->rushing_yards ?? 0,
                    'passing_yards' => $stats->passing_yards ?? 0,
                    'points_allowed' => $stats->points_allowed ?? 0,
                    'rushing_attempts' => $stats->rushing_attempts ?? 0,
                    'fumbles_lost' => $stats->fumbles_lost ?? 0,
                    'penalties' => $stats->penalties ?? 0,
                    'total_plays' => $stats->total_plays ?? 0,
                    'possession' => $stats->possession ?? 0,
                    'safeties' => $stats->safeties ?? 0,
                    'pass_completions_and_attempts' => $stats->pass_completions_and_attempts ?? 0,
                    'passing_first_downs' => $stats->passing_first_downs ?? 0,
                    'interceptions_thrown' => $stats->interceptions_thrown ?? 0,
                    'sacks_and_yards_lost' => $stats->sacks_and_yards_lost ?? 0,
                    'third_down_efficiency' => $stats->third_down_efficiency ?? 0,
                    'yards_per_play' => $stats->yards_per_play ?? 0,
                    'red_zone_scored_and_attempted' => $stats->red_zone_scored_and_attempted ?? 0,
                    'defensive_interceptions' => $stats->defensive_interceptions ?? 0,
                    'defensive_or_special_teams_tds' => $stats->defensive_or_special_teams_tds ?? 0,
                    'total_drives' => $stats->total_drives ?? 0,
                    'rushing_first_downs' => $stats->rushing_first_downs ?? 0,
                    'first_downs' => $stats->first_downs ?? 0,
                    'first_downs_from_penalties' => $stats->first_downs_from_penalties ?? 0,
                    'fourth_down_efficiency' => $stats->fourth_down_efficiency ?? 0,
                    'yards_per_rush' => $stats->yards_per_rush ?? 0,
                    'turnovers' => $stats->turnovers ?? 0,
                    'yards_per_pass' => $stats->yards_per_pass ?? 0
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error fetching summed team statistics: ' . $e->getMessage()
            ];
        }
    }


}
