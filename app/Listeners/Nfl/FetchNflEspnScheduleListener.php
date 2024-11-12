<?php

namespace App\Listeners\Nfl;

use App\Events\Nfl\FetchNflEspnScheduleEvent;
use App\Models\Nfl\NflTeam;
use App\Models\Nfl\NflTeamSchedule;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchNflEspnScheduleListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(FetchNflEspnScheduleEvent $event)
    {
        $url = "https://site.api.espn.com/apis/site/v2/sports/football/nfl/scoreboard?dates={$event->seasonYear}&seasontype={$event->seasonType}&week={$event->weekNumber}";

        try {
            $response = Http::get($url);

            if ($response->successful()) {
                $events = $response->json()['events'] ?? [];

                if (empty($events)) {
                    Log::warning("No events found for URL: $url");
                    return;
                }

                foreach ($events as $eventData) {
                    $this->storeEvent($eventData);
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

        $homeTeamData = $competition['competitors'][0];
        $awayTeamData = $competition['competitors'][1];

        $homeTeamEspnId = $homeTeamData['team']['id'] ?? null;
        $awayTeamEspnId = $awayTeamData['team']['id'] ?? null;
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
            if (str_contains($shortName, ' @ ')) {
                [$awayTeamAbv, $homeTeamAbv] = explode(' @ ', $shortName);
                $homeTeam = NflTeam::where('team_abv', $homeTeamAbv)->first();
                $awayTeam = NflTeam::where('team_abv', $awayTeamAbv)->first();
            }
        }

        // Extract additional details
        $homePts = $homeTeamData['score'] ?? null;
        $awayPts = $awayTeamData['score'] ?? null;
        $gameStatus = $event['status']['type']['description'] ?? 'N/A';
        $gameStatusCode = $event['status']['type']['id'] ?? 'N/A';
        $gameTime = $event['date'] ?? 'N/A';
        $gameTimeEpoch = $gameTime ? strtotime($gameTime) : 'N/A';

        // Determine win/loss
        $homeResult = isset($homeTeamData['winner'])
            ? ($homeTeamData['winner'] ? 'W' : 'L')
            : null;
        $awayResult = isset($awayTeamData['winner'])
            ? ($awayTeamData['winner'] ? 'W' : 'L')
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
            $isConferenceCompetition = $this->isConferenceCompetition($homeTeam, $awayTeam);

            NflTeamSchedule::updateOrCreate(
                [
                    'home_team_id' => $homeTeam->id,
                    'away_team_id' => $awayTeam->id,
                    'game_date' => date('Y-m-d', strtotime($event['date'])),
                ],
                [
                    'espn_event_id' => $event['id'],
                    'uid' => $event['uid'],
                    'status_type_detail' => $event['status']['type']['detail'] ?? null,
                    'home_team_record' => $homeTeamData['records'][0]['summary'] ?? null,
                    'away_team_record' => $awayTeamData['records'][0]['summary'] ?? null,
                    'neutral_site' => $competition['neutralSite'] ?? false,
                    'conference_competition' => $isConferenceCompetition ?? $competition['conferenceCompetition'] ?? false,
                    'attendance' => $competition['attendance'] ?? 0,
                    'home_pts' => $homePts,
                    'away_pts' => $awayPts,
                    'home_result' => $homeResult, // Only set if the winner is determined
                    'away_result' => $awayResult, // Only set if the winner is determined
                    'game_status' => $gameStatus,
                    'game_status_code' => $gameStatusCode,
                    'game_time' => $gameTime,
                    'game_time_epoch' => $gameTimeEpoch,
                    'name' => $event['name'],
                    'short_name' => $event['shortName'],
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
