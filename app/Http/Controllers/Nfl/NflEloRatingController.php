<?php

namespace App\Http\Controllers\Nfl;

use App\Http\Controllers\Controller;
use App\Models\Nfl\NflBettingOdds;
use App\Models\Nfl\NflEloPrediction;
use App\Models\Nfl\NflPlayerData;
use App\Models\Nfl\NflTeamSchedule;
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

        // Fetch injury descriptions for players with no return date or return date in the future
        $today = Carbon::today();

        // Fetch injury data for home team
        $homeTeamInjuries = NflPlayerData::where('teamiD', $homeTeamId)
            ->where(function ($query) use ($today) {
                $query->whereNull('injury_return_date')
                    ->orWhere('injury_return_date', '>', $today);
            })
            ->get([
                'espnName',
                'injury_description',
                'injury_designation',
                'injury_return_date',
            ]);

        // Fetch injury data for away team
        $awayTeamInjuries = NflPlayerData::where('teamiD', $awayTeamId)
            ->where(function ($query) use ($today) {
                $query->whereNull('injury_return_date')
                    ->orWhere('injury_return_date', '>', $today);
            })
            ->get([
                'espnName',
                'injury_description',
                'injury_designation',
                'injury_return_date',
            ]);

        $bettingOdds = NflBettingOdds::where('event_id', $gameId)->first();

        // Calculate total points and compare with total_over
        $totalPoints = null;
        $overUnderResult = null;

        if ($teamSchedule->home_pts !== null && $teamSchedule->away_pts !== null) {
            $totalPoints = $teamSchedule->home_pts + $teamSchedule->away_pts;

            if ($bettingOdds && $bettingOdds->total_over) {
                $totalOver = $bettingOdds->total_over;

                if ($totalPoints > $totalOver) {
                    $overUnderResult = 'Over';
                } elseif ($totalPoints < $totalOver) {
                    $overUnderResult = 'Under';
                } else {
                    $overUnderResult = 'Push';
                }
            }
        }

        $homeTeamLastGames = NflTeamSchedule::where(function ($query) use ($homeTeamId) {
            $query->where('home_team_id', $homeTeamId)
                ->orWhere('away_team_id', $homeTeamId);
        })
            ->where('game_id', '<', $teamSchedule->game_id)
            ->orderBy('game_date', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($game) use ($homeTeamId) {
                // Determine if the team was home or away
                $isHomeTeam = $game->home_team_id === $homeTeamId;

                // Calculate Margin of Victory
                if ($game->home_pts !== null && $game->away_pts !== null) {
                    if ($isHomeTeam) {
                        $game->marginOfVictory = $game->home_pts - $game->away_pts;
                    } else {
                        $game->marginOfVictory = $game->away_pts - $game->home_pts;
                    }
                } else {
                    $game->marginOfVictory = 'N/A';
                }

                // Fetch betting odds for this game
                $bettingOdds = NflBettingOdds::where('event_id', $game->game_id)->first();

                // Calculate over/under result
                if ($game->home_pts !== null && $game->away_pts !== null && $bettingOdds && $bettingOdds->total_over) {
                    $totalPoints = $game->home_pts + $game->away_pts;
                    $totalOver = $bettingOdds->total_over;

                    if ($totalPoints > $totalOver) {
                        $game->overUnderResult = 'Over';
                    } elseif ($totalPoints < $totalOver) {
                        $game->overUnderResult = 'Under';
                    } else {
                        $game->overUnderResult = 'Push';
                    }
                } else {
                    $game->overUnderResult = 'N/A';
                }

                return $game;
            });


        // Fetch the last 3 games for the away team with stats and over/under results
        $awayTeamLastGames = NflTeamSchedule::where(function ($query) use ($awayTeamId) {
            $query->where('home_team_id', $awayTeamId)
                ->orWhere('away_team_id', $awayTeamId);
        })
            ->where('game_id', '<', $teamSchedule->game_id)
            ->orderBy('game_date', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($game) use ($awayTeamId) {
                // Determine if the team was home or away
                $isHomeTeam = $game->home_team_id === $awayTeamId;

                // Calculate Margin of Victory
                if ($game->home_pts !== null && $game->away_pts !== null) {
                    if ($isHomeTeam) {
                        $game->marginOfVictory = $game->home_pts - $game->away_pts;
                    } else {
                        $game->marginOfVictory = $game->away_pts - $game->home_pts;
                    }
                } else {
                    $game->marginOfVictory = 'N/A';
                }

                // Fetch betting odds for this game
                $bettingOdds = NflBettingOdds::where('event_id', $game->game_id)->first();

                // Calculate over/under result
                if ($game->home_pts !== null && $game->away_pts !== null && $bettingOdds && $bettingOdds->total_over) {
                    $totalPoints = $game->home_pts + $game->away_pts;
                    $totalOver = $bettingOdds->total_over;

                    if ($totalPoints > $totalOver) {
                        $game->overUnderResult = 'Over';
                    } elseif ($totalPoints < $totalOver) {
                        $game->overUnderResult = 'Under';
                    } else {
                        $game->overUnderResult = 'Push';
                    }
                } else {
                    $game->overUnderResult = 'N/A';
                }

                return $game;
            });


        // Pass data to the view
        return view('nfl.elo.show', compact(
            'predictions',
            'teamSchedule',
            'homeTeamLastGames',
            'awayTeamLastGames',
            'homeTeamInjuries',
            'awayTeamInjuries',
            'homeTeamId',
            'awayTeamId',
            'bettingOdds',
            'totalPoints',
            'overUnderResult'
        ));
    }
}
