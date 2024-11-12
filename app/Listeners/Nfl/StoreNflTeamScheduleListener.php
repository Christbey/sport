<?php

namespace App\Listeners\Nfl;

use App\Events\Nfl\StoreNflTeamScheduleEvent;
use App\Models\Nfl\NflTeam;
use App\Models\Nfl\NflTeamSchedule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StoreNflTeamScheduleListener implements ShouldQueue
{
    use InteractsWithQueue;

    public $delay = 5; // Delay execution by 5 seconds

    public function handle(StoreNflTeamScheduleEvent $event)
    {
        $cacheKey = "nfl_schedule_{$event->teamAbv}_{$event->season}";

        $scheduleData = Cache::remember($cacheKey, now()->addHours(48), function () use ($event) {
            $response = Http::withHeaders([
                'x-rapidapi-host' => 'tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com',
                'x-rapidapi-key' => config('services.rapidapi.key'),
            ])->get('https://tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com/getNFLTeamSchedule', [
                'teamAbv' => $event->teamAbv,
                'season' => $event->season,
            ]);

            if ($response->ok()) {
                return $response->json('body.schedule');
            } else {
                Log::error("Failed to fetch schedule for team: {$event->teamAbv} in season: {$event->season}. Status: {$response->status()}");
                return null;
            }
        });

        if ($scheduleData) {
            foreach ($scheduleData as $game) {
                $this->storeGameData($game, $event->teamAbv);
            }
        }
    }

    protected function storeGameData($game, $teamAbv)
    {
        // Find the home and away teams based on their IDs or abbreviations
        $homeTeam = NflTeam::where('team_abv', $game['home'])->orWhere('espn_id', $game['teamIDHome'])->first();
        $awayTeam = NflTeam::where('team_abv', $game['away'])->orWhere('espn_id', $game['teamIDAway'])->first();

        if (!$homeTeam || !$awayTeam) {
            Log::warning('Teams not found for game', [
                'game_id' => $game['gameID'],
                'home_team' => $game['home'],
                'away_team' => $game['away'],
            ]);
            return;
        }

        // Convert game date and time to proper formats
        $gameDate = date('Y-m-d', strtotime($game['gameDate']));
        $gameTime = isset($game['gameTime']) ? date('H:i:s', strtotime($game['gameTime'])) : null;
        $gameTimeEpoch = isset($game['gameTime_epoch']) ? (int)$game['gameTime_epoch'] : null;

        // Determine results
        $homeResult = $game['homeResult'] ?? null;
        $awayResult = $game['awayResult'] ?? null;

        // Determine if the game is a conference competition
        $isConferenceCompetition = $this->isConferenceCompetition($homeTeam, $awayTeam);

        // Update or create the schedule entry
        NflTeamSchedule::updateOrCreate(
            [
                'game_id' => $game['gameID'],
            ],
            [
                'team_abv' => $teamAbv,
                'season_type' => $game['seasonType'],
                'home_team_id' => $homeTeam->id,
                'away_team_id' => $awayTeam->id,
                'game_date' => $gameDate,
                'game_week' => $game['gameWeek'],
                'home_team' => $game['home'],
                'away_team' => $game['away'],
                'home_team_record' => $game['homeTeamRecord'] ?? null,
                'away_team_record' => $game['awayTeamRecord'] ?? null,
                'neutral_site' => $game['neutralSite'] ?? false,
                'conference_competition' => $isConferenceCompetition,
                'attendance' => $game['attendance'] ?? 0,
                'home_pts' => $game['homePts'] ?? null,
                'away_pts' => $game['awayPts'] ?? null,
                'home_result' => $homeResult,
                'away_result' => $awayResult,
                'game_status' => $game['gameStatus'] ?? null,
                'game_status_code' => $game['gameStatusCode'] ?? null,
                'game_time' => $gameTime,
                'game_time_epoch' => $gameTimeEpoch,
                'name' => $game['name'] ?? null,
                'short_name' => $game['shortName'] ?? null,
            ]
        );

        Log::info("NFL team schedule updated/created for Game ID: {$game['gameID']}, Home: {$homeTeam->team_abv}, Away: {$awayTeam->team_abv}");
    }

    /**
     * Determine if the competition is a conference competition.
     *
     * @param NflTeam $homeTeam
     * @param NflTeam $awayTeam
     * @return bool
     */
    private function isConferenceCompetition(NflTeam $homeTeam, NflTeam $awayTeam): bool
    {
        return $homeTeam->conference_abv === $awayTeam->conference_abv;
    }
}
