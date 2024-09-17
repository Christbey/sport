<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeamStatsController extends Controller
{
    // Method to display the view with all query options and filter form
    public function index()
    {
        // You can list all possible queries here
        $queries = [
            'average_points' => 'Average Points by Quarter',
            'total_points' => 'Total Points',
            // Add more queries here
        ];

        return view('nfl.stats.index', compact('queries'));
    }

    // Method to fetch the stats based on the selected query and filter
    public function getStats(Request $request)
    {
        $queryType = $request->input('query');
        $teamFilter = $request->input('team');

        switch ($queryType) {
            case 'average_points':
                $data = $this->getAveragePoints($teamFilter);
                $tableHeadings = ['Team', 'Q1', 'Q2', 'Q3', 'Q4', 'First Half', 'Second Half', 'Total Points', 'Location Type'];
                break;

            case 'total_points':
                $data = $this->getTotalPoints($teamFilter);
                $tableHeadings = ['Team', 'Total Points', 'Location Type'];
                break;

            default:
                $data = [];
                $tableHeadings = [];
                break;
        }

        return view('nfl.stats.show', compact('data', 'tableHeadings'));
    }

    // Query to get average points
    protected function getAveragePoints($teamFilter = null)
    {
        $sql = "
        WITH team_scores AS (
            SELECT
                game_id, 
                home_team AS team_abv,
                JSON_UNQUOTE(JSON_EXTRACT(home_line_score, '$.Q1')) AS Q1,
                JSON_UNQUOTE(JSON_EXTRACT(home_line_score, '$.Q2')) AS Q2,
                JSON_UNQUOTE(JSON_EXTRACT(home_line_score, '$.Q3')) AS Q3,
                JSON_UNQUOTE(JSON_EXTRACT(home_line_score, '$.Q4')) AS Q4,
                home_points AS totalPts,
                location,
                'home' AS location_type
            FROM nfl_box_scores
            UNION ALL
            SELECT
                game_id,
                away_team AS team_abv,
                JSON_UNQUOTE(JSON_EXTRACT(away_line_score, '$.Q1')) AS Q1,
                JSON_UNQUOTE(JSON_EXTRACT(away_line_score, '$.Q2')) AS Q2,
                JSON_UNQUOTE(JSON_EXTRACT(away_line_score, '$.Q3')) AS Q3,
                JSON_UNQUOTE(JSON_EXTRACT(away_line_score, '$.Q4')) AS Q4,
                away_points AS totalPts,
                location,
                'away' AS location_type
            FROM nfl_box_scores
        )
        SELECT
            team_abv,
            AVG(CAST(Q1 AS UNSIGNED)) AS avg_Q1_points,
            AVG(CAST(Q2 AS UNSIGNED)) AS avg_Q2_points,
            AVG(CAST(Q3 AS UNSIGNED)) AS avg_Q3_points,
            AVG(CAST(Q4 AS UNSIGNED)) AS avg_Q4_points,
            AVG((CAST(Q1 AS UNSIGNED) + CAST(Q2 AS UNSIGNED)) / 2) AS avg_first_half_points,
            AVG((CAST(Q3 AS UNSIGNED) + CAST(Q4 AS UNSIGNED)) / 2) AS avg_second_half_points,
            AVG(CAST(totalPts AS UNSIGNED)) AS avg_total_points,
            location_type
        FROM team_scores
        GROUP BY team_abv, location_type
    ";

        // If team filter is applied, append it to the query
        if ($teamFilter) {
            $sql .= ' HAVING team_abv = ?';
            return DB::select($sql, [$teamFilter]);
        }

        return DB::select($sql);
    }

    // Query to get total points (you can add more queries as needed)
    protected function getTotalPoints($teamFilter = null)
    {
        $query = DB::table('nfl_box_scores')
            ->selectRaw('home_team AS team_abv, SUM(home_points) AS total_points, "home" AS location_type')
            ->groupBy('home_team')
            ->unionAll(
                DB::table('nfl_box_scores')
                    ->selectRaw('away_team AS team_abv, SUM(away_points) AS total_points, "away" AS location_type')
                    ->groupBy('away_team')
            );

        if ($teamFilter) {
            $query->where('team_abv', $teamFilter);
        }

        return $query->get();
    }

    public function bestReceivers()
    {
        $receivers = DB::table('nfl_player_stats')
            ->select('long_name', 'team_abv', DB::raw('JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.recYds")) AS receiving_yards'))
            ->whereNotNull('receiving')
            ->orderBy(DB::raw('CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.recYds")) AS UNSIGNED)'), 'desc')
            ->limit(10)
            ->get();

        return view('nfl.stats.best_receivers', compact('receivers'));
    }

}

