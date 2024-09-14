<?php

namespace App\Jobs\Nfl;

use App\Models\Nfl\NflTeamSchedule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
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

        // Add a delay of 5 seconds to the job execution
        $this->delay(now()->addSeconds(5));
    }

    public function handle()
    {
        // Create a unique cache key using the team abbreviation and season
        $cacheKey = "nfl_schedule_{$this->teamAbv}_{$this->season}";

        // Cache the data for 48 hours
        $scheduleData = Cache::remember($cacheKey, now()->addHours(48), function () {
            // Fetch the data from the API
            $response = Http::withHeaders([
                'x-rapidapi-host' => 'tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com',
                'x-rapidapi-key' => config('services.rapidapi.key'),
            ])->get('https://tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com/getNFLTeamSchedule', [
                'teamAbv' => $this->teamAbv,
                'season' => $this->season,
            ]);

            if ($response->ok()) {
                return $response->json('body.schedule');
            } else {
                Log::error("Failed to fetch schedule for team: {$this->teamAbv} in season: {$this->season}. Status: {$response->status()}");
                return null;
            }
        });

        // Process the schedule data if it's available
        if ($scheduleData) {
            foreach ($scheduleData as $game) {
                $this->storeGameData($game);
            }
        }
    }

    protected function storeGameData($game)
    {
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
                'away_result' => $game['awayResult'] ?? null,
                'home_result' => $game['homeResult'] ?? null,
                'home_pts' => $game['homePts'] ?? null,
                'away_pts' => $game['awayPts'] ?? null,
                'game_time' => $game['gameTime'] ?? null,
                'game_time_epoch' => !empty($game['gameTime_epoch']) ? (int)$game['gameTime_epoch'] : null,
                'game_status_code' => $game['gameStatusCode'] ?? null,
            ]
        );
    }
}
