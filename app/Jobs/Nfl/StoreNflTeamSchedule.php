<?php

namespace App\Jobs\Nfl;

use App\Models\NFLTeamSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StoreNflTeamSchedule implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $teamAbv;
    protected $season;

    public function __construct($teamAbv, $season)
    {
        $this->teamAbv = $teamAbv;
        $this->season = $season;
    }

    public function handle()
    {
        // Fetch the data from the API
        $response = Http::withHeaders([
            'x-rapidapi-host' => 'tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com',
            'x-rapidapi-key' => config('services.rapidapi.key'),
        ])->get('https://tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com/getNFLTeamSchedule', [
            'teamAbv' => $this->teamAbv,
            'season' => $this->season,
        ]);

        // Check if the response status is OK (200)
        if ($response->ok()) {
            $scheduleData = $response->json('body.schedule');

            // Check if the schedule data is not null or empty
            if (is_array($scheduleData) && !empty($scheduleData)) {
                foreach ($scheduleData as $game) {
                    // Extract necessary game details
                    $homePts = $game['homePts'] ?? null;
                    $awayPts = $game['awayPts'] ?? null;
                    $homeResult = $game['homeResult'] ?? null;
                    $awayResult = $game['awayResult'] ?? null;

                    try {
                        // Store or update the schedule in the database
                        NFLTeamSchedule::updateOrCreate(
                            [
                                'game_id' => $game['gameID'],
                            ],
                            [
                                'team_abv' => $this->teamAbv,
                                'season_type' => $game['seasonType'],
                                'away_team' => $game['away'],
                                'home_team_id' => $game['teamIDHome'],
                                'game_date' => $game['gameDate'],
                                'game_status' => $game['gameStatus'],
                                'game_week' => $game['gameWeek'],
                                'away_team_id' => $game['teamIDAway'],
                                'home_team' => $game['home'],
                                'away_result' => $awayResult, // Storing away team result
                                'home_result' => $homeResult, // Storing home team result
                                'home_pts' => $homePts, // Storing home team points
                                'away_pts' => $awayPts, // Storing away team points
                                'game_time' => $game['gameTime'] ?? null,
                                'game_time_epoch' => !empty($game['gameTime_epoch']) ? (int)$game['gameTime_epoch'] : null,
                                'game_status_code' => $game['gameStatusCode'] ?? null,
                            ]
                        );

                        // Log success for each game
                        Log::info("Game ID {$game['gameID']} for team {$this->teamAbv} stored successfully.");
                    } catch (\Exception $e) {
                        // Log any errors during data storage
                        Log::error("Error storing game {$game['gameID']} for team {$this->teamAbv}: " . $e->getMessage());
                    }
                }
            } else {
                Log::warning("No schedule data found for team: {$this->teamAbv} in season: {$this->season}");
            }
        } else {
            Log::error("Failed to fetch schedule for team: {$this->teamAbv} in season: {$this->season}. Status: {$response->status()}");
        }
    }
}
