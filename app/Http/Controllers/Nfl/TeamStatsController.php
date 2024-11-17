<?php

namespace App\Http\Controllers\Nfl;

use App\Http\Controllers\Controller;
use App\Models\Nfl\NflTeamSchedule;
use App\Models\Nfl\NflTeamStat;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TeamStatsController extends Controller
{
    // Method to display the view with all query options and filter form
    public function index()
    {
        $queries = [
            'average_points' => 'Average Points by Quarter',
            'quarter_scoring' => 'Quarter-by-Quarter Analysis',
            'half_scoring' => 'Half-by-Half Scoring Analysis',
            'score_margins' => 'Score Margins Analysis',
            'quarter_comebacks' => 'Comeback Analysis',
            'scoring_streaks' => 'Scoring Streaks Analysis',
            'bestReceivers' => 'Best Receivers',
            'bestRushers' => 'Best Rushers',
            'bestTacklers' => 'Best Tacklers',
            'big_playmakers' => 'Big Play Analysis',
            'defensive_playmakers' => 'Defensive Playmaker Analysis',
            'dual_threat' => 'Dual-Threat Player Analysis',
            'offensive_consistency' => 'Most Consistent Offensive Players',
            'nfl_team_stats' => 'NFL Team Stats',
            'team_analytics' => 'Team Analytics',
            'over_under_analysis' => 'Over/Under Betting Analysis',
            'team_matchup_edge' => 'Team Matchup Edge Analysis',
            'first_half_trends' => 'First Half Betting Trends',
            'team_vs_conference' => 'Team Performance vs Conference',
            'team_vs_division' => 'Team Performance vs Division',
            'player_vs_conference' => 'Player Stats vs Conference',
            'player_vs_division' => 'Player Stats vs Division',
            'conference_stats' => 'Conference Stats',
            'division_stats' => 'Division Stats'

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
                $tableHeadings = ['Team', 'Location Type', 'Q1', 'Q2', 'Q3', 'Q4', 'First Half', 'Second Half', 'Total Points'];
                $players = null;
                break;

            case 'half_scoring':
                $data = $this->getHalfScoring($teamFilter);
                $tableHeadings = ['Team', 'Location Type', 'First Half Avg', 'Second Half Avg', 'First Half Stronger %', 'Games Played'];
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

            case 'quarter_scoring':
                $data = $this->getQuarterScoring($teamFilter);
                $tableHeadings = [
                    'Team',
                    'Location',
                    'Q1 Avg',
                    'Q2 Avg',
                    'Q3 Avg',
                    'Q4 Avg',
                    'Games',
                    'Q1 Scoring %',
                    'Q2 Scoring %',
                    'Q3 Scoring %',
                    'Q4 Scoring %',
                    'Best Qtr Avg',
                    'Best Qtr',
                    'Worst Qtr Avg',
                    'Worst Qtr'
                ];
                $players = null;
                break;

            // In getStats method
            case 'score_margins':
                $data = $this->getScoreMargins($teamFilter);
                $tableHeadings = ['Team', 'Location', 'Games', 'Avg Margin', 'Largest Win', 'Largest Loss', 'Wins', 'Losses', 'Avg Win Margin', 'Avg Loss Margin', 'One Score Games', 'Win %'];
                $players = null;
                break;

            case 'quarter_comebacks':
                $data = $this->getQuarterComebacks($teamFilter);
                $tableHeadings = [
                    'Team',
                    'Total Games',
                    'Comeback Wins',
                    'Blown Leads',
                    'Avg 4th Qtr Points',
                    'Avg Comeback 4th Qtr Points'
                ];
                $players = null;
                break;

            case 'scoring_streaks':
                $data = $this->getScoringStreaks($teamFilter);
                $tableHeadings = ['Team', 'Games', 'Scored Every Quarter', 'Last Quarter Only', 'Highest Quarter Score'];
                $players = null;
                break;


            // In getStats method
            case 'big_playmakers':
                $data = $this->getBigPlaymakers($teamFilter);
                $tableHeadings = [
                    'Player', 'Team', 'Games', 'Longest Reception', 'Longest Rush',
                    'Receiving Yards', 'Rushing Yards', 'Receptions', 'Carries',
                    'Yards/Game', '20+ Yard Plays', 'Total Yards'

                ];
                $players = null;
                break;

            case 'defensive_playmakers':
                $data = $this->getDefensivePlaymakers($teamFilter);
                $tableHeadings = [
                    'Player', 'Team', 'Games', 'Total Tackles', 'Solo Tackles',
                    'Sacks', 'INTs', 'Pass Deflections', 'Forced Fumbles',
                    'TFLs', 'Tackles/Game', 'Impact Plays'
                ];
                $players = null;
                break;

            case 'dual_threat':
                $data = $this->getDualThreatPlayers($teamFilter);
                $tableHeadings = [
                    'Player', 'Team', 'Games', 'Receiving Yards', 'Rushing Yards',
                    'Receptions', 'Carries', 'Receiving TDs', 'Rushing TDs',
                    'Yards/Reception', 'Yards/Carry', 'Total Yards/Game'
                ];
                $players = null;
                break;

            case 'offensive_consistency':
                $data = $this->getOffensiveConsistency($teamFilter);
                $tableHeadings = [
                    'Player',
                    'Team',
                    'Games',
                    'Avg Yards/Game',
                    'Avg Touches/Game',
                    'Avg TDs/Game',
                    'Yards StdDev',
                    'Touches StdDev',
                    'Yards CV%',
                    'Total Yards',
                    'Total Touches',
                    'Total TDs',
                    '50+ Yard Games %',
                    'Min Yards',
                    'Max Yards',
                    'Above Floor %',
                    'Consistency Rating',
                    'Reliability Score'
                ];
                $players = null;
                break;

            case 'nfl_team_stats':
                $data = $this->getNflTeamStats($teamFilter);
                $tableHeadings = [
                    'id',
                    'Team',
                    'Games',
                    'Avg Total Yards',
                    'Avg Rush Yards',
                    'Avg Pass Yards',
                    'Rush %',
                    'Pass %',
                    'Consistency',
                    'Rush Consistency',
                    'Pass Consistency',
                    'Best Game',
                    'Worst Game',
                    'Explosive %',
                    'Best Rush',
                    'Worst Rush',
                    'Strong Rush %',
                    'Best Pass',
                    'Worst Pass',
                    'Strong Pass %',
                    'Rating',
                    'Trend'
                ];
                $players = null; // Added this line to match other cases

                break;

            case 'team_analytics':
                if ($teamFilter) {
                    $teamStats = $this->calculateTeamAnalytics((int)$teamFilter);

                    $data = [[
                        'Team' => $teamStats['team_abv'] ?? 'N/A',
                        'Games' => $teamStats['sample_size'],
                        'Pass YPG' => number_format($teamStats['offensive_stats']['yards_per_game']['passing'], 1),
                        'Yards/Play' => number_format($teamStats['offensive_stats']['efficiency']['yards_per_play'], 2),
                        'Rush Y/A' => number_format($teamStats['offensive_stats']['efficiency']['rushing_yards_per_attempt'], 2),
                        'Pass Y/A' => number_format($teamStats['offensive_stats']['efficiency']['passing_yards_per_attempt'], 2),
                        'Rush %' => number_format($teamStats['offensive_stats']['play_distribution']['rushing_percentage'], 1) . '%',
                        'Pass %' => number_format($teamStats['offensive_stats']['play_distribution']['passing_percentage'], 1) . '%',
                        'Total CV' => number_format($teamStats['consistency_metrics']['total_yards']['coefficient_of_variation'], 1) . '%',
                        'Rush CV' => number_format($teamStats['consistency_metrics']['rushing_yards']['coefficient_of_variation'], 1) . '%',
                        'Pass CV' => number_format($teamStats['consistency_metrics']['passing_yards']['coefficient_of_variation'], 1) . '%',
                        'Total Trend' => $teamStats['performance_trends']['total_yards']['regression']['trend_direction'] ?? 'N/A',
                        'Rush Trend' => $teamStats['performance_trends']['rushing_yards']['regression']['trend_direction'] ?? 'N/A',
                        'Pass Trend' => $teamStats['performance_trends']['passing_yards']['regression']['trend_direction'] ?? 'N/A',
                        'Rating' => $teamStats['situation_analysis']['home_performance']['performance_rating'] ?? 'Developing',
                        'SOS' => $teamStats['opponent_adjusted']['strength_of_schedule']['difficulty'] ?? 'N/A'
                    ]];
                } else {
                    // Get all teams' analytics
                    $teams = NflTeamStat::select('team_id', 'team_abv')
                        ->distinct('team_id')
                        ->whereNotNull('team_id')
                        ->get();

                    $data = [];
                    foreach ($teams as $team) {
                        $teamStats = $this->calculateTeamAnalytics($team->team_id);

                        if ($teamStats['sample_size'] > 0) {
                            $data[] = [
                                'Team' => $team->team_abv,
                                'Games' => $teamStats['sample_size'],
                                'Pass YPG' => number_format($teamStats['offensive_stats']['yards_per_game']['passing'], 1),
                                'Yards/Play' => number_format($teamStats['offensive_stats']['efficiency']['yards_per_play'], 2),
                                'Rush Y/A' => number_format($teamStats['offensive_stats']['efficiency']['rushing_yards_per_attempt'], 2),
                                'Pass Y/A' => number_format($teamStats['offensive_stats']['efficiency']['passing_yards_per_attempt'], 2),
                                'Rush %' => number_format($teamStats['offensive_stats']['play_distribution']['rushing_percentage'], 1) . '%',
                                'Pass %' => number_format($teamStats['offensive_stats']['play_distribution']['passing_percentage'], 1) . '%',
                                'Total CV' => number_format($teamStats['consistency_metrics']['total_yards']['coefficient_of_variation'], 1) . '%',
                                'Rush CV' => number_format($teamStats['consistency_metrics']['rushing_yards']['coefficient_of_variation'], 1) . '%',
                                'Pass CV' => number_format($teamStats['consistency_metrics']['passing_yards']['coefficient_of_variation'], 1) . '%',
                                'Total Trend' => $teamStats['performance_trends']['total_yards']['regression']['trend_direction'] ?? 'N/A',
                                'Rush Trend' => $teamStats['performance_trends']['rushing_yards']['regression']['trend_direction'] ?? 'N/A',
                                'Pass Trend' => $teamStats['performance_trends']['passing_yards']['regression']['trend_direction'] ?? 'N/A',
                                'Rating' => $teamStats['situation_analysis']['home_performance']['performance_rating'] ?? 'Developing',
                                'SOS' => $teamStats['opponent_adjusted']['strength_of_schedule']['difficulty'] ?? 'N/A'
                            ];
                        }
                    }
                }

                $tableHeadings = array_keys(reset($data));
                $players = null;
                break;
            // Add these cases to the switch statement:
            case 'over_under_analysis':
                $data = $this->getOverUnderAnalysis($teamFilter);
                $tableHeadings = [
                    'Matchup',
                    'Games Analyzed',
                    'Avg Total Points',
                    'Avg First Half Points',
                    'Avg Fourth Quarter Points',
                    'Highest Combined Score',
                    'Lowest Combined Score',
                    'Points Variance',
                    'Games Over 44.5%',
                    'First Half Over 21.5%',
                    'Correlated Overs',
                    'Projected Pace'
                ];
                $players = null;
                break;

            case 'team_matchup_edge':
                $data = $this->getTeamMatchupEdge($teamFilter);
                $tableHeadings = [
                    'Team',
                    'Opponent',
                    'Game Date',
                    'Total Yards',
                    'Rush Yards',
                    'Pass Yards',
                    'Points Scored',
                    'Points Allowed',
                    'Yards Differential',
                    'Points Differential',
                    'Win Probability',
                    'Edge Score'
                ];
                $players = null;
                break;
            case 'first_half_trends':
                $data = $this->getFirstHalfTendencies($teamFilter);
                $tableHeadings = [
                    'Team',
                    'Games Analyzed',
                    'Avg First Half Points',
                    'Avg Opponent First Half Points',
                    'Avg First Half Total',
                    'First Half Points StdDev',
                    'First Half Win %',
                    'First Half Over %',
                    'Max First Half Points',
                    'Min First Half Points'
                ];
                $players = null;
                break;
            case 'team_vs_conference':
                $data = $this->getTeamVsConference($teamFilter);
                $tableHeadings = [
                    'Team',
                    'Conf',
                    'Conference',
                    'Games Played',
                    'Avg Points For',
                    'Avg Points Against',
                    'Avg Yards For',
                    'Avg Yards Against',
                    'Wins',
                    'Losses',
                    'Avg Margin',
                    'Win %',
                    'Points StdDev',
                    'Yards StdDev'
                ];
                $players = null;
                break;

            case 'team_vs_division':
                $data = $this->getTeamVsDivision($teamFilter);
                $tableHeadings = [
                    'Team',
                    'Conf',
                    'Conference',
                    'Division',
                    'Games Played',
                    'Avg Points For',
                    'Avg Points Against',
                    'Avg Yards For',
                    'Avg Yards Against',
                    'Wins',
                    'Losses',
                    'Avg Margin',
                    'Win %',
                    'Points StdDev',
                    'Yards StdDev'
                ];
                $players = null;
                break;
            case 'player_vs_conference':
                $data = $this->getPlayerVsConference($teamFilter);
                $tableHeadings = [
                    'Player',
                    'Team',
                    'Conf',
                    'Conference',
                    'Games Played',
                    'Avg Receiving Yards',
                    'Avg Rushing Yards',
                    'Receiving TDs',
                    'Rushing TDs',
                    'Avg Tackles',
                    'Total Sacks',
                    'Total INTs',
                    'Receiving Yards StdDev',
                    'Rushing Yards StdDev'
                ];
                $players = null;
                break;
            case 'player_vs_division':
                $data = $this->getPlayerVsDivision($teamFilter);
                $tableHeadings = [
                    'Player',
                    'Team',
                    'Conf',
                    'Conference',
                    'Division',
                    'Games Played',
                    'Avg Receiving Yards',
                    'Avg Rushing Yards',
                    'Receiving TDs',
                    'Rushing TDs',
                    'Avg Tackles',
                    'Total Sacks',
                    'Total INTs',
                    'Receiving Yards StdDev',
                    'Rushing Yards StdDev'
                ];
                $players = null;
                break;
            case 'conference_stats':
                $data = $this->getConferenceStats($teamFilter);
                $tableHeadings = [
                    'Conference Abv',
                    'Conference',
                    'Total Games',
                    'Avg Yards',
                    'Avg Points',
                    'Yards StdDev',
                    'Rush %',
                    'Conference Rating'
                ];
                $players = null;
                break;

            case 'division_stats':
                $data = $this->getDivisionStats($teamFilter);
                $tableHeadings = [
                    'Conference Abv',
                    'Conference',
                    'Division',
                    'Total Games',
                    'Avg Yards',
                    'Avg Points',
                    'Yards StdDev',
                    'Rush %',
                    'Teams in Division',
                    'Division Rating'
                ];
                $players = null;
                break;
        }

        return view('nfl.stats.show', compact('data', 'tableHeadings', 'players'))
            ->with('query', $queryType)
            ->with('team', $teamFilter);
    }

    protected function getAveragePoints(?string $teamFilter = null): array
    {
        $baseQuery = "
        SELECT
            b.game_id,
            :%team_column AS team_abv,
            :%location AS location_type,
            JSON_UNQUOTE(JSON_EXTRACT(:%score_column, '$.Q1')) AS Q1,
            JSON_UNQUOTE(JSON_EXTRACT(:%score_column, '$.Q2')) AS Q2,
            JSON_UNQUOTE(JSON_EXTRACT(:%score_column, '$.Q3')) AS Q3,
            JSON_UNQUOTE(JSON_EXTRACT(:%score_column, '$.Q4')) AS Q4,
            :%points_column AS totalPts
        FROM nfl_box_scores b
        INNER JOIN nfl_team_schedules s ON b.game_id = s.game_id
        WHERE s.season_type = 'Regular Season'
        AND (:%team_column = ? OR ? IS NULL)
    ";

        $homeQuery = strtr($baseQuery, [
            ':%team_column' => 'b.home_team',
            ':%location' => "'home'",
            ':%score_column' => 'b.home_line_score',
            ':%points_column' => 'b.home_points'
        ]);

        $awayQuery = strtr($baseQuery, [
            ':%team_column' => 'b.away_team',
            ':%location' => "'away'",
            ':%score_column' => 'b.away_line_score',
            ':%points_column' => 'b.away_points'
        ]);

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
            {$homeQuery}
            UNION ALL
            {$awayQuery}
        ) scores
    )
    SELECT
        team_abv,
        location_type,
        " . $this->generateQuarterAverages() . ',
        ' . $this->generateHalfAverages() . ',
        AVG(CAST(totalPts AS UNSIGNED)) AS avg_total_points
    FROM team_scores
    GROUP BY team_abv, location_type
    ';

        return DB::select($sql, array_fill(0, 4, $teamFilter));
    }

    protected function generateQuarterAverages(): string
    {
        $quarters = ['Q1', 'Q2', 'Q3', 'Q4'];
        return implode(',', array_map(function ($quarter) {
            return "AVG(CAST({$quarter} AS UNSIGNED)) AS avg_{$quarter}_points";
        }, $quarters));
    }

    protected function generateHalfAverages(): string
    {
        return '
        AVG((CAST(Q1 AS UNSIGNED) + CAST(Q2 AS UNSIGNED))) AS avg_first_half_points,
        AVG((CAST(Q3 AS UNSIGNED) + CAST(Q4 AS UNSIGNED))) AS avg_second_half_points
    ';
    }

    public function getHalfScoring(?string $teamFilter = null)
    {
        $baseQuery = "
        SELECT
            b.game_id,
            :%team_column AS team_abv,
            :%location AS location_type,
            JSON_UNQUOTE(JSON_EXTRACT(:%score_column, '$.Q1')) AS Q1,
            JSON_UNQUOTE(JSON_EXTRACT(:%score_column, '$.Q2')) AS Q2,
            JSON_UNQUOTE(JSON_EXTRACT(:%score_column, '$.Q3')) AS Q3,
            JSON_UNQUOTE(JSON_EXTRACT(:%score_column, '$.Q4')) AS Q4
        FROM nfl_box_scores b
        INNER JOIN nfl_team_schedules s ON b.game_id = s.game_id
        WHERE s.season_type = 'Regular Season'
        AND (:%team_column = ? OR ? IS NULL)
    ";

        $homeQuery = strtr($baseQuery, [
            ':%team_column' => 'b.home_team',
            ':%location' => "'home'",
            ':%score_column' => 'b.home_line_score'
        ]);

        $awayQuery = strtr($baseQuery, [
            ':%team_column' => 'b.away_team',
            ':%location' => "'away'",
            ':%score_column' => 'b.away_line_score'
        ]);

        $sql = "
    WITH team_scores AS (
        SELECT
            scores.team_abv,
            scores.location_type,
            scores.Q1,
            scores.Q2,
            scores.Q3,
            scores.Q4
        FROM (
            {$homeQuery}
            UNION ALL
            {$awayQuery}
        ) scores
    )
    SELECT
        team_abv,
        location_type,
        ROUND(AVG(CAST(Q1 AS UNSIGNED) + CAST(Q2 AS UNSIGNED)), 1) AS avg_first_half_points,
        ROUND(AVG(CAST(Q3 AS UNSIGNED) + CAST(Q4 AS UNSIGNED)), 1) AS avg_second_half_points,
        ROUND(AVG(
            CASE 
                WHEN CAST(Q1 AS UNSIGNED) + CAST(Q2 AS UNSIGNED) > CAST(Q3 AS UNSIGNED) + CAST(Q4 AS UNSIGNED) THEN 1
                ELSE 0
            END
        ) * 100, 1) AS first_half_stronger_percentage,
        COUNT(*) as games_played
    FROM team_scores
    GROUP BY team_abv, location_type
    ORDER BY avg_first_half_points DESC
    ";

        return DB::select($sql, array_fill(0, 4, $teamFilter));
    }

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

    public function getQuarterScoring(?string $teamFilter = null)
    {
        $baseQuery = "
        SELECT
            b.game_id,
            :%team_column AS team_abv,
            :%location AS location_type,
            JSON_UNQUOTE(JSON_EXTRACT(:%score_column, '$.Q1')) AS Q1,
            JSON_UNQUOTE(JSON_EXTRACT(:%score_column, '$.Q2')) AS Q2,
            JSON_UNQUOTE(JSON_EXTRACT(:%score_column, '$.Q3')) AS Q3,
            JSON_UNQUOTE(JSON_EXTRACT(:%score_column, '$.Q4')) AS Q4
        FROM nfl_box_scores b
        INNER JOIN nfl_team_schedules s ON b.game_id = s.game_id
        WHERE s.season_type = 'Regular Season'
        AND (:%team_column = ? OR ? IS NULL)
    ";

        $homeQuery = strtr($baseQuery, [
            ':%team_column' => 'b.home_team',
            ':%location' => "'home'",
            ':%score_column' => 'b.home_line_score'
        ]);

        $awayQuery = strtr($baseQuery, [
            ':%team_column' => 'b.away_team',
            ':%location' => "'away'",
            ':%score_column' => 'b.away_line_score'
        ]);

        $sql = "
    WITH team_scores AS (
        SELECT
            scores.team_abv,
            scores.location_type,
            CAST(scores.Q1 AS UNSIGNED) as Q1,
            CAST(scores.Q2 AS UNSIGNED) as Q2,
            CAST(scores.Q3 AS UNSIGNED) as Q3,
            CAST(scores.Q4 AS UNSIGNED) as Q4
        FROM (
            {$homeQuery}
            UNION ALL
            {$awayQuery}
        ) scores
    ),
    quarter_stats AS (
        SELECT
            team_abv,
            location_type,
            ROUND(AVG(Q1), 1) as avg_Q1_points,
            ROUND(AVG(Q2), 1) as avg_Q2_points,
            ROUND(AVG(Q3), 1) as avg_Q3_points,
            ROUND(AVG(Q4), 1) as avg_Q4_points,
            COUNT(*) as games_played,
            ROUND(100 * SUM(CASE WHEN Q1 > 0 THEN 1 ELSE 0 END) / COUNT(*), 1) as Q1_scoring_pct,
            ROUND(100 * SUM(CASE WHEN Q2 > 0 THEN 1 ELSE 0 END) / COUNT(*), 1) as Q2_scoring_pct,
            ROUND(100 * SUM(CASE WHEN Q3 > 0 THEN 1 ELSE 0 END) / COUNT(*), 1) as Q3_scoring_pct,
            ROUND(100 * SUM(CASE WHEN Q4 > 0 THEN 1 ELSE 0 END) / COUNT(*), 1) as Q4_scoring_pct
        FROM team_scores
        GROUP BY team_abv, location_type
    )
    SELECT
        team_abv,
        location_type,
        avg_Q1_points,
        avg_Q2_points,
        avg_Q3_points,
        avg_Q4_points,
        games_played,
        Q1_scoring_pct,
        Q2_scoring_pct,
        Q3_scoring_pct,
        Q4_scoring_pct,
        GREATEST(avg_Q1_points, avg_Q2_points, avg_Q3_points, avg_Q4_points) as best_quarter_avg,
        CASE 
            WHEN avg_Q1_points >= GREATEST(avg_Q2_points, avg_Q3_points, avg_Q4_points) THEN 'Q1'
            WHEN avg_Q2_points >= GREATEST(avg_Q1_points, avg_Q3_points, avg_Q4_points) THEN 'Q2'
            WHEN avg_Q3_points >= GREATEST(avg_Q1_points, avg_Q2_points, avg_Q4_points) THEN 'Q3'
            ELSE 'Q4'
        END as strongest_quarter,
        LEAST(avg_Q1_points, avg_Q2_points, avg_Q3_points, avg_Q4_points) as worst_quarter_avg,
        CASE 
            WHEN avg_Q1_points <= LEAST(avg_Q2_points, avg_Q3_points, avg_Q4_points) THEN 'Q1'
            WHEN avg_Q2_points <= LEAST(avg_Q1_points, avg_Q3_points, avg_Q4_points) THEN 'Q2'
            WHEN avg_Q3_points <= LEAST(avg_Q1_points, avg_Q2_points, avg_Q4_points) THEN 'Q3'
            ELSE 'Q4'
        END as weakest_quarter
    FROM quarter_stats
    ORDER BY (avg_Q1_points + avg_Q2_points + avg_Q3_points + avg_Q4_points) DESC
    ";

        return DB::select($sql, array_fill(0, 4, $teamFilter));
    }

    public function getScoreMargins(?string $teamFilter = null)
    {
        $sql = "
        WITH game_margins AS (
            SELECT 
                CASE 
                    WHEN b.home_team = ? OR ? IS NULL THEN b.home_team
                    ELSE b.away_team 
                END as team_abv,
                CASE 
                    WHEN b.home_team = ? OR ? IS NULL THEN 'home'
                    ELSE 'away' 
                END as location_type,
                CASE 
                    WHEN b.home_team = ? OR ? IS NULL THEN b.home_points - b.away_points
                    ELSE b.away_points - b.home_points 
                END as point_margin,
                CASE 
                    WHEN b.home_team = ? OR ? IS NULL THEN 
                        CASE WHEN b.home_points > b.away_points THEN 'W' ELSE 'L' END
                    ELSE 
                        CASE WHEN b.away_points > b.home_points THEN 'W' ELSE 'L' END
                END as result
            FROM nfl_box_scores b
            JOIN nfl_team_schedules s ON b.game_id = s.game_id
            WHERE s.season_type = 'Regular Season'
            AND (b.home_team = ? OR b.away_team = ? OR ? IS NULL)
        )
        SELECT 
            team_abv,
            location_type,
            COUNT(*) as games_played,
            ROUND(AVG(point_margin), 1) as avg_margin,
            MAX(point_margin) as largest_win_margin,
            MIN(point_margin) as largest_loss_margin,
            SUM(CASE WHEN point_margin > 0 THEN 1 ELSE 0 END) as wins,
            SUM(CASE WHEN point_margin < 0 THEN 1 ELSE 0 END) as losses,
            ROUND(AVG(CASE WHEN result = 'W' THEN point_margin END), 1) as avg_margin_in_wins,
            ROUND(AVG(CASE WHEN result = 'L' THEN point_margin END), 1) as avg_margin_in_losses,
            SUM(CASE WHEN ABS(point_margin) <= 7 THEN 1 ELSE 0 END) as one_score_games,
            ROUND(SUM(CASE WHEN point_margin > 0 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as win_percentage
        FROM game_margins
        GROUP BY team_abv, location_type
        ORDER BY avg_margin DESC
        ";

        return DB::select($sql, array_fill(0, 11, $teamFilter));
    }

    public function getQuarterComebacks(?string $teamFilter = null)
    {
        $sql = "
    WITH quarter_scores AS (
        SELECT 
            b.game_id,
            b.home_team,
            b.away_team,
            CAST(JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, '$.Q1')) AS UNSIGNED) +
            CAST(JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, '$.Q2')) AS UNSIGNED) +
            CAST(JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, '$.Q3')) AS UNSIGNED) as home_thru_3,
            CAST(JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, '$.Q1')) AS UNSIGNED) +
            CAST(JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, '$.Q2')) AS UNSIGNED) +
            CAST(JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, '$.Q3')) AS UNSIGNED) as away_thru_3,
            b.home_points as home_final,
            b.away_points as away_final,
            CAST(JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, '$.Q4')) AS UNSIGNED) as home_q4,
            CAST(JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, '$.Q4')) AS UNSIGNED) as away_q4
        FROM nfl_box_scores b
        JOIN nfl_team_schedules s ON b.game_id = s.game_id
        WHERE s.season_type = 'Regular Season'
        AND (b.home_team = ? OR b.away_team = ? OR ? IS NULL)
    ),
    team_stats AS (
        SELECT 
            home_team as team,
            'home' as location,
            home_thru_3 as team_thru_3,
            away_thru_3 as opp_thru_3,
            home_final as team_final,
            away_final as opp_final,
            home_q4 as team_q4
        FROM quarter_scores
        WHERE home_team = ? OR ? IS NULL
        
        UNION ALL
        
        SELECT 
            away_team as team,
            'away' as location,
            away_thru_3 as team_thru_3,
            home_thru_3 as opp_thru_3,
            away_final as team_final,
            home_final as opp_final,
            away_q4 as team_q4
        FROM quarter_scores
        WHERE away_team = ? OR ? IS NULL
    )
    SELECT 
        team as team_abv,
        COUNT(*) as total_games,
        SUM(CASE 
            WHEN team_thru_3 < opp_thru_3 AND team_final > opp_final THEN 1
            ELSE 0 
        END) as comeback_wins,
        SUM(CASE 
            WHEN team_thru_3 > opp_thru_3 AND team_final < opp_final THEN 1
            ELSE 0 
        END) as blown_leads,
        ROUND(AVG(team_q4), 1) as avg_fourth_quarter_points,
        ROUND(AVG(CASE 
            WHEN team_thru_3 < opp_thru_3 AND team_final > opp_final THEN team_q4
            ELSE NULL 
        END), 1) as avg_comeback_q4_points
    FROM team_stats
    GROUP BY team
    ORDER BY comeback_wins DESC
    ";

        return DB::select($sql, array_fill(0, 7, $teamFilter));
    }

    public function getScoringStreaks(?string $teamFilter = null)
    {
        $sql = "
        WITH consecutive_quarters AS (
            SELECT 
                b.game_id,
                CASE 
                    WHEN b.home_team = ? OR ? IS NULL THEN b.home_team
                    ELSE b.away_team 
                END as team_abv,
                CASE 
                    WHEN b.home_team = ? OR ? IS NULL THEN b.home_line_score
                    ELSE b.away_line_score 
                END as line_score
            FROM nfl_box_scores b
            JOIN nfl_team_schedules s ON b.game_id = s.game_id
            WHERE s.season_type = 'Regular Season'
            AND (b.home_team = ? OR b.away_team = ? OR ? IS NULL)
        )
        SELECT 
            team_abv,
            COUNT(*) as games_analyzed,
            SUM(CASE 
                WHEN CAST(JSON_UNQUOTE(JSON_EXTRACT(line_score, '$.Q1')) AS UNSIGNED) > 0 AND
                     CAST(JSON_UNQUOTE(JSON_EXTRACT(line_score, '$.Q2')) AS UNSIGNED) > 0 AND
                     CAST(JSON_UNQUOTE(JSON_EXTRACT(line_score, '$.Q3')) AS UNSIGNED) > 0 AND
                     CAST(JSON_UNQUOTE(JSON_EXTRACT(line_score, '$.Q4')) AS UNSIGNED) > 0
                THEN 1 ELSE 0 END) as games_scored_every_quarter,
            SUM(CASE 
                WHEN CAST(JSON_UNQUOTE(JSON_EXTRACT(line_score, '$.Q1')) AS UNSIGNED) = 0 AND
                     CAST(JSON_UNQUOTE(JSON_EXTRACT(line_score, '$.Q2')) AS UNSIGNED) = 0 AND
                     CAST(JSON_UNQUOTE(JSON_EXTRACT(line_score, '$.Q3')) AS UNSIGNED) = 0 AND
                     CAST(JSON_UNQUOTE(JSON_EXTRACT(line_score, '$.Q4')) AS UNSIGNED) > 0
                THEN 1 ELSE 0 END) as fourth_quarter_only_scores,
            MAX(
                GREATEST(
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(line_score, '$.Q1')) AS UNSIGNED),
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(line_score, '$.Q2')) AS UNSIGNED),
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(line_score, '$.Q3')) AS UNSIGNED),
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(line_score, '$.Q4')) AS UNSIGNED)
                )
            ) as highest_scoring_quarter
        FROM consecutive_quarters
        GROUP BY team_abv
        ORDER BY games_scored_every_quarter DESC
        ";

        return DB::select($sql, array_fill(0, 7, $teamFilter));
    }

    public function getBigPlaymakers(?string $teamFilter = null)
    {
        $sql = "
    WITH player_big_plays AS (
        SELECT 
            ps.long_name,
            ps.team_abv,
            CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.longRec')), '0') AS SIGNED) as longest_reception,
            CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.longRush')), '0') AS SIGNED) as longest_rush,
            CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.recYds')), '0') AS SIGNED) as receiving_yards,
            CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.receptions')), '0') AS SIGNED) as receptions,
            CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.rushYds')), '0') AS SIGNED) as rushing_yards,
            CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.carries')), '0') AS SIGNED) as carries
        FROM nfl_player_stats ps
        WHERE (receiving IS NOT NULL OR rushing IS NOT NULL)
        AND (ps.team_abv = ? OR ? IS NULL)
    )
    SELECT 
        long_name,
        team_abv,
        COUNT(*) as games_played,
        MAX(longest_reception) as longest_reception,
        MAX(longest_rush) as longest_rush,
        SUM(receiving_yards) as total_receiving_yards,
        SUM(rushing_yards) as total_rushing_yards,
        SUM(receptions) as total_receptions,
        SUM(carries) as total_carries,
        ROUND(
            (SUM(receiving_yards) + SUM(rushing_yards)) / COUNT(*), 
            1
        ) as avg_yards_per_game,
        COUNT(
            CASE 
                WHEN longest_reception >= 20 OR longest_rush >= 20 
                THEN 1 
            END
        ) as games_with_20plus_plays,
        SUM(receiving_yards + rushing_yards) as total_yards
    FROM player_big_plays
    GROUP BY long_name, team_abv
    HAVING (
        MAX(longest_reception) >= 20 
        OR MAX(longest_rush) >= 20
        OR total_yards > 0
    )
    ORDER BY games_with_20plus_plays DESC, total_yards DESC
    LIMIT 20
    ";

        return DB::select($sql, array_fill(0, 2, $teamFilter));
    }

    public function getDefensivePlaymakers(?string $teamFilter = null)
    {
        $sql = "
    SELECT 
        ps.long_name,
        ps.team_abv,
        COUNT(*) as games_played,
        SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.totalTackles')) AS UNSIGNED)) as total_tackles,
        SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.soloTackles')) AS UNSIGNED)) as solo_tackles,
        SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.sacks')) AS UNSIGNED)) as sacks,
        SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.defensiveInterceptions')) AS UNSIGNED)) as interceptions,
        SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.passDeflections')) AS UNSIGNED)) as pass_deflections,
        SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.forcedFumbles')) AS UNSIGNED)) as forced_fumbles,
        SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.tfl')) AS UNSIGNED)) as tackles_for_loss,
        ROUND(AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.totalTackles')) AS UNSIGNED)), 1) as avg_tackles_per_game,
        SUM(
            CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.sacks')) AS UNSIGNED) +
            CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.defensiveInterceptions')) AS UNSIGNED) +
            CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.forcedFumbles')) AS UNSIGNED)
        ) as impact_plays
    FROM nfl_player_stats ps
    WHERE defense IS NOT NULL
    AND (ps.team_abv = ? OR ? IS NULL)
    GROUP BY ps.long_name, ps.team_abv
    HAVING total_tackles > 0
    ORDER BY impact_plays DESC, total_tackles DESC
    LIMIT 20
    ";

        return DB::select($sql, array_fill(0, 2, $teamFilter));
    }

    public function getDualThreatPlayers(?string $teamFilter = null)
    {
        $sql = "
   WITH player_stats AS (
    SELECT 
        ps.long_name,
        ps.team_abv,
        CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.recYds')) AS DECIMAL(12,2)) as receiving_yards,
        CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.receptions')) AS DECIMAL(12,2)) as receptions,
        CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.recTD')) AS DECIMAL(12,2)) as receiving_tds,
        CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.rushYds')) AS DECIMAL(12,2)) as rushing_yards,
        CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.carries')) AS DECIMAL(12,2)) as carries,
        CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.rushTD')) AS DECIMAL(12,2)) as rushing_tds
    FROM nfl_player_stats ps
    WHERE receiving IS NOT NULL 
    AND rushing IS NOT NULL
    AND (ps.team_abv = ? OR ? IS NULL)
)
SELECT 
    long_name,
    team_abv,
    COUNT(*) as games_played,
    CAST(SUM(receiving_yards) AS DECIMAL(12,2)) as total_receiving_yards,
    CAST(SUM(rushing_yards) AS DECIMAL(12,2)) as total_rushing_yards,
    CAST(SUM(receptions) AS DECIMAL(12,2)) as total_receptions,
    CAST(SUM(carries) AS DECIMAL(12,2)) as total_carries,
    CAST(SUM(receiving_tds) AS DECIMAL(12,2)) as receiving_touchdowns,
    CAST(SUM(rushing_tds) AS DECIMAL(12,2)) as rushing_touchdowns,
    ROUND(SUM(receiving_yards) / NULLIF(SUM(receptions), 0), 1) as yards_per_reception,
    ROUND(SUM(rushing_yards) / NULLIF(SUM(carries), 0), 1) as yards_per_carry,
    ROUND((SUM(receiving_yards) + SUM(rushing_yards)) / COUNT(*), 1) as total_yards_per_game
FROM player_stats
GROUP BY long_name, team_abv
HAVING total_receiving_yards > 0 AND total_rushing_yards > 0
ORDER BY total_yards_per_game DESC
LIMIT 20";

        return DB::select($sql, array_fill(0, 2, $teamFilter));
    }

    public function getOffensiveConsistency(?string $teamFilter = null)
    {
        // Get all weeks from config
        $weeks = collect(config('nfl.weeks'));

        // Get current date
        $currentDate = now()->format('Y-m-d');

        // Find the current week based on date
        $currentWeek = $weeks->filter(function ($week) use ($currentDate) {
            return $currentDate >= $week['start'] && $currentDate <= $week['end'];
        })->keys()->first();

        // If current date is before season starts, use Week 1
        if (!$currentWeek) {
            $currentWeek = 'Week 1';
        }

        // Get all weeks up to current week
        $activeWeeks = $weeks->take(
            (int)str_replace('Week ', '', $currentWeek)
        );

        $startDate = "'" . $activeWeeks->first()['start'] . "'";
        $endDate = "'" . $activeWeeks->last()['end'] . "'";
        $totalWeeks = $activeWeeks->count();

        $sql = "
    WITH game_stats AS (
        SELECT 
            ps.long_name,
            ps.team_abv,
            ps.game_id,
            (CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.recYds')), '0') AS SIGNED) +
             CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.rushYds')), '0') AS SIGNED)) as total_yards,
            (CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.receptions')), '0') AS SIGNED) +
             CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.carries')), '0') AS SIGNED)) as total_touches,
            (CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.recTD')), '0') AS SIGNED) +
             CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.rushTD')), '0') AS SIGNED)) as total_tds
        FROM nfl_player_stats ps
        JOIN nfl_box_scores b ON ps.game_id = b.game_id
        JOIN nfl_team_schedules s ON b.game_id = s.game_id
        WHERE (receiving IS NOT NULL OR rushing IS NOT NULL)
        AND s.season_type = 'Regular Season'
        AND s.game_date BETWEEN ${startDate} AND ${endDate}
        AND (ps.team_abv = ? OR ? IS NULL)
    ),
    player_averages AS (
        SELECT 
            long_name,
            team_abv,
            AVG(total_yards) as avg_yards
        FROM game_stats
        GROUP BY long_name, team_abv
    ),
    player_stats AS (
        SELECT 
            gs.long_name,
            gs.team_abv,
            COUNT(*) as games_played,
            ROUND(AVG(gs.total_yards), 1) as avg_total_yards,
            ROUND(AVG(gs.total_touches), 1) as avg_touches,
            ROUND(AVG(gs.total_tds), 2) as avg_touchdowns,
            ROUND(STDDEV(gs.total_yards), 2) as yards_stddev,
            ROUND(STDDEV(gs.total_touches), 2) as touches_stddev,
            ROUND(
                STDDEV(gs.total_yards) / NULLIF(AVG(gs.total_yards), 0) * 100,
                2
            ) as yards_cv,
            SUM(gs.total_yards) as total_yards,
            SUM(gs.total_touches) as total_touches,
            SUM(gs.total_tds) as total_touchdowns,
            ROUND(
                SUM(CASE WHEN gs.total_yards >= 50 THEN 1 ELSE 0 END) * 100.0 / COUNT(*),
                1
            ) as pct_games_50plus_yards,
            MIN(gs.total_yards) as min_yards,
            MAX(gs.total_yards) as max_yards,
            ROUND(
                SUM(CASE WHEN gs.total_yards >= (pa.avg_yards * 0.8) THEN 1 ELSE 0 END) * 100.0 / COUNT(*),
                1
            ) as pct_above_floor
        FROM game_stats gs
        JOIN player_averages pa ON gs.long_name = pa.long_name AND gs.team_abv = pa.team_abv
        GROUP BY gs.long_name, gs.team_abv
        HAVING games_played >= 2 AND total_yards > 0
    )
    SELECT 
        long_name as player,
        team_abv as team,
        games_played,
        avg_total_yards,
        avg_touches,
        avg_touchdowns,
        yards_stddev,
        touches_stddev,
        yards_cv,
        total_yards,
        total_touches,
        total_touchdowns,
        pct_games_50plus_yards,
        min_yards,
        max_yards,
        pct_above_floor,
        CASE 
            WHEN yards_cv <= 35 AND avg_total_yards >= 50 THEN 'Elite'
            WHEN yards_cv <= 50 AND avg_total_yards >= 40 THEN 'Consistent'
            WHEN yards_cv <= 65 THEN 'Moderate'
            ELSE 'Variable'
        END as consistency_rating,
        ROUND(
            (
                (1 - (yards_cv / 100)) * 0.3 +
                (pct_games_50plus_yards / 100) * 0.3 +
                (pct_above_floor / 100) * 0.2 +
                (games_played / ?) * 0.2
            ) * 100,
            1
        ) as reliability_score
    FROM player_stats
    ORDER BY reliability_score DESC, total_yards DESC
    LIMIT 20
    ";

        return DB::select($sql, [$teamFilter, $teamFilter, $totalWeeks]);
    }

    private function getNflTeamStats(?string $teamFilter = null)
    {
        $sql = "
    WITH team_game_stats AS (
        SELECT 
            ts.team_id,
            ts.team_abv,
            ts.game_id,
            ts.total_yards,
            ts.rushing_yards,
            ts.passing_yards,
            ts.points_allowed,
            ROW_NUMBER() OVER (PARTITION BY ts.team_id ORDER BY ts.created_at DESC) as game_number
        FROM nfl_team_stats ts
        WHERE (ts.team_id = ? OR ? IS NULL)
    ),
    team_metrics AS (
        SELECT 
            team_id,
            team_abv,
            COUNT(*) as games_analyzed,
            
            -- Basic Averages
            ROUND(AVG(total_yards), 1) as avg_total_yards,
            ROUND(AVG(rushing_yards), 1) as avg_rushing_yards,
            ROUND(AVG(passing_yards), 1) as avg_passing_yards,
            
            -- Consistency Metrics
            ROUND(STDDEV(total_yards), 1) as total_yards_stddev,
            ROUND(STDDEV(rushing_yards), 1) as rushing_yards_stddev,
            ROUND(STDDEV(passing_yards), 1) as passing_yards_stddev,
            
            -- Min/Max Values
            MIN(total_yards) as min_total_yards,
            MAX(total_yards) as max_total_yards,
            MIN(rushing_yards) as min_rushing_yards,
            MAX(rushing_yards) as max_rushing_yards,
            MIN(passing_yards) as min_passing_yards,
            MAX(passing_yards) as max_passing_yards,
            
            -- Recent Game Trends
            GROUP_CONCAT(
                CONCAT_WS(':', 
                    game_number,
                    total_yards,
                    rushing_yards,
                    passing_yards
                )
                ORDER BY game_number ASC
            ) as recent_games,
            
            -- Performance Thresholds
            ROUND(
                (SUM(CASE WHEN total_yards >= 350 THEN 1 ELSE 0 END) * 100.0) / COUNT(*),
                1
            ) as pct_games_over_350,
            ROUND(
                (SUM(CASE WHEN rushing_yards >= 150 THEN 1 ELSE 0 END) * 100.0) / COUNT(*),
                1
            ) as pct_games_rush_150,
            ROUND(
                (SUM(CASE WHEN passing_yards >= 250 THEN 1 ELSE 0 END) * 100.0) / COUNT(*),
                1
            ) as pct_games_pass_250
        FROM team_game_stats
        WHERE game_number <= 5
        GROUP BY team_id, team_abv
    ),
    trend_analysis AS (
        SELECT
            tm.*,
            -- Coefficient of Variation (lower is more consistent)
            ROUND((total_yards_stddev / avg_total_yards) * 100, 1) as total_yards_cv,
            ROUND((rushing_yards_stddev / avg_rushing_yards) * 100, 1) as rushing_yards_cv,
            ROUND((passing_yards_stddev / avg_passing_yards) * 100, 1) as passing_yards_cv,
            
            -- Offensive Balance
            ROUND((avg_rushing_yards / avg_total_yards) * 100, 1) as rushing_yards_pct,
            ROUND((avg_passing_yards / avg_total_yards) * 100, 1) as passing_yards_pct,
            
            -- Performance Rating
            CASE 
                WHEN avg_total_yards >= 350 AND total_yards_stddev <= 50 THEN 'ELITE'
                WHEN avg_total_yards >= 300 AND total_yards_stddev <= 60 THEN 'STRONG'
                WHEN avg_total_yards >= 250 AND total_yards_stddev <= 70 THEN 'ABOVE_AVERAGE'
                WHEN avg_total_yards >= 200 THEN 'AVERAGE'
                ELSE 'BELOW_AVERAGE'
            END as performance_rating,
            
            -- Trend Direction (based on last 5 games)
            CASE 
                WHEN SUBSTRING_INDEX(recent_games, ',', 1) > SUBSTRING_INDEX(recent_games, ',', -1) THEN 'IMPROVING'
                WHEN SUBSTRING_INDEX(recent_games, ',', 1) < SUBSTRING_INDEX(recent_games, ',', -1) THEN 'DECLINING'
                ELSE 'STABLE'
            END as trend_direction
        FROM team_metrics tm
    )
    SELECT 
        team_id,
        team_abv,
        games_analyzed,
        
        -- Offensive Production
        avg_total_yards,
        avg_rushing_yards,
        avg_passing_yards,
        rushing_yards_pct,
        passing_yards_pct,
        
        -- Consistency Metrics
        total_yards_cv as consistency_score,
        rushing_yards_cv as rushing_consistency,
        passing_yards_cv as passing_consistency,
        
        -- High/Low Performance
        max_total_yards as best_game,
        min_total_yards as worst_game,
        pct_games_over_350 as explosive_offense_pct,
        
        -- Rushing Specific
        max_rushing_yards as best_rushing,
        min_rushing_yards as worst_rushing,
        pct_games_rush_150 as strong_rush_pct,
        
        -- Passing Specific
        max_passing_yards as best_passing,
        min_passing_yards as worst_passing,
        pct_games_pass_250 as strong_pass_pct,
        
        -- Overall Assessment
        performance_rating,
        trend_direction
    FROM trend_analysis
    ORDER BY avg_total_yards DESC;
    ";

        return DB::select($sql, [$teamFilter, $teamFilter]);
    }

    public function calculateTeamAnalytics(int $teamId, int $gamesBack = 5): array
    {
        $games = NflTeamStat::where('team_id', $teamId)
            ->orderBy('created_at', 'desc')
            ->take($gamesBack)
            ->get();

        if ($games->isEmpty()) {
            return $this->getEmptyAnalytics();
        }

        $gameCount = $games->count();
        $stats = $this->calculateBaseStats($games);

        return [
            'sample_size' => $gameCount,
            'offensive_stats' => [
                'yards_per_game' => [
                    'total' => round($stats['avg_total_yards'], 1),
                    'rushing' => round($stats['avg_rushing_yards'], 1),
                    'passing' => round($stats['avg_passing_yards'], 1),
                ],
                'efficiency' => [
                    'yards_per_play' => round($stats['avg_total_yards'] / ($stats['avg_rushing_attempts'] + $stats['avg_passing_attempts']), 2),
                    'rushing_yards_per_attempt' => round($stats['avg_rushing_yards'] / $stats['avg_rushing_attempts'], 2),
                    'passing_yards_per_attempt' => round($stats['avg_passing_yards'] / $stats['avg_passing_attempts'], 2),
                ],
                'play_distribution' => [
                    'rushing_percentage' => round(($stats['avg_rushing_attempts'] / ($stats['avg_rushing_attempts'] + $stats['avg_passing_attempts'])) * 100, 1),
                    'passing_percentage' => round(($stats['avg_passing_attempts'] / ($stats['avg_rushing_attempts'] + $stats['avg_passing_attempts'])) * 100, 1),
                ],
            ],
            'consistency_metrics' => [
                'total_yards' => $this->calculateConsistencyMetrics($games->pluck('total_yards')),
                'rushing_yards' => $this->calculateConsistencyMetrics($games->pluck('rushing_yards')),
                'passing_yards' => $this->calculateConsistencyMetrics($games->pluck('passing_yards')),
            ],
            'performance_trends' => [
                'total_yards' => $this->calculateTrends($games->pluck('total_yards')->toArray()),
                'rushing_yards' => $this->calculateTrends($games->pluck('rushing_yards')->toArray()),
                'passing_yards' => $this->calculateTrends($games->pluck('passing_yards')->toArray()),
            ],
            'situation_analysis' => $this->analyzeSituationalPerformance($teamId, $gamesBack),
            'opponent_adjusted' => $this->calculateOpponentAdjustedStats($teamId, $gamesBack),
        ];
    }

    private function getEmptyAnalytics(): array
    {
        return [
            'sample_size' => 0,
            'offensive_stats' => [
                'yards_per_game' => ['total' => 0, 'rushing' => 0, 'passing' => 0],
                'efficiency' => ['yards_per_play' => 0, 'rushing_yards_per_attempt' => 0, 'passing_yards_per_attempt' => 0],
                'play_distribution' => ['rushing_percentage' => 0, 'passing_percentage' => 0],
            ],
            'consistency_metrics' => [
                'total_yards' => ['mean' => 0, 'median' => 0, 'std_dev' => 0, 'coefficient_of_variation' => 0, 'range' => ['min' => 0, 'max' => 0]],
                'rushing_yards' => ['mean' => 0, 'median' => 0, 'std_dev' => 0, 'coefficient_of_variation' => 0, 'range' => ['min' => 0, 'max' => 0]],
                'passing_yards' => ['mean' => 0, 'median' => 0, 'std_dev' => 0, 'coefficient_of_variation' => 0, 'range' => ['min' => 0, 'max' => 0]],
            ],
            'performance_trends' => ['trend' => 'insufficient_data'],
            'situation_analysis' => ['home_performance' => [], 'away_performance' => []],
            'opponent_adjusted' => ['adjusted_per_game' => [], 'strength_of_schedule' => []],
        ];
    }

    /**
     * Calculate base statistics from games
     *
     * @param Collection $games
     * @return array
     */
    private function calculateBaseStats($games): array
    {
        $totalStats = [
            'total_yards' => 0,
            'rushing_yards' => 0,
            'passing_yards' => 0,
            'rushing_attempts' => 0,
            'passing_attempts' => 0,
        ];

        foreach ($games as $game) {
            $totalStats['total_yards'] += $game->total_yards;
            $totalStats['rushing_yards'] += $game->rushing_yards;
            $totalStats['passing_yards'] += $game->passing_yards;
            // You might need to add these columns to your table
            $totalStats['rushing_attempts'] += $game->rushing_attempts ?? 20; // Default fallback
            $totalStats['passing_attempts'] += $game->passing_attempts ?? 30; // Default fallback
        }

        $gameCount = $games->count();
        return [
            'avg_total_yards' => $totalStats['total_yards'] / $gameCount,
            'avg_rushing_yards' => $totalStats['rushing_yards'] / $gameCount,
            'avg_passing_yards' => $totalStats['passing_yards'] / $gameCount,
            'avg_rushing_attempts' => $totalStats['rushing_attempts'] / $gameCount,
            'avg_passing_attempts' => $totalStats['passing_attempts'] / $gameCount,
        ];
    }

    /**
     * Calculate consistency metrics for a given stat
     *
     * @param Collection $values
     * @return array
     */
    private function calculateConsistencyMetrics($values): array
    {
        $mean = $values->avg();
        $standardDeviation = sqrt($values->map(function ($value) use ($mean) {
            return pow($value - $mean, 2);
        })->avg());

        return [
            'mean' => round($mean, 1),
            'median' => round($values->median(), 1),
            'std_dev' => round($standardDeviation, 1),
            'coefficient_of_variation' => round(($standardDeviation / $mean) * 100, 1),
            'range' => [
                'min' => $values->min(),
                'max' => $values->max(),
            ],
        ];
    }

    /**
     * Calculate performance trends
     *
     * @param array $values
     * @return array
     */
    private function calculateTrends(array $values): array
    {
        $count = count($values);
        if ($count < 2) return ['trend' => 'insufficient_data'];

        // Calculate moving averages
        $threeGameMA = $count >= 3 ? array_sum(array_slice($values, 0, 3)) / 3 : null;
        $fiveGameMA = $count >= 5 ? array_sum($values) / 5 : null;

        // Calculate linear regression
        $x = range(1, $count);
        $y = array_reverse($values); // Most recent first
        $linearRegression = $this->calculateLinearRegression($x, $y);

        return [
            'moving_averages' => [
                'three_game' => round($threeGameMA, 1),
                'five_game' => round($fiveGameMA, 1),
            ],
            'regression' => [
                'slope' => round($linearRegression['slope'], 3),
                'trend_direction' => $this->getTrendDirection($linearRegression['slope']),
                'r_squared' => round($linearRegression['r_squared'], 3),
            ],
            'momentum' => $this->calculateMomentum($values),
        ];
    }

    /**
     * Calculate linear regression
     *
     * @param array $x
     * @param array $y
     * @return array
     */
    private function calculateLinearRegression(array $x, array $y): array
    {
        $n = count($x);
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = array_sum(array_map(function ($xi, $yi) {
            return $xi * $yi;
        }, $x, $y));
        $sumX2 = array_sum(array_map(function ($xi) {
            return $xi * $xi;
        }, $x));

        $slope = (($n * $sumXY) - ($sumX * $sumY)) / (($n * $sumX2) - ($sumX * $sumX));
        $intercept = ($sumY - ($slope * $sumX)) / $n;

        // Calculate R-squared
        $yMean = $sumY / $n;
        $totalSS = array_sum(array_map(function ($yi) use ($yMean) {
            return pow($yi - $yMean, 2);
        }, $y));
        $residualSS = array_sum(array_map(function ($xi, $yi) use ($slope, $intercept) {
            $predicted = ($slope * $xi) + $intercept;
            return pow($yi - $predicted, 2);
        }, $x, $y));
        $rSquared = 1 - ($residualSS / $totalSS);

        return [
            'slope' => $slope,
            'intercept' => $intercept,
            'r_squared' => $rSquared,
        ];
    }

    /**
     * Get trend direction based on slope
     *
     * @param float $slope
     * @return string
     */
    private function getTrendDirection(float $slope): string
    {
        if ($slope > 5) return 'strong_upward';
        if ($slope > 2) return 'moderate_upward';
        if ($slope > 0) return 'slight_upward';
        if ($slope < -5) return 'strong_downward';
        if ($slope < -2) return 'moderate_downward';
        if ($slope < 0) return 'slight_downward';
        return 'stable';
    }

    /**
     * Calculate momentum based on recent performance
     *
     * @param array $values
     * @return string
     */
    private function calculateMomentum(array $values): string
    {
        if (count($values) < 2) return 'insufficient_data';

        $recentAvg = array_sum(array_slice($values, 0, 2)) / 2;
        $previousAvg = array_sum(array_slice($values, -2)) / 2;
        $percentChange = (($recentAvg - $previousAvg) / $previousAvg) * 100;

        if ($percentChange > 15) return 'strong_positive';
        if ($percentChange > 5) return 'positive';
        if ($percentChange < -15) return 'strong_negative';
        if ($percentChange < -5) return 'negative';
        return 'neutral';
    }

    /**
     * Analyze situational performance
     *
     * @param int $teamId
     * @param int $gamesBack
     * @return array
     */
    private function analyzeSituationalPerformance(int $teamId, int $gamesBack): array
    {
        // Get schedule records
        $schedules = NflTeamSchedule::where(function ($query) use ($teamId) {
            $query->where('home_team_id', $teamId)
                ->orWhere('away_team_id', $teamId);
        })
            ->orderBy('game_date', 'desc')
            ->take($gamesBack)
            ->get();

        $homeStats = [];
        $awayStats = [];

        // Get stats for each game
        foreach ($schedules as $schedule) {
            $gameStats = NflTeamStat::where('game_id', $schedule->game_id)
                ->where('team_id', $teamId)
                ->first();

            if ($gameStats) {
                if ($schedule->home_team_id === $teamId) {
                    $homeStats[] = $gameStats;
                } else {
                    $awayStats[] = $gameStats;
                }
            }
        }

        return [
            'home_performance' => $this->calculateSituationalMetrics($homeStats),
            'away_performance' => $this->calculateSituationalMetrics($awayStats),
        ];
    }

    /**
     * Calculate situational metrics for a set of stats
     *
     * @param array $stats
     * @return array
     */
    private function calculateSituationalMetrics(array $stats): array
    {
        if (empty($stats)) {
            return [
                'average_yards' => 0,
                'yards_consistency' => 0,
                'performance_rating' => 'insufficient_data'
            ];
        }

        $totalYards = 0;
        $yardValues = [];

        foreach ($stats as $stat) {
            if ($stat) {
                $totalYards += $stat->total_yards;
                $yardValues[] = $stat->total_yards;
            }
        }

        $average = count($stats) > 0 ? $totalYards / count($stats) : 0;
        $consistency = $this->calculateConsistencyScore($yardValues);

        return [
            'average_yards' => round($average, 1),
            'yards_consistency' => round($consistency, 2),
            'performance_rating' => $this->getRatingFromStats($average, $consistency)
        ];
    }

    /**
     * Calculate consistency score
     *
     * @param array $values
     * @return float
     */
    private function calculateConsistencyScore(array $values): float
    {
        if (empty($values)) {
            return 0;
        }

        $mean = array_sum($values) / count($values);
        $variances = array_map(function ($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values);

        $variance = array_sum($variances) / count($values);
        $standardDeviation = sqrt($variance);

        return $mean > 0 ? ($standardDeviation / $mean) * 100 : 0;
    }

    /**
     * Get performance rating based on stats
     *
     * @param float $average
     * @param float $consistency
     * @return string
     */
    private function getRatingFromStats(float $average, float $consistency): string
    {
        if ($average === 0) {
            $rating = $this->getBaselineRating($consistency);
        } else {
            $rating = $this->getFullRating($average, $consistency);
        }

        return ucfirst(str_replace('_', ' ', $rating));
    }

    /**
     * Get baseline rating when average is 0
     *
     * @param float $consistency
     * @return string
     */
    private function getBaselineRating(float $consistency): string
    {
        return 'developing'; // or another appropriate baseline rating
    }

    /**
     * Get full rating based on both metrics
     *
     * @param float $average
     * @param float $consistency
     * @return string
     */
    private function getFullRating(float $average, float $consistency): string
    {
        // Elite tier
        if ($average >= 350) {
            if ($consistency < 15) return 'elite_consistent';
            if ($consistency < 25) return 'elite_stable';
            return 'elite_volatile';
        }

        // Strong tier
        if ($average >= 300) {
            if ($consistency < 20) return 'strong_consistent';
            if ($consistency < 30) return 'strong_stable';
            return 'strong_volatile';
        }

        // Above Average tier
        if ($average >= 250) {
            if ($consistency < 25) return 'above_average_consistent';
            if ($consistency < 35) return 'above_average_stable';
            return 'above_average_volatile';
        }

        // Average tier
        if ($average >= 200) {
            if ($consistency < 30) return 'average_consistent';
            if ($consistency < 40) return 'average_stable';
            return 'average_volatile';
        }

        // Below Average tier
        if ($consistency < 35) return 'below_average_consistent';
        if ($consistency < 45) return 'below_average_stable';
        return 'below_average_volatile';
    }

    /**
     * Calculate opponent-adjusted statistics
     *
     * @param int $teamId
     * @param int $gamesBack
     * @return array
     */
    private function calculateOpponentAdjustedStats(int $teamId, int $gamesBack): array
    {
        // Get league averages for comparison
        $leagueAverages = NflTeamStat::selectRaw('
        AVG(total_yards) as avg_total_yards,
        AVG(rushing_yards) as avg_rushing_yards,
        AVG(passing_yards) as avg_passing_yards
    ')->first();

        // Get team stats
        $teamStats = NflTeamStat::where('team_id', $teamId)
            ->orderBy('created_at', 'desc')
            ->take($gamesBack)
            ->get();

        $adjustedStats = [
            'total_yards' => 0,
            'rushing_yards' => 0,
            'passing_yards' => 0,
        ];

        if ($teamStats->isEmpty()) {
            return [
                'adjusted_per_game' => [
                    'total_yards' => 0,
                    'rushing_yards' => 0,
                    'passing_yards' => 0,
                ],
                'strength_of_schedule' => [
                    'rating' => 0,
                    'difficulty' => 'insufficient_data',
                    'opponent_count' => 0
                ]
            ];
        }

        foreach ($teamStats as $stat) {
            // Parse opponent from game_id
            $gameDetails = explode('_', $stat->game_id);
            if (count($gameDetails) === 2) {
                $teams = explode('@', $gameDetails[1]);
                if (count($teams) === 2) {
                    $opponentAbv = ($teams[0] === $stat->team_abv) ? $teams[1] : $teams[0];

                    // Get opponent stats
                    $opponentStats = $this->getOpponentAveragesByAbv($opponentAbv);

                    // Calculate adjustment factors
                    $adjustedStats['total_yards'] += $stat->total_yards * ($leagueAverages->avg_total_yards / $opponentStats['total_yards']);
                    $adjustedStats['rushing_yards'] += $stat->rushing_yards * ($leagueAverages->avg_rushing_yards / $opponentStats['rushing_yards']);
                    $adjustedStats['passing_yards'] += $stat->passing_yards * ($leagueAverages->avg_passing_yards / $opponentStats['passing_yards']);
                }
            }
        }

        $gameCount = $teamStats->count() ?: 1; // Prevent division by zero

        return [
            'adjusted_per_game' => [
                'total_yards' => round($adjustedStats['total_yards'] / $gameCount, 1),
                'rushing_yards' => round($adjustedStats['rushing_yards'] / $gameCount, 1),
                'passing_yards' => round($adjustedStats['passing_yards'] / $gameCount, 1),
            ],
            'strength_of_schedule' => $this->calculateStrengthOfSchedule($teamId, $gamesBack),
        ];
    }

    /**
     * Get opponent averages by team abbreviation
     *
     * @param string $teamAbv
     * @return array
     */
    private function getOpponentAveragesByAbv(string $teamAbv): array
    {
        $stats = NflTeamStat::where('team_abv', $teamAbv)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        if ($stats->isEmpty()) {
            return [
                'total_yards' => 350, // League average fallback
                'rushing_yards' => 120,
                'passing_yards' => 230,
            ];
        }

        return [
            'total_yards' => $stats->avg('total_yards') ?: 350,
            'rushing_yards' => $stats->avg('rushing_yards') ?: 120,
            'passing_yards' => $stats->avg('passing_yards') ?: 230,
        ];
    }

    /**
     * Calculate strength of schedule
     *
     * @param int $teamId
     * @param int $gamesBack
     * @return array
     */
    private function calculateStrengthOfSchedule(int $teamId, int $gamesBack): array
    {
        $schedules = NflTeamSchedule::where(function ($query) use ($teamId) {
            $query->where('home_team_id', $teamId)
                ->orWhere('away_team_id', $teamId);
        })
            ->orderBy('game_date', 'desc')
            ->take($gamesBack)
            ->get();

        $opponentIds = $schedules->map(function ($schedule) use ($teamId) {
            return $schedule->home_team_id === $teamId
                ? $schedule->away_team_id
                : $schedule->home_team_id;
        });

        $opponentStats = NflTeamStat::whereIn('team_id', $opponentIds)
            ->get()
            ->groupBy('team_id');

        $totalRating = 0;
        $count = 0;

        foreach ($opponentStats as $stats) {
            $avgYards = $stats->avg('total_yards');
            $totalRating += $this->getStrengthRating($avgYards);
            $count++;
        }

        $averageRating = $count > 0 ? $totalRating / $count : 0;

        return [
            'rating' => round($averageRating, 2),
            'difficulty' => $this->getScheduleDifficulty($averageRating),
            'opponent_count' => $count
        ];
    }

    /**
     * Get strength rating based on average yards
     *
     * @param float $avgYards
     * @return float
     */
    private function getStrengthRating(float $avgYards): float
    {
        if ($avgYards >= 400) return 1.0;
        if ($avgYards >= 350) return 0.8;
        if ($avgYards >= 300) return 0.6;
        if ($avgYards >= 250) return 0.4;
        return 0.2;
    }

    /**
     * Get schedule difficulty description
     *
     * @param float $rating
     * @return string
     */
    private function getScheduleDifficulty(float $rating): string
    {
        if ($rating >= 0.8) return 'very_difficult';
        if ($rating >= 0.6) return 'difficult';
        if ($rating >= 0.4) return 'moderate';
        if ($rating >= 0.2) return 'easy';
        return 'very_easy';
    }

    public function getOverUnderAnalysis(?string $teamFilter = null)
    {
        $sql = "
    WITH game_totals AS (
        SELECT 
            b.game_id,
            b.home_team,
            b.away_team,
            b.home_points + b.away_points as total_points,
            JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, '$.Q1')) + 
            JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, '$.Q2')) as home_first_half,
            JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, '$.Q1')) + 
            JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, '$.Q2')) as away_first_half,
            JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, '$.Q4')) as home_fourth_quarter,
            JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, '$.Q4')) as away_fourth_quarter
        FROM nfl_box_scores b
        JOIN nfl_team_schedules s ON b.game_id = s.game_id
        WHERE s.season_type = 'Regular Season'
        AND (b.home_team = ? OR b.away_team = ? OR ? IS NULL)
    )
    SELECT 
        CASE 
            WHEN ? IS NOT NULL THEN ?
            ELSE CONCAT(home_team, ' vs ', away_team)
        END as matchup,
        COUNT(*) as games_analyzed,
        ROUND(AVG(total_points), 1) as avg_total_points,
        ROUND(AVG(home_first_half + away_first_half), 1) as avg_first_half_points,
        ROUND(AVG(CAST(home_fourth_quarter AS UNSIGNED) + CAST(away_fourth_quarter AS UNSIGNED)), 1) as avg_fourth_quarter_points,
        MAX(total_points) as highest_combined_score,
        MIN(total_points) as lowest_combined_score,
        ROUND(STDDEV(total_points), 1) as points_variance,
        ROUND(
            SUM(CASE WHEN total_points > 44.5 THEN 1 ELSE 0 END) * 100.0 / COUNT(*),
            1
        ) as pct_games_over_44_5,
        ROUND(
            SUM(CASE WHEN home_first_half + away_first_half > 21.5 THEN 1 ELSE 0 END) * 100.0 / COUNT(*),
            1
        ) as pct_first_half_over_21_5,
        COUNT(CASE WHEN total_points > 44.5 AND home_first_half + away_first_half > 21.5 THEN 1 END) as correlated_overs,
        ROUND(AVG(CAST(home_fourth_quarter AS UNSIGNED) + CAST(away_fourth_quarter AS UNSIGNED)) * 4, 1) as projected_pace
    FROM game_totals
    GROUP BY matchup;
    ";

        return DB::select($sql, array_fill(0, 5, $teamFilter));
    }

    public function getTeamMatchupEdge(?string $teamFilter = null)
    {
        $sql = "
    WITH game_matchups AS (
        SELECT 
            b.game_id,
            s.game_date,
            ts.team_abv,
            CASE 
                WHEN b.home_team = ts.team_abv THEN b.away_team
                ELSE b.home_team
            END as opponent_team,
            ts.total_yards,
            ts.rushing_yards,
            ts.passing_yards,
            CASE 
                WHEN b.home_team = ts.team_abv THEN b.home_points
                ELSE b.away_points
            END as points_scored,
            CASE 
                WHEN b.home_team = ts.team_abv THEN b.away_points
                ELSE b.home_points
            END as points_allowed,
            CASE 
                WHEN b.home_team = ts.team_abv THEN 'home'
                ELSE 'away'
            END as location
        FROM nfl_team_stats ts
        JOIN nfl_box_scores b ON ts.game_id = b.game_id
        JOIN nfl_team_schedules s ON b.game_id = s.game_id
        WHERE s.season_type = 'Regular Season'
        AND (ts.team_abv = ? OR ? IS NULL)
        ORDER BY s.game_date DESC
        LIMIT 5
    ),
    team_averages AS (
        SELECT 
            team_abv,
            opponent_team,
            DATE_FORMAT(game_date, '%Y-%m-%d') as game_date,
            total_yards,
            rushing_yards,
            passing_yards,
            points_scored,
            points_allowed,
            total_yards - LAG(total_yards) OVER (PARTITION BY team_abv ORDER BY game_date) as yards_trend,
            points_scored - LAG(points_scored) OVER (PARTITION BY team_abv ORDER BY game_date) as scoring_trend,
            location
        FROM game_matchups
    )
    SELECT 
        ta.team_abv,
        ta.opponent_team,
        ta.game_date,
        ta.total_yards,
        ta.rushing_yards,
        ta.passing_yards,
        ta.points_scored,
        ta.points_allowed,
        ta.total_yards - AVG(gm.total_yards) OVER (PARTITION BY ta.opponent_team) as yards_differential,
        ta.points_scored - ta.points_allowed as points_differential,
        CASE 
            WHEN ta.total_yards > AVG(gm.total_yards) OVER (PARTITION BY ta.opponent_team) 
            AND ta.points_scored > AVG(gm.points_scored) OVER (PARTITION BY ta.opponent_team)
            THEN 'High'
            WHEN ta.total_yards < AVG(gm.total_yards) OVER (PARTITION BY ta.opponent_team) 
            AND ta.points_scored < AVG(gm.points_scored) OVER (PARTITION BY ta.opponent_team)
            THEN 'Low'
            ELSE 'Medium'
        END as win_probability,
        ROUND(
            (
                (ta.total_yards / NULLIF(AVG(gm.total_yards) OVER (PARTITION BY ta.opponent_team), 0)) * 40 +
                (ta.points_scored / NULLIF(AVG(gm.points_scored) OVER (PARTITION BY ta.opponent_team), 0)) * 40 +
                CASE 
                    WHEN ta.location = 'home' THEN 20
                    ELSE 0
                END
            ),
            1
        ) as edge_score
    FROM team_averages ta
    JOIN game_matchups gm ON ta.opponent_team = gm.team_abv
    ORDER BY ta.game_date DESC, edge_score DESC;
    ";

        return DB::select($sql, array_fill(0, 2, $teamFilter));
    }

    public function getFirstHalfTendencies(?string $teamFilter = null)
    {
        $sql = "
    WITH first_half_stats AS (
        SELECT 
            b.game_id,
            CASE 
                WHEN b.home_team = ? OR ? IS NULL THEN b.home_team
                ELSE b.away_team 
            END as team_abv,
            CASE 
                WHEN b.home_team = ? OR ? IS NULL THEN 
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, '$.Q1')) AS UNSIGNED) +
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, '$.Q2')) AS UNSIGNED)
                ELSE 
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, '$.Q1')) AS UNSIGNED) +
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, '$.Q2')) AS UNSIGNED)
            END as team_first_half_points,
            CASE 
                WHEN b.home_team = ? OR ? IS NULL THEN 
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, '$.Q1')) AS UNSIGNED) +
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, '$.Q2')) AS UNSIGNED)
                ELSE 
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, '$.Q1')) AS UNSIGNED) +
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, '$.Q2')) AS UNSIGNED)
            END as opponent_first_half_points
        FROM nfl_box_scores b
        JOIN nfl_team_schedules s ON b.game_id = s.game_id
        WHERE s.season_type = 'Regular Season'
        AND (b.home_team = ? OR b.away_team = ? OR ? IS NULL)
    )
    SELECT 
        team_abv,
        COUNT(*) as games_analyzed,
        ROUND(AVG(team_first_half_points), 1) as avg_first_half_points,
        ROUND(AVG(opponent_first_half_points), 1) as avg_opponent_first_half_points,
        ROUND(AVG(team_first_half_points + opponent_first_half_points), 1) as avg_first_half_total,
        ROUND(STDDEV(team_first_half_points), 1) as first_half_points_stddev,
        ROUND(
            SUM(CASE WHEN team_first_half_points > opponent_first_half_points THEN 1 ELSE 0 END) * 100.0 / COUNT(*),
            1
        ) as first_half_win_percentage,
        ROUND(
            SUM(CASE WHEN team_first_half_points + opponent_first_half_points > 21.5 THEN 1 ELSE 0 END) * 100.0 / COUNT(*),
            1
        ) as first_half_over_percentage,
        MAX(team_first_half_points) as max_first_half_points,
        MIN(team_first_half_points) as min_first_half_points
    FROM first_half_stats
    GROUP BY team_abv
    ORDER BY avg_first_half_points DESC;
    ";

        return DB::select($sql, array_fill(0, 9, $teamFilter));
    }

    public function getTeamVsConference()
    {
        $sql = "
    WITH conference_games AS (
        SELECT 
            b.game_id,
            b.home_team,
            b.away_team,
            t1.conference_abv,
            t1.conference as conference_name,
            CASE 
                WHEN b.home_team = t1.team_abv THEN b.home_points
                ELSE b.away_points
            END as team_points,
            CASE 
                WHEN b.home_team = t1.team_abv THEN b.away_points
                ELSE b.home_points
            END as opponent_points,
            CASE 
                WHEN b.home_team = t1.team_abv THEN ts1.total_yards
                ELSE ts2.total_yards
            END as team_yards,
            CASE 
                WHEN b.home_team = t1.team_abv THEN ts2.total_yards
                ELSE ts1.total_yards
            END as opponent_yards,
            t1.team_abv
        FROM nfl_box_scores b
        JOIN nfl_team_schedules s ON b.game_id = s.game_id
        JOIN nfl_teams t1 ON (b.home_team = t1.team_abv OR b.away_team = t1.team_abv)
        JOIN nfl_teams t2 ON (
            CASE 
                WHEN b.home_team = t1.team_abv THEN b.away_team
                ELSE b.home_team
            END = t2.team_abv
        )
        JOIN nfl_team_stats ts1 ON (b.game_id = ts1.game_id AND ts1.team_abv = t1.team_abv)
        JOIN nfl_team_stats ts2 ON (
            b.game_id = ts2.game_id 
            AND ts2.team_abv = CASE 
                WHEN b.home_team = t1.team_abv THEN b.away_team
                ELSE b.home_team
            END
        )
        WHERE s.season_type = 'Regular Season'
        AND t1.conference_abv = t2.conference_abv
    )
    SELECT 
        team_abv,
        conference_abv,
        conference_name,
        COUNT(*) as games_played,
        ROUND(AVG(team_points), 1) as avg_points_for,
        ROUND(AVG(opponent_points), 1) as avg_points_against,
        ROUND(AVG(team_yards), 1) as avg_yards_for,
        ROUND(AVG(opponent_yards), 1) as avg_yards_against,
        SUM(CASE WHEN team_points > opponent_points THEN 1 ELSE 0 END) as wins,
        SUM(CASE WHEN team_points < opponent_points THEN 1 ELSE 0 END) as losses,
        ROUND(AVG(team_points - opponent_points), 1) as avg_margin,
        ROUND(
            SUM(CASE WHEN team_points > opponent_points THEN 1 ELSE 0 END) * 100.0 / 
            COUNT(*), 1
        ) as win_percentage,
        ROUND(STDDEV(team_points), 1) as points_stddev,
        ROUND(STDDEV(team_yards), 1) as yards_stddev
    FROM conference_games
    GROUP BY team_abv, conference_abv, conference_name
    ORDER BY win_percentage DESC, avg_margin DESC;
    ";

        return DB::select($sql);
    }

    public function getTeamVsDivision()
    {
        $sql = "
    WITH division_games AS (
        SELECT 
            b.game_id,
            b.home_team,
            b.away_team,
            t1.conference_abv,
            t1.conference as conference_name,
            t1.division,
            CASE 
                WHEN b.home_team = t1.team_abv THEN b.home_points
                ELSE b.away_points
            END as team_points,
            CASE 
                WHEN b.home_team = t1.team_abv THEN b.away_points
                ELSE b.home_points
            END as opponent_points,
            CASE 
                WHEN b.home_team = t1.team_abv THEN ts1.total_yards
                ELSE ts2.total_yards
            END as team_yards,
            CASE 
                WHEN b.home_team = t1.team_abv THEN ts2.total_yards
                ELSE ts1.total_yards
            END as opponent_yards,
            t1.team_abv
        FROM nfl_box_scores b
        JOIN nfl_team_schedules s ON b.game_id = s.game_id
        JOIN nfl_teams t1 ON (b.home_team = t1.team_abv OR b.away_team = t1.team_abv)
        JOIN nfl_teams t2 ON (
            CASE 
                WHEN b.home_team = t1.team_abv THEN b.away_team
                ELSE b.home_team
            END = t2.team_abv
        )
        JOIN nfl_team_stats ts1 ON (b.game_id = ts1.game_id AND ts1.team_abv = t1.team_abv)
        JOIN nfl_team_stats ts2 ON (
            b.game_id = ts2.game_id 
            AND ts2.team_abv = CASE 
                WHEN b.home_team = t1.team_abv THEN b.away_team
                ELSE b.home_team
            END
        )
        WHERE s.season_type = 'Regular Season'
        AND t1.division = t2.division
    )
    SELECT 
        team_abv,
        conference_abv,
        conference_name,
        division,
        COUNT(*) as games_played,
        ROUND(AVG(team_points), 1) as avg_points_for,
        ROUND(AVG(opponent_points), 1) as avg_points_against,
        ROUND(AVG(team_yards), 1) as avg_yards_for,
        ROUND(AVG(opponent_yards), 1) as avg_yards_against,
        SUM(CASE WHEN team_points > opponent_points THEN 1 ELSE 0 END) as wins,
        SUM(CASE WHEN team_points < opponent_points THEN 1 ELSE 0 END) as losses,
        ROUND(AVG(team_points - opponent_points), 1) as avg_margin,
        ROUND(
            SUM(CASE WHEN team_points > opponent_points THEN 1 ELSE 0 END) * 100.0 / 
            COUNT(*), 1
        ) as win_percentage,
        ROUND(STDDEV(team_points), 1) as points_stddev,
        ROUND(STDDEV(team_yards), 1) as yards_stddev
    FROM division_games
    GROUP BY team_abv, conference_abv, conference_name, division
    ORDER BY win_percentage DESC, avg_margin DESC;
    ";

        return DB::select($sql);
    }

    public function getPlayerVsConference()
    {
        $sql = "
    WITH conference_games AS (
        SELECT 
            ps.*,
            t1.conference_abv,
            t1.conference as conference_name
        FROM nfl_player_stats ps
        JOIN nfl_box_scores b ON ps.game_id = b.game_id
        JOIN nfl_team_schedules s ON b.game_id = s.game_id
        JOIN nfl_teams t1 ON ps.team_abv = t1.team_abv
        JOIN nfl_teams t2 ON (
            CASE 
                WHEN b.home_team = ps.team_abv THEN b.away_team 
                ELSE b.home_team 
            END = t2.team_abv
        )
        WHERE s.season_type = 'Regular Season'
        AND t1.conference_abv = t2.conference_abv
    )
    SELECT 
        cg.long_name as player,
        cg.team_abv as team,
        conference_abv,
        conference_name,
        COUNT(DISTINCT cg.game_id) as games_played,
        -- Offensive Stats
        ROUND(AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.recYds')) AS UNSIGNED)), 1) as avg_receiving_yards,
        ROUND(AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.rushYds')) AS UNSIGNED)), 1) as avg_rushing_yards,
        SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.recTD')) AS UNSIGNED)) as receiving_tds,
        SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.rushTD')) AS UNSIGNED)) as rushing_tds,
        -- Defensive Stats
        ROUND(AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.totalTackles')) AS UNSIGNED)), 1) as avg_tackles,
        SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.sacks')) AS UNSIGNED)) as total_sacks,
        SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.defensiveInterceptions')) AS UNSIGNED)) as total_ints,
        -- Performance Metrics
        ROUND(STDDEV(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.recYds')) AS UNSIGNED)), 1) as receiving_yards_stddev,
        ROUND(STDDEV(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.rushYds')) AS UNSIGNED)), 1) as rushing_yards_stddev,
        -- Total Impact Score
        (
            SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.recTD')) AS UNSIGNED)) * 6 +
            SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.rushTD')) AS UNSIGNED)) * 6 +
            SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.sacks')) AS UNSIGNED)) * 4 +
            SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.defensiveInterceptions')) AS UNSIGNED)) * 5
        ) as impact_score
    FROM conference_games cg
    WHERE (receiving IS NOT NULL OR rushing IS NOT NULL OR defense IS NOT NULL)
    GROUP BY cg.long_name, cg.team_abv, conference_abv, conference_name
    HAVING games_played >= 2
    ORDER BY impact_score DESC
    LIMIT 50;
    ";

        return DB::select($sql);
    }

    public function getPlayerVsDivision()
    {
        $sql = "
    WITH division_games AS (
        SELECT 
            ps.*,
            t1.conference_abv,
            t1.conference as conference_name,
            t1.division
        FROM nfl_player_stats ps
        JOIN nfl_box_scores b ON ps.game_id = b.game_id
        JOIN nfl_team_schedules s ON b.game_id = s.game_id
        JOIN nfl_teams t1 ON ps.team_abv = t1.team_abv
        JOIN nfl_teams t2 ON (
            CASE 
                WHEN b.home_team = ps.team_abv THEN b.away_team 
                ELSE b.home_team 
            END = t2.team_abv
        )
        WHERE s.season_type = 'Regular Season'
        AND t1.division = t2.division
    )
    SELECT 
        dg.long_name as player,
        dg.team_abv as team,
        conference_abv,
        conference_name,
        division,
        COUNT(DISTINCT dg.game_id) as games_played,
        -- Offensive Stats
        ROUND(AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.recYds')) AS UNSIGNED)), 1) as avg_receiving_yards,
        ROUND(AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.rushYds')) AS UNSIGNED)), 1) as avg_rushing_yards,
        SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.recTD')) AS UNSIGNED)) as receiving_tds,
        SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.rushTD')) AS UNSIGNED)) as rushing_tds,
        -- Defensive Stats
        ROUND(AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.totalTackles')) AS UNSIGNED)), 1) as avg_tackles,
        SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.sacks')) AS UNSIGNED)) as total_sacks,
        SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.defensiveInterceptions')) AS UNSIGNED)) as total_ints,
        -- Performance Metrics
        ROUND(STDDEV(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.recYds')) AS UNSIGNED)), 1) as receiving_yards_stddev,
        ROUND(STDDEV(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.rushYds')) AS UNSIGNED)), 1) as rushing_yards_stddev,
        -- Total Impact Score
        (
            SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.recTD')) AS UNSIGNED)) * 6 +
            SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.rushTD')) AS UNSIGNED)) * 6 +
            SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.sacks')) AS UNSIGNED)) * 4 +
            SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.defensiveInterceptions')) AS UNSIGNED)) * 5
        ) as impact_score
    FROM division_games dg
    WHERE (receiving IS NOT NULL OR rushing IS NOT NULL OR defense IS NOT NULL)
    GROUP BY dg.long_name, dg.team_abv, conference_abv, conference_name, division
    HAVING games_played >= 2
    ORDER BY impact_score DESC
    LIMIT 50;
    ";

        return DB::select($sql);
    }

    public function getConferenceStats(?string $teamFilter = null)
    {
        $sql = "
    WITH conference_summary AS (
        SELECT 
            t.conference_abv,
            t.conference,
            COUNT(DISTINCT b.game_id) as total_games,
            ROUND(AVG(ts.total_yards), 1) as avg_yards,
            ROUND(AVG(
                CASE 
                    WHEN b.home_team = ts.team_abv THEN b.home_points
                    ELSE b.away_points
                END
            ), 1) as avg_points,
            ROUND(STDDEV(ts.total_yards), 1) as yards_stddev,
            ROUND(AVG(ts.rushing_yards / NULLIF(ts.total_yards, 0) * 100), 1) as rush_percentage
        FROM nfl_team_stats ts
        JOIN nfl_box_scores b ON ts.game_id = b.game_id
        JOIN nfl_teams t ON ts.team_abv = t.team_abv
        JOIN nfl_team_schedules s ON b.game_id = s.game_id
        WHERE s.season_type = 'Regular Season'
        AND (ts.team_abv = ? OR ? IS NULL)
        GROUP BY t.conference_abv, t.conference
    )
    SELECT 
        cs.*,
        CASE 
            WHEN yards_stddev <= 50 AND avg_yards >= 350 THEN 'Elite'
            WHEN yards_stddev <= 60 AND avg_yards >= 300 THEN 'Strong'
            WHEN yards_stddev <= 70 AND avg_yards >= 250 THEN 'Above Average'
            ELSE 'Average'
        END as conference_rating
    FROM conference_summary cs
    ORDER BY avg_yards DESC;
    ";

        return DB::select($sql, [$teamFilter, $teamFilter]);
    }

    public function getDivisionStats(?string $teamFilter = null)
    {
        $sql = "
    WITH division_summary AS (
        SELECT 
            t.conference_abv,
            t.conference,
            t.division,
            COUNT(DISTINCT b.game_id) as total_games,
            ROUND(AVG(ts.total_yards), 1) as avg_yards,
            ROUND(AVG(
                CASE 
                    WHEN b.home_team = ts.team_abv THEN b.home_points
                    ELSE b.away_points
                END
            ), 1) as avg_points,
            ROUND(STDDEV(ts.total_yards), 1) as yards_stddev,
            ROUND(AVG(ts.rushing_yards / NULLIF(ts.total_yards, 0) * 100), 1) as rush_percentage,
            COUNT(DISTINCT ts.team_abv) as teams_in_division
        FROM nfl_team_stats ts
        JOIN nfl_box_scores b ON ts.game_id = b.game_id
        JOIN nfl_teams t ON ts.team_abv = t.team_abv
        JOIN nfl_team_schedules s ON b.game_id = s.game_id
        WHERE s.season_type = 'Regular Season'
        AND (ts.team_abv = ? OR ? IS NULL)
        GROUP BY t.conference_abv, t.conference, t.division
    )
    SELECT 
        ds.*,
        CASE 
            WHEN yards_stddev <= 50 AND avg_yards >= 350 THEN 'Elite'
            WHEN yards_stddev <= 60 AND avg_yards >= 300 THEN 'Strong'
            WHEN yards_stddev <= 70 AND avg_yards >= 250 THEN 'Above Average'
            ELSE 'Average'
        END as division_rating
    FROM division_summary ds
    ORDER BY avg_yards DESC;
    ";

        return DB::select($sql, [$teamFilter, $teamFilter]);
    }


}
