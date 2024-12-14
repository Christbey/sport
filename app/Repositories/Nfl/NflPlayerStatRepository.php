<?php

namespace App\Repositories\Nfl;

use App\Models\Nfl\NflBoxScore;
use App\Models\Nfl\NflPlayerStat;
use DB;
use Exception;
use Log;

class NflPlayerStatRepository
{

    protected $model;

    public function __construct()
    {
        $this->model = new NflPlayerStat();
    }

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

    public function getReceivingStats(?string $longName = null, ?string $teamAbv = null): array
    {
        try {
            $stats = DB::table('nfl_player_stats')
                ->select([
                    'long_name',
                    'team_abv',
                    DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.recYds")) AS UNSIGNED)) as avg_yards'),
                    DB::raw('MAX(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.recYds")) AS UNSIGNED)) as max_yards'),
                    DB::raw('MIN(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.recYds")) AS UNSIGNED)) as min_yards'),
                    DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.recTD")) AS UNSIGNED)) as avg_touchdowns'),
                    DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.recTD")) AS UNSIGNED)) as total_touchdowns'),
                    DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.rec")) AS UNSIGNED)) as avg_receptions'),
                    DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.rec")) AS UNSIGNED)) as total_receptions'),
                ])
                ->when($longName, function ($query) use ($longName) {
                    $query->where('long_name', $longName);
                })
                ->when($teamAbv, function ($query) use ($teamAbv) {
                    $query->where('team_abv', $teamAbv);
                })
                ->whereNotNull('receiving')
                ->groupBy('long_name', 'team_abv')
                ->first();

            // Check if no stats are returned
            if (!$stats) {
                return [
                    'success' => false,
                    'message' => 'No receiving stats found for the specified player and team.',
                ];
            }

            // Return formatted results
            return [
                'success' => true,
                'name' => $stats->long_name,
                'team' => $stats->team_abv,
                'receiving_stats' => [
                    'avg_yards' => round($stats->avg_yards, 2),
                    'max_yards' => $stats->max_yards,
                    'min_yards' => $stats->min_yards,
                    'avg_touchdowns' => round($stats->avg_touchdowns, 2),
                    'total_touchdowns' => $stats->total_touchdowns,
                    'avg_receptions' => round($stats->avg_receptions, 2),
                    'total_receptions' => $stats->total_receptions,
                ],
            ];
        } catch (Exception $e) {
            // Log the exception
            Log::error('Error fetching receiving stats', [
                'error' => $e->getMessage(),
                'long_name' => $longName,
                'team_abv' => $teamAbv,
            ]);

            // Return error response
            return [
                'success' => false,
                'message' => 'An error occurred while fetching receiving stats.',
            ];
        }
    }

    public function getRushingStats(?string $longName = null, ?string $teamAbv = null): array
    {
        try {
            $stats = DB::table('nfl_player_stats')
                ->select([
                    'long_name',
                    'team_abv',
                    DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.rushYds")) AS UNSIGNED)) as avg_yards'),
                    DB::raw('MAX(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.rushYds")) AS UNSIGNED)) as max_yards'),
                    DB::raw('MIN(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.rushYds")) AS UNSIGNED)) as min_yards'),
                    DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.rushTD")) AS UNSIGNED)) as avg_touchdowns'),
                    DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.rushTD")) AS UNSIGNED)) as total_touchdowns'),
                    DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.carries")) AS UNSIGNED)) as avg_carries'),
                    DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.carries")) AS UNSIGNED)) as total_carries'),
                ])
                ->when($longName, function ($query) use ($longName) {
                    $query->where('long_name', $longName);
                })
                ->when($teamAbv, function ($query) use ($teamAbv) {
                    $query->where('team_abv', $teamAbv);
                })
                ->whereNotNull('rushing')
                ->groupBy('long_name', 'team_abv')
                ->first();

            // Check if no stats are returned
            if (!$stats) {
                return [
                    'success' => false,
                    'message' => 'No rushing stats found for the specified player and team.',
                ];
            }

            // Return formatted results
            return [
                'success' => true,
                'name' => $stats->long_name,
                'team' => $stats->team_abv,
                'rushing_stats' => [
                    'avg_yards' => round($stats->avg_yards, 2),
                    'max_yards' => $stats->max_yards,
                    'min_yards' => $stats->min_yards,
                    'avg_touchdowns' => round($stats->avg_touchdowns, 2),
                    'total_touchdowns' => $stats->total_touchdowns,
                    'avg_carries' => round($stats->avg_carries, 2),
                    'total_carries' => $stats->total_carries,
                ],
            ];
        } catch (Exception $e) {
            // Log the exception
            Log::error('Error fetching rushing stats', [
                'error' => $e->getMessage(),
                'long_name' => $longName,
                'team_abv' => $teamAbv,
            ]);

            // Return error response
            return [
                'success' => false,
                'message' => 'An error occurred while fetching rushing stats.',
            ];
        }
    }

