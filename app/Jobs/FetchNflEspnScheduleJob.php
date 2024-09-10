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
    public function __construct($seasonYear, $seasonType, $weekNumber)
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

                foreach ($events as $event) {
                    $this->storeEvent($event);
                }

                Log::info('NFL schedule events fetched and stored successfully.');
            } else {
                Log::error('Failed to fetch NFL schedule from ESPN API', ['url' => $url]);
            }
        } catch (Exception $e) {
            Log::error('Error fetching NFL schedule', ['url' => $url, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Store event data into the NFL team schedules table.
     *
     * @param array $event
     */
    protected function storeEvent(array $event)
    {
        $competition = $event['competitions'][0] ?? [];

        // Ensure both competitors exist
        if (!isset($competition['competitors'][0]) || !isset($competition['competitors'][1])) {
            Log::warning('Missing competitors for event', ['event_id' => $event['id']]);
            return; // Skip this event
        }

        $homeTeamEspnId = $competition['competitors'][0]['team']['id'] ?? null;
        $awayTeamEspnId = $competition['competitors'][1]['team']['id'] ?? null;
        $shortName = $event['shortName'] ?? 'Unknown'; // e.g., "BAL @ KC"

        // Try to match using espn_id first
        $homeTeam = NflTeam::where('espn_id', $homeTeamEspnId)->first();
        $awayTeam = NflTeam::where('espn_id', $awayTeamEspnId)->first();

        // Fallback: Try to match using short_name if espn_id matching fails
        if (!$homeTeam || !$awayTeam) {
            if (strpos($shortName, ' at ') !== false) {
                [$awayTeamAbv, $homeTeamAbv] = explode(' at ', $shortName);
                $homeTeam = NflTeam::where('team_abv', $homeTeamAbv)->first();
                $awayTeam = NflTeam::where('team_abv', $awayTeamAbv)->first();
            }
        }

        // Only proceed if both teams are found
        if ($homeTeam && $awayTeam) {
            NflTeamSchedule::updateOrCreate(
                [
                    // Match on home_team_id and away_team_id only
                    'home_team_id' => $homeTeam->id,
                    'away_team_id' => $awayTeam->id,
                ],
                [
                    'espn_event_id' => $event['id'], // Ensure this is stored
                    'uid' => $event['uid'],
                    'status_type_detail' => $event['status']['type']['detail'] ?? null,
                    'home_team_record' => $competition['competitors'][0]['records'][0]['summary'] ?? null,
                    'away_team_record' => $competition['competitors'][1]['records'][0]['summary'] ?? null,
                    'neutral_site' => $competition['neutralSite'] ?? null,
                    'conference_competition' => $competition['conferenceCompetition'] ?? null,
                    'attendance' => $competition['attendance'] ?? null,
                    'name' => $event['name'],
                    'short_name' => $event['shortName'],
                    'game_date' => date('Y-m-d', strtotime($event['date'])), // Store game_date
                ]
            );

            Log::info("NFL team schedule updated/created for Home: {$homeTeam->id}, Away: {$awayTeam->id}");
        } else {
            Log::warning('Team not found for event', [
                'short_name' => $shortName,
                'home_team_espn_id' => $homeTeamEspnId,
                'away_team_espn_id' => $awayTeamEspnId,
            ]);
        }
    }
}
