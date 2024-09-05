<?php

namespace App\Jobs\Nfl;

use App\Models\NFLTeamSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

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
            'x-rapidapi-key' => env('RAPIDAPI_KEY'),
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
                    NflTeamSchedule::updateOrCreate(
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
                            'away_result' => $game['awayResult'] ?? null, // Use null if key does not exist
                            'home_result' => $game['homeResult'] ?? null, // Use null if key does not exist
                            'home_pts' => $game['homePts'] ?? null, // Use null if key does not exist
                            'away_pts' => $game['awayPts'] ?? null, // Use null if key does not exist
                            'game_time' => $game['gameTime'] ?? null, // Use null if key does not exist
                            'game_time_epoch' => !empty($game['gameTime_epoch']) ? (int)$game['gameTime_epoch'] : null, // Ensure it's an integer or null
                            'game_status_code' => $game['gameStatusCode'] ?? null, // Use null if key does not exist
                        ]
                    );
                }
            } else {
                \Log::warning("No schedule data found for team: {$this->teamAbv} in season: {$this->season}");
            }
        } else {
            \Log::error("Failed to fetch schedule for team: {$this->teamAbv} in season: {$this->season}. Status: {$response->status()}");
        }
    }
}