    public function getDefenseStats(?string $longName = null, ?string $teamAbv = null): array
    {
        try {
            $stats = DB::table('nfl_player_stats')
                ->select([
                    'long_name',
                    'team_abv',
                    DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defensive, "$.soloTackles")) AS UNSIGNED)) as total_solo_tackles'),
                    DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defensive, "$.totalTackles")) AS UNSIGNED)) as total_tackles'),
                    DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defensive, "$.sacks")) AS UNSIGNED)) as total_sacks'),
                    DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defensive, "$.forcedFumbles")) AS UNSIGNED)) as total_forced_fumbles'),
                    DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defensive, "$.fumblesRecovered")) AS UNSIGNED)) as total_fumbles_recovered'),
                    DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defensive, "$.defensiveInterceptions")) AS UNSIGNED)) as total_interceptions'),
                    DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defensive, "$.passDeflections")) AS UNSIGNED)) as total_pass_deflections'),
                    DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(defensive, "$.soloTackles")) AS UNSIGNED)) as avg_solo_tackles'),
                    DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(defensive, "$.totalTackles")) AS UNSIGNED)) as avg_tackles'),
                    DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(defensive, "$.sacks")) AS UNSIGNED)) as avg_sacks'),
                ])
                ->when($longName, function ($query) use ($longName) {
                    $query->where('long_name', $longName);
                })
                ->when($teamAbv, function ($query) use ($teamAbv) {
                    $query->where('team_abv', $teamAbv);
                })
                ->whereNotNull('defensive')
                ->groupBy('long_name', 'team_abv')
                ->first();

            // Check if no stats are returned
            if (!$stats) {
                return [
                    'success' => false,
                    'message' => 'No defensive stats found for the specified player and team.',
                ];
            }

            // Return formatted results
            return [
                'success' => true,
                'name' => $stats->long_name,
                'team' => $stats->team_abv,
                'defensive_stats' => [
                    'total_solo_tackles' => $stats->total_solo_tackles,
                    'total_tackles' => $stats->total_tackles,
                    'total_sacks' => round($stats->total_sacks, 2),
                    'total_forced_fumbles' => $stats->total_forced_fumbles,
                    'total_fumbles_recovered' => $stats->total_fumbles_recovered,
                    'total_interceptions' => $stats->total_interceptions,
                    'total_pass_deflections' => $stats->total_pass_deflections,
                    'avg_solo_tackles' => round($stats->avg_solo_tackles, 2),
                    'avg_tackles' => round($stats->avg_tackles, 2),
                    'avg_sacks' => round($stats->avg_sacks, 2),
                ],
            ];
        } catch (Exception $e) {
            // Log the exception
            Log::error('Error fetching defensive stats', [
                'error' => $e->getMessage(),
                'long_name' => $longName,
                'team_abv' => $teamAbv,
            ]);

            // Return error response
            return [
                'success' => false,
                'message' => 'An error occurred while fetching defensive stats.',
            ];
        }
    }

    public function getKickingStats(?string $longName = null, ?string $teamAbv = null): array
    {
        try {
            $stats = DB::table('nfl_player_stats')
                ->select([
                    'long_name',
                    'team_abv',
                    DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(kicking, "$.fieldGoalsMade")) AS UNSIGNED)) as total_field_goals_made'),
                    DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(kicking, "$.fieldGoalsAttempted")) AS UNSIGNED)) as total_field_goals_attempted'),
                    DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(kicking, "$.fieldGoalsMade")) AS UNSIGNED)) as avg_field_goals_made'),
                    DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(kicking, "$.fieldGoalsAttempted")) AS UNSIGNED)) as avg_field_goals_attempted'),
                    DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(kicking, "$.extraPointsMade")) AS UNSIGNED)) as total_extra_points_made'),
                    DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(kicking, "$.extraPointsAttempted")) AS UNSIGNED)) as total_extra_points_attempted'),
                    DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(kicking, "$.extraPointsMade")) AS UNSIGNED)) as avg_extra_points_made'),
                    DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(kicking, "$.extraPointsAttempted")) AS UNSIGNED)) as avg_extra_points_attempted'),
                ])
                ->when($longName, function ($query) use ($longName) {
                    $query->where('long_name', $longName);
                })
                ->when($teamAbv, function ($query) use ($teamAbv) {
                    $query->where('team_abv', $teamAbv);
                })
                ->whereNotNull('kicking')
                ->groupBy('long_name', 'team_abv')
                ->first();

            // Check if no stats are returned
            if (!$stats) {
                return [
                    'success' => false,
                    'message' => 'No kicking stats found for the specified player and team.',
                ];
            }

            // Return formatted results
            return [
                'success' => true,
                'name' => $stats->long_name,
                'team' => $stats->team_abv,
                'kicking_stats' => [
                    'total_field_goals_made' => $stats->total_field_goals_made,
                    'total_field_goals_attempted' => $stats->total_field_goals_attempted,
                    'avg_field_goals_made' => round($stats->avg_field_goals_made, 2),
                    'avg_field_goals_attempted' => round($stats->avg_field_goals_attempted, 2),
                    'total_extra_points_made' => $stats->total_extra_points_made,
                    'total_extra_points_attempted' => $stats->total_extra_points_attempted,
                    'avg_extra_points_made' => round($stats->avg_extra_points_made, 2),
                    'avg_extra_points_attempted' => round($stats->avg_extra_points_attempted, 2),
                ],
            ];
        } catch (Exception $e) {
            // Log the exception
            Log::error('Error fetching kicking stats', [
                'error' => $e->getMessage(),
                'long_name' => $longName,
                'team_abv' => $teamAbv,
            ]);

            // Return error response
            return [
                'success' => false,
                'message' => 'An error occurred while fetching kicking stats.',
            ];
        }
    }

    public function getPuntingStats(?string $longName = null, ?string $teamAbv = null): array
    {
        try {
            $stats = DB::table('nfl_player_stats')
                ->select([
                    'long_name',
                    'team_abv',
                    DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(punting, "$.punts")) AS UNSIGNED)) as total_punts'),
                    DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(punting, "$.punts")) AS UNSIGNED)) as avg_punts'),
                    DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(punting, "$.puntYds")) AS UNSIGNED)) as total_punt_yards'),
                    DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(punting, "$.puntYds")) AS UNSIGNED)) as avg_punt_yards'),
                    DB::raw('MAX(CAST(JSON_UNQUOTE(JSON_EXTRACT(punting, "$.longPunt")) AS UNSIGNED)) as longest_punt'),
                    DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(punting, "$.inside20")) AS UNSIGNED)) as punts_inside_20'),
                ])
                ->when($longName, function ($query) use ($longName) {
                    $query->where('long_name', $longName);
                })
                ->when($teamAbv, function ($query) use ($teamAbv) {
                    $query->where('team_abv', $teamAbv);
                })
                ->whereNotNull('punting')
                ->groupBy('long_name', 'team_abv')
                ->first();

            // Check if no stats are returned
            if (!$stats) {
                return [
                    'success' => false,
                    'message' => 'No punting stats found for the specified player and team.',
                ];
            }

            // Return formatted results
            return [
                'success' => true,
                'name' => $stats->long_name,
                'team' => $stats->team_abv,
                'punting_stats' => [
                    'total_punts' => $stats->total_punts,
                    'avg_punts' => round($stats->avg_punts, 2),
                    'total_punt_yards' => $stats->total_punt_yards,
                    'avg_punt_yards' => round($stats->avg_punt_yards, 2),
                    'longest_punt' => $stats->longest_punt,
                    'punts_inside_20' => $stats->punts_inside_20,
                ],
            ];
        } catch (Exception $e) {
            // Log the exception
            Log::error('Error fetching punting stats', [
                'error' => $e->getMessage(),
                'long_name' => $longName,
                'team_abv' => $teamAbv,
            ]);

            // Return error response
            return [
                'success' => false,
                'message' => 'An error occurred while fetching punting stats.',
            ];
        }
    }
}
