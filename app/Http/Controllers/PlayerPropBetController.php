<?php

namespace App\Http\Controllers;

use App\Models\NbaEvent;
use App\Models\NbaPropBet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlayerPropBetController extends Controller
{
    public function index(Request $request): View
    {
        $date = $request->input('date');
        $eventId = $request->input('event_id');

        $eventDates = NbaPropBet::select(DB::raw('DATE(event_date) as event_date'))
            ->distinct()
            ->orderBy('event_date', 'desc')
            ->pluck('event_date')
            ->toArray();

        $events = [];
        $playerOverStats = [];
        $noDataMessage = null;
        $playerStats = [];

        if ($date) {
            $events = NbaEvent::whereDate('date', $date)
                ->select('espn_id as event_id', 'home_team_id', 'away_team_id', 'date')
                ->get();

            if ($eventId) {
                $validEvent = $events->where('event_id', $eventId)->first();

                if ($validEvent) {
                    $query = DB::table('nba_prop_bets')
                        ->join('nba_player_stats', function ($join) {
                            $join->on('nba_prop_bets.event_id', '=', 'nba_player_stats.event_id')
                                ->on('nba_prop_bets.athlete_id', '=', 'nba_player_stats.player_id');
                        })
                        ->join('nba_players', 'nba_prop_bets.athlete_id', '=', 'nba_players.espn_id')
                        ->select(
                            'nba_players.display_name as player_name',
                            'nba_prop_bets.total as prop_total',
                            'nba_prop_bets.prop_type',
                            'nba_player_stats.splits_json',
                            'nba_prop_bets.event_date',
                            'nba_prop_bets.event_id'
                        )
                        ->where('nba_prop_bets.prop_type', 'Total Points')
                        ->where('nba_prop_bets.event_id', $eventId)
                        ->groupBy(
                            'nba_players.display_name',
                            'nba_prop_bets.total',
                            'nba_prop_bets.prop_type',
                            'nba_player_stats.splits_json',
                            'nba_prop_bets.event_date',
                            'nba_prop_bets.event_id'
                        )
                        ->get();

                    foreach ($query as $record) {
                        $playerName = $record->player_name;
                        $points = $this->extractPoints($record->splits_json);

                        if ($points === null) {
                            continue;
                        }

                        if (!isset($playerStats[$playerName])) {
                            $playerStats[$playerName] = [
                                'player_name' => $playerName,
                                'prop_total' => $record->prop_total, // Store prop_total from the query
                                'points_total' => 0,
                                'total_over_hits' => 0,
                                'total_under_hits' => 0,
                                'total_events' => 0,
                            ];
                        }

                        $playerStats[$playerName]['points_total'] += $points;
                        $playerStats[$playerName]['total_events']++;

                        if ($points > $record->prop_total) {
                            $playerStats[$playerName]['total_over_hits']++;
                        } elseif ($points < $record->prop_total) {
                            $playerStats[$playerName]['total_under_hits']++;
                        }
                    }

                    foreach ($playerStats as $stat) {
                        $totalEvents = $stat['total_events'];
                        $overHits = $stat['total_over_hits'];
                        $underHits = $stat['total_under_hits'];
                        $averagePoints = $totalEvents > 0 ? round($stat['points_total'] / $totalEvents, 1) : 0;

                        $playerOverStats[] = (object)[
                            'player_name' => $stat['player_name'],
                            'prop_total' => floatval($stat['prop_total']), // Convert to float
                            'average_points' => $averagePoints,
                            'total_over_hits' => $overHits,
                            'average_over_percentage' => $totalEvents > 0 ? round(($overHits / $totalEvents) * 100, 2) : 0,
                            'total_under_hits' => $underHits,
                            'average_under_percentage' => $totalEvents > 0 ? round(($underHits / $totalEvents) * 100, 2) : 0,
                            'total_events' => $totalEvents,
                        ];
                    }

                    if (empty($playerOverStats)) {
                        $noDataMessage = 'No prop bet data available for this event.';
                    }
                } else {
                    $noDataMessage = 'Selected event does not exist for the chosen date.';
                }
            }
        }

        // Debug the data being sent to the view
        // dd($playerOverStats);

        return view('nba.player-prop-bets', compact(
            'playerOverStats',
            'eventDates',
            'events',
            'date',
            'eventId',
            'noDataMessage'
        ));
    }

    protected function extractPoints(string $splitsJson): ?float
    {
        $data = json_decode($splitsJson, true);

        if (!is_array($data) || !isset($data['categories'])) {
            return null;
        }

        foreach ($data['categories'] as $category) {
            if (isset($category['name']) && strtolower($category['name']) === 'offensive') {
                if (isset($category['stats']) && is_array($category['stats'])) {
                    foreach ($category['stats'] as $stat) {
                        if (isset($stat['name']) && strtolower($stat['name']) === 'points') {
                            return isset($stat['value']) ? (float)$stat['value'] : null;
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Extracts the assists value from the splits JSON.
     *
     * @param string $splitsJson
     * @return ?float
     */
    protected function extractAssists(string $splitsJson): ?float
    {
        $data = json_decode($splitsJson, true);

        if (!is_array($data) || !isset($data['categories'])) {
            return null;
        }

        foreach ($data['categories'] as $category) {
            if (isset($category['name']) && strtolower($category['name']) === 'offensive') {
                if (isset($category['stats']) && is_array($category['stats'])) {
                    foreach ($category['stats'] as $stat) {
                        if (isset($stat['name']) && strtolower($stat['name']) === 'assists') {
                            return isset($stat['value']) ? (float)$stat['value'] : null;
                        }
                    }
                }
            }
        }

        return null;
    }

}