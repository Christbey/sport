<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlayerTrendsController extends Controller
{
    public function index(Request $request)
    {
        $team = $request->input('team');
        $season = $request->input('season', config('nfl.seasonYear'));
        $week = $request->input('week');
        $query = DB::table('player_trends')->where('season', $season);

        if ($team) {
            $query->join('nfl_team_schedules', 'player_trends.game_id', '=', 'nfl_team_schedules.game_id')
                ->where(function ($q) use ($team) {
                    $q->where('nfl_team_schedules.home_team', $team)
                        ->orWhere('nfl_team_schedules.away_team', $team);
                });
        }

        if ($week) {
            $query->where('week', $week);
        }

        $playerTrends = $query->orderBy('week')->get();

        return view('nfl.player-trends', [
            'playerTrends' => $playerTrends,
            'selectedTeam' => $team,
            'season' => $season,
            'week' => $week,
        ]);
    }
}
