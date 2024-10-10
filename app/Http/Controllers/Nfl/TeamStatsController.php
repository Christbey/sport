<?php

namespace App\Http\Controllers\Nfl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeamStatsController extends Controller
{
    // Method to display the view with all query options and filter form
    public function index()
    {
        $queries = [
            'average_points'    => 'Average Points by Quarter',
            'bestReceivers'     => 'Best Receivers', // Combined option
            'bestRushers'       => 'Best Rushers',   // Updated option
            'bestTacklers'      => 'Best Tacklers',
        ];

        return view('nfl.stats.index', compact('queries'));
    }

    // Method to fetch the stats based on the selected query and filter
    public function getStats(Request $request)
    {
        $queryType  = $request->input('query');
        $teamFilter = $request->input('team');

        switch ($queryType) {
            case 'average_points':
                $data = $this->getAveragePoints($teamFilter);
                $tableHeadings = ['Team', 'Location Type', 'Q1', 'Q2', 'Q3', 'Q4', 'First Half', 'Second Half', 'Total Points'];
                $players = null;
                break;


            case 'bestReceivers':
                $data = $this->bestReceivers($teamFilter);
                $tableHeadings = ['Player', 'Team', 'Total Receiving Yards', 'Total Receptions', 'Receiving TDs', 'Average Yards Per Reception', 'Average Yards Per Game'];
                $players = $data; // Paginated data
                break;

            case 'bestRushers':
                $data = $this->bestRushers($teamFilter);
                $tableHeadings = ['Player', 'Team', 'Total Rushing Yards', 'Total Attempts', 'Rushing TDs', 'Games Played', 'Average Yards Per Attempt', 'Average Yards Per Game'];
                $players = $data; // Paginated data
                break;

            case 'bestTacklers':
                $data = $this->bestTacklers($teamFilter);
                $tableHeadings = ['Player', 'Team', 'Total Tackles', 'Total Sacks', 'Avg. Tackles', 'Avg. Sacks'];
                $players = $data; // Paginated data
                break;

            default:
                $data = [];
                $tableHeadings = [];
                $players = null;
                break;
        }

        return view('nfl.stats.show', compact('data', 'tableHeadings', 'players'))
            ->with('query', $queryType)
            ->with('team', $teamFilter);
    }

