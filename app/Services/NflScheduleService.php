<?php

namespace App\Services;

use App\Models\Nfl\NflTeamSchedule;
use App\Models\Nfl\NflTeamStat;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;

class NflScheduleService
{
    private $client;
    private $apiHost = 'tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com';
    private $apiKey;
    private $teams = [
        'ARI', 'ATL', 'BAL', 'BUF', 'CAR', 'CHI', 'CIN', 'CLE',
        'DAL', 'DEN', 'DET', 'GB', 'HOU', 'IND', 'JAX', 'KC',
        'LAC', 'LAR', 'LV', 'MIA', 'MIN', 'NE', 'NO', 'NYG',
        'NYJ', 'PHI', 'PIT', 'SEA', 'SF', 'TB', 'TEN', 'WSH'
    ];

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = config('services.rapidapi.key');
    }

    public function updateScheduleForWeek(string $season, int $weekNumber, string $seasonType)
    {
        $teams = config('nfl.teams', ['BUF', 'NYJ', 'MIA', 'NE', 'BAL', 'CIN', 'CLE', 'PIT',
            'HOU', 'IND', 'JAX', 'TEN', 'DEN', 'KC', 'LV', 'LAC', 'DAL', 'NYG', 'PHI',
            'WSH', 'CHI', 'DET', 'GB', 'MIN', 'ATL', 'CAR', 'NO', 'TB', 'ARI', 'LAR',
            'SF', 'SEA']);

        foreach ($teams as $team) {
            $schedule = $this->fetchTeamSchedule($team, $season);

            foreach ($schedule['body']['schedule'] as $game) {
                if ($this->matchesWeekCriteria($game, $weekNumber, $seasonType)) {
                    $gameDetails = $this->fetchGameDetails($game['gameID']);
                    $boxScore = $this->fetchBoxScore($game['gameID']);

                    $this->upsertGame($game, $gameDetails, $season);
                    if ($boxScore) {
                        $this->processGameStats($boxScore, $game['gameID']);
                    }
                }
            }
        }
    }

    private function fetchTeamSchedule(string $teamAbv, string $season)
    {
        $response = $this->client->request('GET',
            "https://{$this->apiHost}/getNFLTeamSchedule", [
                'query' => [
                    'teamAbv' => $teamAbv,
                    'season' => $season
                ],
                'headers' => $this->getHeaders()
            ]);

        $data = json_decode($response->getBody(), true);
        if (!isset($data['body']['schedule'])) {
            throw new Exception("Invalid response for team {$teamAbv}");
        }
        return $data;
    }

    private function getHeaders(): array
    {
        return [
            'x-rapidapi-host' => $this->apiHost,
            'x-rapidapi-key' => $this->apiKey,
        ];
    }

    private function matchesWeekCriteria(array $game, int $weekNumber, string $seasonType): bool
    {
        $weekMatch = $game['gameWeek'] === "Week {$weekNumber}" ||
            $game['gameWeek'] === "Preseason Week {$weekNumber}";

        $typeMap = [
            '1' => 'Preseason',
            '2' => 'Regular Season',
            '3' => 'Postseason'
        ];

        return $weekMatch && $game['seasonType'] === $typeMap[$seasonType];
    }

    private function fetchGameDetails(string $gameId)
    {
        $response = $this->client->request('GET',
            "https://{$this->apiHost}/getNFLGameInfo", [
                'query' => ['gameID' => $gameId],
                'headers' => $this->getHeaders()
            ]);

        return json_decode($response->getBody(), true);
    }

    private function fetchBoxScore(string $gameId)
    {
        $response = $this->client->request('GET',
            "https://{$this->apiHost}/getNFLBoxScore", [
                'query' => [
                    'gameID' => $gameId,
                    'playByPlay' => 'true',
                    'fantasyPoints' => 'true',

                ],
                'headers' => $this->getHeaders()
            ]);

        return json_decode($response->getBody(), true);
    }


    private function upsertGame(array $scheduleData, array $gameDetails, string $season)
    {
        $espnId = $gameDetails['body']['espnID'] ?? null;
        $gameId = $scheduleData['gameID'] ?? null;

        if (!$espnId || !$gameId || !isset($scheduleData['home'])) {
            return;
        }

        $gameWeek = $scheduleData['gameWeek'];
        $awayTeam = $scheduleData['away'];
        $homeTeam = $scheduleData['home'];
        $gameStatus = $scheduleData['gameStatus'];
        $shortName = "$awayTeam @ $homeTeam";

        $gameData = [
            'game_status' => $gameStatus,
            'away_result' => $scheduleData['awayResult'] ?? null,
            'home_result' => $scheduleData['homeResult'] ?? null,
            'home_pts' => $scheduleData['homePts'] ?? null,
            'away_pts' => $scheduleData['awayPts'] ?? null,
            'attendance' => $gameDetails['body']['attendance'] ?? null,
            'game_status_code' => $scheduleData['gameStatusCode'] ?? null,
            'status_type_detail' => $gameDetails['body']['gameStatus'] ?? 'Final',
            'home_team_record' => $gameDetails['body']['homeTeamRecord'] ?? null,
            'away_team_record' => $gameDetails['body']['awayTeamRecord'] ?? null,
            'conference_competition' => $this->isConferenceCompetition($homeTeam, $awayTeam),
        ];

        $existingGame = NflTeamSchedule::where('espn_event_id', $espnId)
            ->where('team_abv', $homeTeam)
            ->first();

        if ($existingGame) {
            $existingGame->update($gameData);
        } else {
            NflTeamSchedule::create(array_merge($gameData, [
                'team_abv' => $homeTeam,
                'game_id' => $gameId,
                'season' => $season,
                'season_type' => $scheduleData['seasonType'],
                'away_team' => $awayTeam,
                'home_team_id' => $scheduleData['teamIDHome'],
                'game_date' => Carbon::createFromFormat('Ymd', $scheduleData['gameDate']),
                'game_week' => $gameWeek,
                'away_team_id' => $scheduleData['teamIDAway'],
                'home_team' => $homeTeam,
                'game_time' => $scheduleData['gameTime'],
                'game_time_epoch' => $scheduleData['gameTime_epoch'],
                'espn_event_id' => $espnId,
                'neutral_site' => ($gameDetails['body']['neutralSite'] ?? 'False') === 'True',
                'attendance' => $gameDetails['body']['attendance'] ?? null,
                'name' => $gameDetails['body']['name'] ?? null,
                'short_name' => $shortName,
            ]));
        }
    }

    private function isConferenceCompetition(string $homeTeam, string $awayTeam)
    {
        $homeConference = $this->getConference($homeTeam);
        $awayConference = $this->getConference($awayTeam);

        return $homeConference === $awayConference;
    }

    //@TODO: Move this to a config file
    private function getConference(string $teamAbv)
    {
        $conferenceMap = [
            'ARI' => 'NFC', 'ATL' => 'NFC', 'BAL' => 'AFC', 'BUF' => 'AFC',
            'CAR' => 'NFC', 'CHI' => 'NFC', 'CIN' => 'AFC', 'CLE' => 'AFC',
            'DAL' => 'NFC', 'DEN' => 'AFC', 'DET' => 'NFC', 'GB' => 'NFC',
            'HOU' => 'AFC', 'IND' => 'AFC', 'JAX' => 'AFC', 'KC' => 'AFC',
            'LAC' => 'AFC', 'LAR' => 'NFC', 'LV' => 'AFC', 'MIA' => 'AFC',
            'MIN' => 'NFC', 'NE' => 'AFC', 'NO' => 'NFC', 'NYG' => 'NFC',
            'NYJ' => 'AFC', 'PHI' => 'NFC', 'PIT' => 'AFC', 'SEA' => 'NFC',
            'SF' => 'NFC', 'TB' => 'NFC', 'TEN' => 'AFC', 'WSH' => 'NFC'
        ];

        return $conferenceMap[$teamAbv] ?? null;
    }

    private function processGameStats(array $boxScore, string $gameId)
    {
        if (!isset($boxScore['body']['teamStats'])) {
            return;
        }

        foreach (['home', 'away'] as $side) {
            $stats = $boxScore['body']['teamStats'][$side];

            $completionsAttempts = explode('-', $stats['passCompletionsAndAttempts'] ?? '0-0');
            $penalties = explode('-', $stats['penalties'] ?? '0-0');
            $sacksLost = explode('-', $stats['sacksAndYardsLost'] ?? '0-0');
            $thirdDown = explode('-', $stats['thirdDownEfficiency'] ?? '0-0');
            $fourthDown = explode('-', $stats['fourthDownEfficiency'] ?? '0-0');
            $redZone = explode('-', $stats['redZoneScoredAndAttempted'] ?? '0-0');

            NflTeamStat::updateOrCreate(
                [
                    'game_id' => $gameId,
                    'team_id' => $stats['teamID']
                ],
                [
                    'total_yards' => $stats['totalYards'] ?? null,
                    'rushing_attempts' => $stats['rushingAttempts'] ?? null,
                    'rushing_yards' => $stats['rushingYards'] ?? null,
                    'passing_yards' => $stats['passingYards'] ?? null,
                    'passing_completions' => $completionsAttempts[0] ?? 0,
                    'passing_attempts' => $completionsAttempts[1] ?? 0,
                    'interceptions_thrown' => $stats['interceptionsThrown'] ?? null,
                    'fumbles_lost' => $stats['fumblesLost'] ?? null,
                    'turnovers' => $stats['turnovers'] ?? null,
                    'penalties' => $penalties[0] ?? 0,
                    'penalty_yards' => $penalties[1] ?? 0,
                    'sacks_against' => $sacksLost[0] ?? 0,
                    'sacks_yards_lost' => $sacksLost[1] ?? 0,
                    'possession_time' => $stats['possession'] ?? null,
                    'third_down_conversions' => $thirdDown[0] ?? 0,
                    'third_down_attempts' => $thirdDown[1] ?? 0,
                    'fourth_down_conversions' => $fourthDown[0] ?? 0,
                    'fourth_down_attempts' => $fourthDown[1] ?? 0,
                    'total_plays' => $stats['totalPlays'] ?? null,
                    'yards_per_play' => $stats['yardsPerPlay'] ?? null,
                    'first_downs' => $stats['firstDowns'] ?? null,
                    'rushing_first_downs' => $stats['rushingFirstDowns'] ?? null,
                    'passing_first_downs' => $stats['passingFirstDowns'] ?? null,
                    'penalty_first_downs' => $stats['firstDownsFromPenalties'] ?? null,
                    'defensive_interceptions' => $stats['defensiveInterceptions'] ?? null,
                    'defensive_tds' => $stats['defensiveOrSpecialTeamsTds'] ?? null,
                    'total_drives' => $stats['totalDrives'] ?? null,
                    'yards_per_rush' => $stats['yardsPerRush'] ?? null,
                    'yards_per_pass' => $stats['yardsPerPass'] ?? null,
                    'redzone_scores' => $redZone[0] ?? 0,
                    'redzone_attempts' => $redZone[1] ?? 0,
                    'team_abr' => $stats['teamAbv'] ?? null,
                ]
            );

        }
    }
}