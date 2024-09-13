<?php

namespace App\Jobs\Nfl;

use App\Models\NflBettingOdds;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StoreNflBettingOdds implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $gameDate;

    public function __construct($gameDate)
    {
        $this->gameDate = $gameDate;
    }

    public function handle()
    {
        // Make the API request
        $response = Http::withHeaders([
            'x-rapidapi-host' => 'tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com',
            'x-rapidapi-key' => env('RAPIDAPI_KEY'),
        ])->get('https://tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com/getNFLBettingOdds', [
            'gameDate' => $this->gameDate,
            'itemFormat' => 'list',
            'impliedTotals' => 'true',
        ]);

        // Log the full response for debugging
        Log::info('NFL Betting Odds API Response:', ['response' => $response->json()]);

        if ($response->successful()) {
            $oddsData = $response->json();

            if (isset($oddsData['body']) && is_array($oddsData['body'])) {
                foreach ($oddsData['body'] as $game) {
                    if (isset($game['sportsBooks']) && is_array($game['sportsBooks'])) {
                        foreach ($game['sportsBooks'] as $sportsBook) {
                            if (isset($sportsBook['odds']) && is_array($sportsBook['odds'])) {
                                $odds = $sportsBook['odds'];

                                // Store or update the data
                                if (!empty($game['gameID'])) {
                                    NflBettingOdds::updateOrCreate(
                                        [
                                            'event_id' => $game['gameID'], // Conditions to match an existing record
                                            'source' => $sportsBook['sportsBook'],
                                        ],
                                        [
                                            'game_date' => $game['gameDate'],
                                            'away_team' => $game['awayTeam'],
                                            'home_team' => $game['homeTeam'],
                                            'away_team_id' => $game['teamIDAway'],
                                            'home_team_id' => $game['teamIDHome'],
                                            'spread_home' => isset($odds['homeTeamSpread']) ? floatval($odds['homeTeamSpread']) : null,
                                            'spread_away' => isset($odds['awayTeamSpread']) ? floatval($odds['awayTeamSpread']) : null,
                                            'total_over' => isset($odds['totalOver']) ? floatval($odds['totalOver']) : null,
                                            'total_under' => isset($odds['totalUnder']) ? floatval($odds['totalUnder']) : null,
                                            'moneyline_home' => isset($odds['homeTeamMLOdds']) ? floatval($odds['homeTeamMLOdds']) : null,
                                            'moneyline_away' => isset($odds['awayTeamMLOdds']) ? floatval($odds['awayTeamMLOdds']) : null,
                                            'implied_total_home' => $odds['impliedTotals']['homeTotal'] ?? null,
                                            'implied_total_away' => $odds['impliedTotals']['awayTotal'] ?? null,
                                        ]
                                    );
                                } else {
                                    Log::warning('No event ID found for the game', ['game' => $game]);
                                }
                            }
                        }
                    }
                }
                Log::info('All odds data stored or updated successfully.');
            } else {
                Log::error('Unexpected data format received from the API.', ['data' => $oddsData]);
            }
        } else {
            Log::error('Failed to fetch NFL Betting Odds from the API.', ['status' => $response->status()]);
        }
    }
}
