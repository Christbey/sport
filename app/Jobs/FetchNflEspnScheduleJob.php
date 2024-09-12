<?php

namespace App\Jobs;

use App\Models\NflTeamSchedule;
use App\Models\NflTeam;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchNflEspnScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $seasonYear;
    protected $seasonType;
    protected $weekNumber;

    /**
     * Create a new job instance.
     *
     * @param int $seasonYear
     * @param int $seasonType
     * @param int $weekNumber
     */
    public function __construct(int $seasonYear, int $seasonType, $weekNumber)
    {
        $this->seasonYear = $seasonYear;
        $this->seasonType = $seasonType;
        $this->weekNumber = $weekNumber;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $url = "https://site.api.espn.com/apis/site/v2/sports/football/nfl/scoreboard?dates={$this->seasonYear}&seasontype={$this->seasonType}&week={$this->weekNumber}";

        try {
            $response = Http::get($url);

            if ($response->successful()) {
                $events = $response->json()['events'] ?? [];

                if (empty($events)) {
                    Log::warning("No events found for URL: $url");
                    return;
                }

                foreach ($events as $event) {
                    $this->storeEvent($event);
                }

                Log::info('NFL schedule events fetched and stored successfully.');
            } else {
                Log::error('Failed to fetch NFL schedule from ESPN API', [
                    'url' => $url,
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                ]);
            }
        } catch (Exception $e) {
            Log::error('Error fetching NFL schedule', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Store event data into the NFL team schedules table.
     *
     * @param array $event
     */
    protected function storeEvent(array $event)
    {
        $competition = $event['competitions'][0] ?? null;

        if (!$competition || !isset($competition['competitors'][0], $competition['competitors'][1])) {
            Log::warning('Missing competitors for event', ['event_id' => $event['id']]);
            return;
        }

        $homeTeamEspnId = $competition['competitors'][0]['team']['id'] ?? null;
        $awayTeamEspnId = $competition['competitors'][1]['team']['id'] ?? null;
        $shortName = $event['shortName'] ?? 'Unknown';

        // Log if any data is missing
        if (!$homeTeamEspnId || !$awayTeamEspnId) {
            Log::warning('Missing team IDs', [
                'home_team_espn_id' => $homeTeamEspnId,
                'away_team_espn_id' => $awayTeamEspnId,
                'event_id' => $event['id'],
            ]);
            return;
        }

        // Try to match teams by ESPN ID
        $homeTeam = NflTeam::where('espn_id', $homeTeamEspnId)->first();
        $awayTeam = NflTeam::where('espn_id', $awayTeamEspnId)->first();

        // Fallback to team abbreviations if ESPN IDs are not available
        if (!$homeTeam || !$awayTeam) {
            if (strpos($shortName, ' @ ') !== false) {
                [$awayTeamAbv, $homeTeamAbv] = explode(' @ ', $shortName);
                $homeTeam = NflTeam::where('team_abv', $homeTeamAbv)->first();
                $awayTeam = NflTeam::where('team_abv', $awayTeamAbv)->first();
            }
        }

        // Extract additional details
        $homePts = $competition['competitors'][0]['score'] ?? null;
        $awayPts = $competition['competitors'][1]['score'] ?? null;
        $gameStatus = $event['status']['type']['description'] ?? 'N/A';
        $gameStatusCode = $event['status']['type']['id'] ?? 'N/A';
        $gameTime = $event['date'] ?? 'N/A';
        $gameTimeEpoch = $gameTime ? strtotime($gameTime) : 'N/A';

        // Check if the game has completed (i.e., a winner is defined)
        $homeResult = isset($competition['competitors'][0]['winner'])
            ? ($competition['competitors'][0]['winner'] ? 'Win' : 'Loss')
            : null;
        $awayResult = isset($competition['competitors'][1]['winner'])
            ? ($competition['competitors'][1]['winner'] ? 'Win' : 'Loss')
            : null;

        // Log for debugging
        Log::info('Event Details:', [
            'event_id' => $event['id'],
            'home_team' => $homeTeamEspnId,
            'away_team' => $awayTeamEspnId,
            'home_pts' => $homePts,
            'away_pts' => $awayPts,
            'home_result' => $homeResult,
            'away_result' => $awayResult,
            'game_status' => $gameStatus,
        ]);

        // Only store if both teams are matched
        if ($homeTeam && $awayTeam) {
            NflTeamSchedule::updateOrCreate(
                [
                    'home_team_id' => $homeTeam->id,
                    'away_team_id' => $awayTeam->id,
                ],
                [
                    'espn_event_id' => $event['id'],
                    'uid' => $event['uid'],
                    'status_type_detail' => $event['status']['type']['detail'] ?? null,
                    'home_team_record' => $competition['competitors'][0]['records'][0]['summary'] ?? null,
                    'away_team_record' => $competition['competitors'][1]['records'][0]['summary'] ?? null,
                    'neutral_site' => $competition['neutralSite'] ?? false,
                    'conference_competition' => $competition['conferenceCompetition'] ?? false,
                    'attendance' => $competition['attendance'] ?? 0,
                    'home_pts' => $homePts,
                    'away_pts' => $awayPts,
                    'home_result' => $homeResult, // Only set if the winner is determined
                    'away_result' => $awayResult, // Only set if the winner is determined
                    'game_status' => $gameStatus,
                    'game_status_code' => $gameStatusCode,
                    'game_time' => $gameTime,
                    'game_time_epoch' => $gameTimeEpoch,
                ]
            );

            Log::info("NFL team schedule updated/created for Home: {$homeTeam->team_abv}, Away: {$awayTeam->team_abv}");
        } else {
            Log::warning('Teams not found for event', [
                'short_name' => $shortName,
                'home_team_espn_id' => $homeTeamEspnId,
                'away_team_espn_id' => $awayTeamEspnId,
            ]);
        }
    }
}
