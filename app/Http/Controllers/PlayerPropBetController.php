<?php

namespace App\Http\Controllers;

use App\Models\NbaPropBet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlayerPropBetController extends Controller
{
    /**
     * Display a listing of player or team prop bets.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $date = $request->input('date');
        $propType = $request->input('prop_type', 'Total Points'); // Default to "Total Points"
        $viewType = $request->input('view_type', 'player'); // Default to "player"

        // Get all distinct event dates for the dropdown
        $eventDates = NbaPropBet::selectRaw('DATE(event_date) as event_date')
            ->distinct()
            ->orderBy('event_date', 'desc')
            ->pluck('event_date')
            ->toArray();

        // Get all distinct prop types for the dropdown
        $propTypes = NbaPropBet::select('prop_type')
            ->distinct()
            ->orderBy('prop_type')
            ->pluck('prop_type')
            ->toArray();

        $playerOverStats = [];
        $teamStats = [];
        $noDataMessage = null;

        if ($date) {
            if ($viewType === 'player') {
                // Fetch player stats
                $propBets = NbaPropBet::select(
                    'nba_prop_bets.athlete_id',
                    'nba_prop_bets.event_id',
                    'nba_prop_bets.total as prop_total',
                    'nba_players.display_name as athlete_name',
                    'nba_players.team_espn_id',
                    'nba_teams.name as team_name',
                    'nba_prop_bets.event_date'
                )
                    ->leftJoin('nba_players', 'nba_prop_bets.athlete_id', '=', 'nba_players.espn_id')
                    ->leftJoin('nba_teams', 'nba_players.team_espn_id', '=', 'nba_teams.espn_id')
                    ->whereDate('nba_prop_bets.event_date', $date)
                    ->where('nba_prop_bets.prop_type', $propType)
                    ->whereIn('nba_prop_bets.id', function ($query) use ($date, $propType) {
                        $query->selectRaw('MAX(id)')
                            ->from('nba_prop_bets')
                            ->whereDate('event_date', $date)
                            ->where('prop_type', $propType)
                            ->groupBy('athlete_id', 'event_id', 'prop_type');
                    })
                    ->limit($this->getResultLimit())
                    ->get();

                if ($propBets->isNotEmpty()) {
                    $athleteIds = $propBets->pluck('athlete_id')->unique();

                    $playerStats = DB::table('nba_player_stats')
                        ->whereIn('player_id', $athleteIds)
                        ->select('player_id', 'points', 'event_date', 'event_id')
                        ->get()
                        ->groupBy('player_id');

                    foreach ($propBets as $propBet) {
                        $athleteId = $propBet->athlete_id;
                        $athleteName = $propBet->athlete_name;
                        $teamName = $propBet->team_name;
                        $propTotal = $propBet->prop_total;

                        if (!isset($playerStats[$athleteId])) {
                            continue;
                        }

                        $playerRecords = $playerStats[$athleteId];
                        $totalOverHits = 0;
                        $totalUnderHits = 0;
                        $totalEvents = count($playerRecords);

                        foreach ($playerRecords as $record) {
                            if ($record->points > $propTotal) {
                                $totalOverHits++;
                            } elseif ($record->points < $propTotal) {
                                $totalUnderHits++;
                            }
                        }

                        $playerOverStats[] = (object)[
                            'athlete_id' => $athleteId,
                            'athlete_name' => $athleteName,
                            'team_name' => $teamName,
                            'prop_total' => $propTotal,
                            'total_events' => $totalEvents,
                            'total_over_hits' => $totalOverHits,
                            'total_under_hits' => $totalUnderHits,
                            'average_over_percentage' => $totalEvents > 0 ? round(($totalOverHits / $totalEvents) * 100, 2) : 0,
                            'average_under_percentage' => $totalEvents > 0 ? round(($totalUnderHits / $totalEvents) * 100, 2) : 0,
                        ];
                    }
                } else {
                    $noDataMessage = 'No player prop bet data available for this date and type.';
                }
            } elseif ($viewType === 'team') {
                // Fetch team stats
                $startDate = '2024-10-22';
                $endDate = today(); // Today's date

                $teamStats = DB::select("
    WITH TeamHalfPeriodResults AS (
        SELECT 
            e.id AS event_id,
            e.home_team_id,
            e.away_team_id,
            JSON_UNQUOTE(JSON_EXTRACT(e.home_linescores, '$[0].points')) AS home_period_1,
            JSON_UNQUOTE(JSON_EXTRACT(e.away_linescores, '$[0].points')) AS away_period_1,
            JSON_UNQUOTE(JSON_EXTRACT(e.home_linescores, '$[1].points')) AS home_period_2,
            JSON_UNQUOTE(JSON_EXTRACT(e.away_linescores, '$[1].points')) AS away_period_2,
            JSON_UNQUOTE(JSON_EXTRACT(e.home_linescores, '$[2].points')) AS home_period_3,
            JSON_UNQUOTE(JSON_EXTRACT(e.away_linescores, '$[2].points')) AS away_period_3,
            JSON_UNQUOTE(JSON_EXTRACT(e.home_linescores, '$[3].points')) AS home_period_4,
            JSON_UNQUOTE(JSON_EXTRACT(e.away_linescores, '$[3].points')) AS away_period_4,
            JSON_UNQUOTE(JSON_EXTRACT(e.home_linescores, '$[0].points')) +
            JSON_UNQUOTE(JSON_EXTRACT(e.home_linescores, '$[1].points')) AS home_first_half,
            JSON_UNQUOTE(JSON_EXTRACT(e.away_linescores, '$[0].points')) +
            JSON_UNQUOTE(JSON_EXTRACT(e.away_linescores, '$[1].points')) AS away_first_half,
            JSON_UNQUOTE(JSON_EXTRACT(e.home_linescores, '$[2].points')) +
            JSON_UNQUOTE(JSON_EXTRACT(e.home_linescores, '$[3].points')) AS home_second_half,
            JSON_UNQUOTE(JSON_EXTRACT(e.away_linescores, '$[2].points')) +
            JSON_UNQUOTE(JSON_EXTRACT(e.away_linescores, '$[3].points')) AS away_second_half,
            DATE(e.date) AS event_date
        FROM nba_events e
        WHERE DATE(e.date) BETWEEN ? AND ?
    ),
    AggregatedTeamStats AS (
        SELECT
            team_id,
            SUM(CASE WHEN home_first_half > away_first_half THEN 1 ELSE 0 END) AS first_half_wins,
            SUM(CASE WHEN home_first_half < away_first_half THEN 1 ELSE 0 END) AS first_half_losses,
            SUM(CASE WHEN home_second_half > away_second_half THEN 1 ELSE 0 END) AS second_half_wins,
            SUM(CASE WHEN home_second_half < away_second_half THEN 1 ELSE 0 END) AS second_half_losses,
            SUM(CASE WHEN home_period_1 > away_period_1 THEN 1 ELSE 0 END) AS period_1_wins,
            SUM(CASE WHEN home_period_1 < away_period_1 THEN 1 ELSE 0 END) AS period_1_losses,
            SUM(CASE WHEN home_period_2 > away_period_2 THEN 1 ELSE 0 END) AS period_2_wins,
            SUM(CASE WHEN home_period_2 < away_period_2 THEN 1 ELSE 0 END) AS period_2_losses,
            SUM(CASE WHEN home_period_3 > away_period_3 THEN 1 ELSE 0 END) AS period_3_wins,
            SUM(CASE WHEN home_period_3 < away_period_3 THEN 1 ELSE 0 END) AS period_3_losses,
            SUM(CASE WHEN home_period_4 > away_period_4 THEN 1 ELSE 0 END) AS period_4_wins,
            SUM(CASE WHEN home_period_4 < away_period_4 THEN 1 ELSE 0 END) AS period_4_losses
        FROM (
            SELECT 
                home_team_id AS team_id,
                home_first_half,
                away_first_half,
                home_second_half,
                away_second_half,
                home_period_1,
                away_period_1,
                home_period_2,
                away_period_2,
                home_period_3,
                away_period_3,
                home_period_4,
                away_period_4
            FROM TeamHalfPeriodResults
            UNION ALL
            SELECT 
                away_team_id AS team_id,
                away_first_half AS home_first_half,
                home_first_half AS away_first_half,
                away_second_half AS home_second_half,
                home_second_half AS away_second_half,
                away_period_1 AS home_period_1,
                home_period_1 AS away_period_1,
                away_period_2 AS home_period_2,
                home_period_2 AS away_period_2,
                away_period_3 AS home_period_3,
                home_period_3 AS away_period_3,
                away_period_4 AS home_period_4,
                home_period_4 AS away_period_4
            FROM TeamHalfPeriodResults
        ) AS aggregated
        GROUP BY team_id
    )
    SELECT 
        thpr.event_id,
        h.name AS home_team_name,
        a.name AS away_team_name,
        thpr.event_date,
        ats1.first_half_wins AS home_first_half_wins,
        ats1.first_half_losses AS home_first_half_losses,
        (ats1.first_half_wins / NULLIF(ats1.first_half_wins + ats1.first_half_losses, 0)) AS home_first_half_win_percentage,
        ats1.second_half_wins AS home_second_half_wins,
        ats1.second_half_losses AS home_second_half_losses,
        (ats1.second_half_wins / NULLIF(ats1.second_half_wins + ats1.second_half_losses, 0)) AS home_second_half_win_percentage,
        ats1.period_1_wins AS home_period_1_wins,
        ats1.period_1_losses AS home_period_1_losses,
        (ats1.period_1_wins / NULLIF(ats1.period_1_wins + ats1.period_1_losses, 0)) AS home_period_1_win_percentage,
        ats1.period_2_wins AS home_period_2_wins,
        ats1.period_2_losses AS home_period_2_losses,
        (ats1.period_2_wins / NULLIF(ats1.period_2_wins + ats1.period_2_losses, 0)) AS home_period_2_win_percentage,
        ats1.period_3_wins AS home_period_3_wins,
        ats1.period_3_losses AS home_period_3_losses,
        (ats1.period_3_wins / NULLIF(ats1.period_3_wins + ats1.period_3_losses, 0)) AS home_period_3_win_percentage,
        ats1.period_4_wins AS home_period_4_wins,
        ats1.period_4_losses AS home_period_4_losses,
        (ats1.period_4_wins / NULLIF(ats1.period_4_wins + ats1.period_4_losses, 0)) AS home_period_4_win_percentage,
        ats2.first_half_wins AS away_first_half_wins,
        ats2.first_half_losses AS away_first_half_losses,
        (ats2.first_half_wins / NULLIF(ats2.first_half_wins + ats2.first_half_losses, 0)) AS away_first_half_win_percentage,
        ats2.second_half_wins AS away_second_half_wins,
        ats2.second_half_losses AS away_second_half_losses,
        (ats2.second_half_wins / NULLIF(ats2.second_half_wins + ats2.second_half_losses, 0)) AS away_second_half_win_percentage,
        ats2.period_1_wins AS away_period_1_wins,
        ats2.period_1_losses AS away_period_1_losses,
        (ats2.period_1_wins / NULLIF(ats2.period_1_wins + ats2.period_1_losses, 0)) AS away_period_1_win_percentage,
        ats2.period_2_wins AS away_period_2_wins,
        ats2.period_2_losses AS away_period_2_losses,
        (ats2.period_2_wins / NULLIF(ats2.period_2_wins + ats2.period_2_losses, 0)) AS away_period_2_win_percentage,
        ats2.period_3_wins AS away_period_3_wins,
        ats2.period_3_losses AS away_period_3_losses,
        (ats2.period_3_wins / NULLIF(ats2.period_3_wins + ats2.period_3_losses, 0)) AS away_period_3_win_percentage,
        ats2.period_4_wins AS away_period_4_wins,
        ats2.period_4_losses AS away_period_4_losses,
        (ats2.period_4_wins / NULLIF(ats2.period_4_wins + ats2.period_4_losses, 0)) AS away_period_4_win_percentage
    FROM TeamHalfPeriodResults thpr
    LEFT JOIN AggregatedTeamStats ats1 ON ats1.team_id = thpr.home_team_id
    LEFT JOIN AggregatedTeamStats ats2 ON ats2.team_id = thpr.away_team_id
    LEFT JOIN nba_teams h ON h.id = thpr.home_team_id
    LEFT JOIN nba_teams a ON a.id = thpr.away_team_id
    WHERE thpr.event_date = ?
", [$startDate, $endDate, $date]);
            } else {
                $noDataMessage = 'Select a date and view type to display results.';
            }
        }
        // Excluded prop types
        $excludedPropTypes = [
            '1st Quarter Moneyline',
            '4th Quarter Spread',
            '1st Half Moneyline',
            '2nd Half Moneyline',
            '1st Quarter Spread',
            '2nd Quarter Moneyline',
            '3rd Quarter Moneyline',
            '4th Quarter Moneyline',
            '1st Half Spread',
            '2nd Half Spread',
            '1st Quarter Spread',
            '2nd Quarter Spread',
            '3rd Quarter Spread',


            // Add more types to exclude here
        ];

// Filter out the excluded prop types
        $propTypes = NbaPropBet::select('prop_type')
            ->distinct()
            ->orderBy('prop_type')
            ->pluck('prop_type')
            ->reject(function ($type) use ($excludedPropTypes) {
                return in_array($type, $excludedPropTypes);
            })
            ->toArray();

        //dd($playerOverStats, $teamStats, $eventDates, $propTypes, $date, $propType, $viewType, $noDataMessage);
        return view('nba.player-prop-bets', compact(
            'playerOverStats',
            'teamStats',
            'eventDates',
            'propTypes',
            'date',
            'propType',
            'viewType',
            'noDataMessage'
        ));
    }

    private function getResultLimit(): int
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return 1000; // Admin sees more rows
        } elseif ($user->hasRole(['pro_subscriber', 'pro_user', 'pro'])) {
            return 25;  // Pro users see 500 rows
        } elseif ($user->hasRole(['basic_subscriber', 'basic_user', 'basic'])) {
            return 3;  // Basic users see 100 rows
        } else {
            return 3;   // Free users see 25 rows
        }
    }
}
