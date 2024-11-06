<?php

namespace App\Http\Controllers\Nfl;

use App\Http\Controllers\Controller;
use App\Models\Nfl\NflBettingOdds;
use App\Models\Nfl\NflEloPrediction;
use App\Models\Nfl\NflPlayerData;
use App\Models\Nfl\NflTeamSchedule;
use App\Models\Nfl\NflTeamStat;
use Carbon\Carbon;
use Illuminate\Http\Request;

// Add the model for team schedules

class NflEloRatingController extends Controller
{
    public function prediction(Request $request)
    {
        $week = $request->input('week');

        // Fetch Elo predictions
        $eloPredictionsQuery = NflEloPrediction::query();
        if ($week) {
            $eloPredictionsQuery->where('week', $week);
        }
        $eloPredictions = $eloPredictionsQuery->orderBy('team')->get();

        // Fetch available weeks for the dropdown
        $weeks = NflEloPrediction::select('week')->distinct()->orderBy('week')->pluck('week');

        // Fetch betting odds
        $nflBettingOdds = NflBettingOdds::whereIn('event_id', $eloPredictions->pluck('game_id'))->get()->keyBy('event_id');

        // Fetch home and away points from nfl_team_schedules
        $teamSchedules = NflTeamSchedule::whereIn('game_id', $eloPredictions->pluck('game_id'))
            ->get()->keyBy('game_id');

        // Process the Elo predictions and enrich with game data
        foreach ($eloPredictions as $prediction) {
            $game = $teamSchedules[$prediction->game_id] ?? null;
            if ($game) {
                // Attach homePts, awayPts from teamSchedules
                $prediction->homePts = $game->home_pts ?? null;
                $prediction->awayPts = $game->away_pts ?? null;
                $prediction->gameStatus = $game->game_status ?? null;
                $prediction->gameStatusDetail = $game->status_type_detail ?? null;

                // Calculate if the prediction was correct (only if the game is completed)
                if (isset($game->home_pts) && isset($game->away_pts)) {
                    $actualSpread = $game->home_pts - $game->away_pts; // Actual point difference
                    $predictedSpread = $prediction->predicted_spread;

                    // Determine if the prediction was correct
                    $prediction->wasCorrect = ($predictedSpread > 0 && $actualSpread > $predictedSpread) || ($predictedSpread < 0 && $actualSpread < $predictedSpread);
                } else {
                    $prediction->wasCorrect = null; // Game is not completed, so no result yet
                }
            }
        }

        // Pass the enriched predictions and betting odds to the view
        return view('nfl.elo_predictions', compact('eloPredictions', 'weeks', 'week', 'nflBettingOdds'));
    }

    public function show($gameId)
    {
        // Fetch predictions for both teams in the game
        $predictions = NflEloPrediction::where('game_id', $gameId)->get();

        // Fetch the team schedule details for this game
        $teamSchedule = NflTeamSchedule::where('game_id', $gameId)->first();

        // Check if data exists
        if ($predictions->isEmpty() || !$teamSchedule) {
            return redirect()->back()->with('error', 'No stats available for this game.');
        }

        // Define team IDs
        $homeTeamId = $teamSchedule->home_team_id;
        $awayTeamId = $teamSchedule->away_team_id;

        // Fetch the last 3 games for the home team with stats
        $homeTeamLastGames = NflTeamSchedule::where(function ($query) use ($homeTeamId) {
            $query->where('home_team_id', $homeTeamId)
                ->orWhere('away_team_id', $homeTeamId);
        })
            ->where('game_id', '<', $teamSchedule->game_id)
            ->orderBy('game_date', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($game) use ($homeTeamId) {
                // Fetch stats for this game and team
                $stats = NflTeamStat::where('game_id', $game->game_id)
                    ->where('team_id', $homeTeamId)
                    ->first(['rushing_yards', 'passing_yards']);

                // Assign stats to the game
                $game->rushing_yards = $stats->rushing_yards ?? 'N/A';
                $game->passing_yards = $stats->passing_yards ?? 'N/A';

                return $game;
            });

        // Fetch the last 3 games for the away team with stats
        $awayTeamLastGames = NflTeamSchedule::where(function ($query) use ($awayTeamId) {
            $query->where('home_team_id', $awayTeamId)
                ->orWhere('away_team_id', $awayTeamId);
        })
            ->where('game_id', '<', $teamSchedule->game_id)
            ->orderBy('game_date', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($game) use ($awayTeamId) {
                // Fetch stats for this game and team
                $stats = NflTeamStat::where('game_id', $game->game_id)
                    ->where('team_id', $awayTeamId)
                    ->first(['rushing_yards', 'passing_yards']);

                // Assign stats to the game
                $game->rushing_yards = $stats->rushing_yards ?? 'N/A';
                $game->passing_yards = $stats->passing_yards ?? 'N/A';

                return $game;
            });

        // Fetch injury descriptions for players with no return date or return date in the future
        $today = Carbon::today();

        $homeTeamInjuries = NflPlayerData::where('teamiD', $homeTeamId)
            ->where(function ($query) use ($today) {
                $query->whereNull('injury_return_date')
                    ->orWhere('injury_return_date', '>', $today);
            })
            ->pluck('injury_description');

        $awayTeamInjuries = NflPlayerData::where('teamiD', $awayTeamId)
            ->where(function ($query) use ($today) {
                $query->whereNull('injury_return_date')
                    ->orWhere('injury_return_date', '>', $today);
            })
            ->pluck('injury_description');

        // Pass data to the view
        return view('nfl.elo.show', compact(
            'predictions',
            'teamSchedule',
            'homeTeamLastGames',
            'awayTeamLastGames',
            'homeTeamInjuries',
            'awayTeamInjuries',
            'homeTeamId',
            'awayTeamId'
        ));
    }
}
