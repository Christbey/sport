<?php

namespace App\Http\Controllers\Nfl;

use App\Http\Controllers\Controller;
use App\Models\Nfl\NflBettingOdds;
use App\Models\NflEloPrediction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Log;

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

        // Make API request to get live game data
        $response = Http::withHeaders([
            'x-rapidapi-host' => 'tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com',
            'x-rapidapi-key' => config('services.rapidapi.key'),
        ])->get('https://tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com/getNFLScoresOnly', [
            'gameDate' => '20241013', // Example date, adjust as needed
        ]);

        // Log the response to ensure we're getting the expected structure
        Log::info('API Response:', $response->json());

        // Check if the response is successful and has the body data
        if ($response->successful() && isset($response->json()['body'])) {
            $gamesData = $response->json()['body'];  // Access the "body" key

            // Process the Elo predictions and enrich with game data
            foreach ($eloPredictions as $prediction) {
                $game = $gamesData[$prediction->game_id] ?? null;
                if ($game) {
                    // Attach homePts, awayPts, etc., to the prediction
                    $prediction->homePts = $game['homePts'] ?? null;
                    $prediction->awayPts = $game['awayPts'] ?? null;
                    $prediction->gameStatus = $game['gameStatus'] ?? null;
                    $prediction->gameClock = $game['gameClock'] ?? null;

                    // Calculate if the prediction was correct (only if the game is completed)
                    if ($game['gameStatus'] === 'Completed' && isset($game['homePts']) && isset($game['awayPts'])) {
                        $actualSpread = $game['homePts'] - $game['awayPts']; // Actual point difference
                        $predictedSpread = $prediction->predicted_spread;

                        // Determine if the prediction was correct
                        $prediction->wasCorrect = ($predictedSpread > 0 && $actualSpread > $predictedSpread) || ($predictedSpread < 0 && $actualSpread < $predictedSpread);
                    } else {
                        $prediction->wasCorrect = null; // Game is not completed, so no result yet
                    }
                }
            }
        } else {
            Log::warning('Failed to fetch data from the API or missing "body" key.');
        }

        // Pass the enriched predictions and betting odds to the view
        return view('nfl.elo_predictions', compact('eloPredictions', 'weeks', 'week', 'nflBettingOdds'));
    }
}
