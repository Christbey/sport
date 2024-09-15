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
        // Find the existing record, or create a new one if it doesn't exist
        $nflTeamSchedule = NflTeamSchedule::firstOrNew(['game_id' => $game['gameID']]);

        // Set the fields only if they are defined in the incoming data
        $nflTeamSchedule->team_abv = $this->teamAbv;
        $nflTeamSchedule->season_type = $game['seasonType'];
        $nflTeamSchedule->away_team = $game['away'];
        $nflTeamSchedule->home_team_id = $game['teamIDHome'];
        $nflTeamSchedule->game_date = $game['gameDate'];
        $nflTeamSchedule->game_week = $game['gameWeek'];
        $nflTeamSchedule->away_team_id = $game['teamIDAway'];
        $nflTeamSchedule->home_team = $game['home'];

        // Only update if the value is not null and not empty
        if (!empty($game['awayResult'])) {
            $nflTeamSchedule->away_result = $game['awayResult'];
        }

        if (!empty($game['homeResult'])) {
            $nflTeamSchedule->home_result = $game['homeResult'];
        }

        if (!empty($game['gameStatus'])) {
            $nflTeamSchedule->game_status = $game['gameStatus'];
        }

        // Handle optional points
        $nflTeamSchedule->home_pts = $game['homePts'] ?? $nflTeamSchedule->home_pts;
        $nflTeamSchedule->away_pts = $game['awayPts'] ?? $nflTeamSchedule->away_pts;

        // Handle optional game time
        $nflTeamSchedule->game_time = $game['gameTime'] ?? $nflTeamSchedule->game_time;
        $nflTeamSchedule->game_time_epoch = !empty($game['gameTime_epoch']) ? (int)$game['gameTime_epoch'] : $nflTeamSchedule->game_time_epoch;

        // Handle game status code
        $nflTeamSchedule->game_status_code = $game['gameStatusCode'] ?? $nflTeamSchedule->game_status_code;

        // Save the record to the database
        $nflTeamSchedule->save();
    }
}