    // Combined Best Receivers Method
    public function bestReceivers($teamFilter = null)
    {
        $query = DB::table('nfl_player_stats')
            ->join('nfl_box_scores', 'nfl_player_stats.game_id', '=', 'nfl_box_scores.game_id')
            ->join('nfl_team_schedules', 'nfl_box_scores.game_id', '=', 'nfl_team_schedules.game_id')
            ->where('nfl_team_schedules.season_type', 'Regular Season')
            ->whereNotNull('receiving')
            ->select(
                'nfl_player_stats.long_name',
                'nfl_player_stats.team_abv',
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.recYds")) AS SIGNED)) AS total_receiving_yards'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.receptions")) AS UNSIGNED)) AS total_receptions'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.recTD")) AS UNSIGNED)) AS total_recTDs'),
                DB::raw('COALESCE(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.recYds")) AS SIGNED)) / NULLIF(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.receptions")) AS UNSIGNED)), 0), 0) AS average_yards_per_reception'),
                DB::raw('COALESCE(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.recYds")) AS SIGNED)) / NULLIF(COUNT(DISTINCT nfl_box_scores.game_id), 0), 0) AS avg_yards_per_game')
            )
            ->when($teamFilter, function ($q) use ($teamFilter) {
                $q->where('nfl_player_stats.team_abv', $teamFilter);
            })
            ->groupBy('nfl_player_stats.long_name', 'nfl_player_stats.team_abv')
            ->orderBy('total_receiving_yards', 'desc')
            ->paginate(10);

        return $query;
    }

    // Modified Best Rushers Method
    public function bestRushers($teamFilter = null)
    {
        $query = DB::table('nfl_player_stats')
            ->join('nfl_box_scores', 'nfl_player_stats.game_id', '=', 'nfl_box_scores.game_id')
            ->join('nfl_team_schedules', 'nfl_box_scores.game_id', '=', 'nfl_team_schedules.game_id')
            ->where('nfl_team_schedules.season_type', 'Regular Season')
            ->whereNotNull('rushing')
            ->select(
                'nfl_player_stats.long_name',
                'nfl_player_stats.team_abv',
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.rushYds")) AS SIGNED)) AS total_rushing_yards'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.carries")) AS UNSIGNED)) AS total_attempts'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.rushTD")) AS UNSIGNED)) AS total_rushing_TDs'),
                DB::raw('COUNT(DISTINCT nfl_box_scores.game_id) AS games_played'),
                DB::raw('COALESCE(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.rushYds")) AS SIGNED)) / NULLIF(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.carries")) AS UNSIGNED)), 0), 0) AS average_yards_per_attempt'),
                DB::raw('COALESCE(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.rushYds")) AS SIGNED)) / NULLIF(COUNT(DISTINCT nfl_box_scores.game_id), 0), 0) AS avg_yards_per_game')
            )
            ->when($teamFilter, function ($q) use ($teamFilter) {
                $q->where('nfl_player_stats.team_abv', $teamFilter);
            })
            ->groupBy('nfl_player_stats.long_name', 'nfl_player_stats.team_abv')
            ->orderBy('total_rushing_yards', 'desc')
            ->paginate(10);

        return $query;
    }

    public function bestTacklers($teamFilter = null)
    {
        $query = DB::table('nfl_player_stats')
            ->join('nfl_box_scores', 'nfl_player_stats.game_id', '=', 'nfl_box_scores.game_id')
            ->join('nfl_team_schedules', 'nfl_box_scores.game_id', '=', 'nfl_team_schedules.game_id')
            ->where('nfl_team_schedules.season_type', 'Regular Season')
            ->whereNotNull('defense')
            ->select(
                'nfl_player_stats.long_name',
                'nfl_player_stats.team_abv',
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.totalTackles")) AS UNSIGNED)) AS total_tackles'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.sacks")) AS UNSIGNED)) AS total_sacks'),
                DB::raw('COALESCE(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.totalTackles")) AS UNSIGNED)) / NULLIF(COUNT(DISTINCT nfl_box_scores.game_id), 0), 0) AS avg_tackles_per_game'),
                DB::raw('COALESCE(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.sacks")) AS UNSIGNED)) / NULLIF(COUNT(DISTINCT nfl_box_scores.game_id), 0), 0) AS avg_sacks_per_game')
            )
            ->when($teamFilter, function ($q) use ($teamFilter) {
                $q->where('nfl_player_stats.team_abv', $teamFilter);
            })
            ->groupBy('nfl_player_stats.long_name', 'nfl_player_stats.team_abv')
            ->orderBy('total_tackles', 'desc')
            ->paginate(10);

        return $query;
    }

    // Average Points Query
    protected function getAveragePoints($teamFilter = null)
    {
        $sql = "
        WITH team_scores AS (
            SELECT
                scores.game_id, 
                scores.team_abv,
                scores.location_type,
                scores.Q1,
                scores.Q2,
                scores.Q3,
                scores.Q4,
                scores.totalPts
            FROM (
                SELECT
                    b.game_id,
                    b.home_team AS team_abv,
                    'home' AS location_type,
                    JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, '$.Q1')) AS Q1,
                    JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, '$.Q2')) AS Q2,
                    JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, '$.Q3')) AS Q3,
                    JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, '$.Q4')) AS Q4,
                    b.home_points AS totalPts
                FROM nfl_box_scores b
                INNER JOIN nfl_team_schedules s ON b.game_id = s.game_id
                WHERE s.season_type = 'Regular Season'
                AND (b.home_team = ? OR ? IS NULL)

                UNION ALL

                SELECT
                    b.game_id,
                    b.away_team AS team_abv,
                    'away' AS location_type,
                    JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, '$.Q1')) AS Q1,
                    JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, '$.Q2')) AS Q2,
                    JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, '$.Q3')) AS Q3,
                    JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, '$.Q4')) AS Q4,
                    b.away_points AS totalPts
                FROM nfl_box_scores b
                INNER JOIN nfl_team_schedules s ON b.game_id = s.game_id
                WHERE s.season_type = 'Regular Season'
                AND (b.away_team = ? OR ? IS NULL)
            ) scores
        )
        SELECT
            team_abv,
            location_type,
            AVG(CAST(Q1 AS UNSIGNED)) AS avg_Q1_points,
            AVG(CAST(Q2 AS UNSIGNED)) AS avg_Q2_points,
            AVG(CAST(Q3 AS UNSIGNED)) AS avg_Q3_points,
            AVG(CAST(Q4 AS UNSIGNED)) AS avg_Q4_points,
            AVG((CAST(Q1 AS UNSIGNED) + CAST(Q2 AS UNSIGNED)) ) AS avg_first_half_points,
            AVG((CAST(Q3 AS UNSIGNED) + CAST(Q4 AS UNSIGNED)) ) AS avg_second_half_points,
            AVG(CAST(totalPts AS UNSIGNED)) AS avg_total_points
        FROM team_scores
        GROUP BY team_abv, location_type
        ";

        // Always provide parameters to match placeholders
        $parameters = [$teamFilter, $teamFilter, $teamFilter, $teamFilter];
        return DB::select($sql, $parameters);
    }
}
