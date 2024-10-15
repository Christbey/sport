<?php

namespace App\Http\Controllers\Nfl;

use App\Http\Controllers\Controller;
use App\Models\Nfl\NflBettingOdds;
use App\Models\NflEloPrediction;
use App\Models\Nfl\NflTeamSchedule;

// Add the model for team schedules
use Illuminate\Http\Request;

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
}
