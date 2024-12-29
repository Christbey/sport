<?php

namespace App\Jobs\CollegeBasketball;

use App\Models\CollegeBasketballGame;
use App\Models\CollegeBasketballGameStatistic;
use App\Models\CollegeBasketballTeam;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class FetchCollegeBasketballScoreboardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $dateInput;

    public function __construct($dateInput)
    {
        $this->dateInput = $dateInput;
        Log::info("Job initialized for date: {$dateInput}.");
    }

    public function handle()
    {
        $url = "https://site.api.espn.com/apis/site/v2/sports/basketball/mens-college-basketball/scoreboard?dates={$this->dateInput}";

        try {
            Log::info("Fetching schedule data from URL: {$url}.");
            $data = $this->fetchData($url);

            foreach ($data['events'] as $event) {
                $this->processEvent($event);
            }

            Log::info('Schedule data processed successfully.');
        } catch (Exception $e) {
            Log::error("Error fetching or processing schedule data: {$e->getMessage()}");
        }
    }

    protected function fetchData($url)
    {
        $client = new Client();
        $response = $client->get($url, [
            'headers' => ['User-Agent' => 'Mozilla/5.0'],
        ]);

        return json_decode($response->getBody(), true);
    }

    protected function processEvent(array $event)
    {
        try {
            $eventId = $event['id'];
            $eventUid = $event['uid'] ?? null;
            $venueName = $event['competitions'][0]['venue']['fullName'] ?? null;
            $attendance = $event['competitions'][0]['attendance'] ?? null;
            $gameTime = $event['date'] ?? null;
            $isCompleted = $event['status']['type']['completed'] ?? false;

            $competitors = collect($event['competitions'][0]['competitors']);
            $homeTeamData = $competitors->firstWhere('homeAway', 'home');
            $awayTeamData = $competitors->firstWhere('homeAway', 'away');

            if (!$homeTeamData || !$awayTeamData) {
                Log::warning("Home or away team data missing for event: {$eventId}");
                return;
            }

            $homeTeam = $this->findOrCreateTeam($homeTeamData['team']);
            $awayTeam = $this->findOrCreateTeam($awayTeamData['team']);

            if (!$homeTeam || !$awayTeam) {
                Log::warning("Teams not found or created: {$homeTeamData['team']['displayName']} vs. {$awayTeamData['team']['displayName']}");
                return;
            }

            $game = $this->saveGame($event, $homeTeam, $awayTeam, $venueName, $attendance, $gameTime, $isCompleted);

            $this->saveStatistics($game, 'home', $homeTeamData['statistics'] ?? []);
            $this->saveStatistics($game, 'away', $awayTeamData['statistics'] ?? []);
        } catch (Exception $e) {
            Log::error("Error processing event: {$e->getMessage()}", ['event_id' => $event['id'] ?? 'unknown']);
        }
    }

    protected function findOrCreateTeam(array $teamData)
    {
        return CollegeBasketballTeam::firstOrCreate(
            ['display_name' => $teamData['displayName']],
            [
                'name' => $teamData['name'] ?? null,
                'abbreviation' => $teamData['abbreviation'] ?? null,
                'location' => $teamData['location'] ?? null,
                'conference_id' => $teamData['conferenceId'] ?? null,
            ]
        );
    }

    protected function saveGame($event, $homeTeam, $awayTeam, $venueName, $attendance, $gameTime, $isCompleted)
    {
        $formattedGameDate = Carbon::createFromFormat('Ymd', $this->dateInput)->toDateString();
        $homeTeamData = collect($event['competitions'][0]['competitors'])->firstWhere('homeAway', 'home');
        $awayTeamData = collect($event['competitions'][0]['competitors'])->firstWhere('homeAway', 'away');

        return CollegeBasketballGame::updateOrCreate(
            [
                'event_id' => $event['id'],
                'game_date' => $formattedGameDate,
            ],
            [
                'event_uid' => $event['uid'] ?? null,
                'matchup' => "{$awayTeam->display_name} vs. {$homeTeam->display_name}",
                'home_team_id' => $homeTeam->id,
                'away_team_id' => $awayTeam->id,
                'home_team' => $homeTeam->display_name,
                'away_team' => $awayTeam->display_name,
                'location' => $venueName,
                'attendance' => $attendance,
                'home_team_score' => $homeTeamData['score'] ?? null,
                'away_team_score' => $awayTeamData['score'] ?? null,
                'home_rank' => $homeTeamData['curatedRank']['current'] ?? null,
                'away_rank' => $awayTeamData['curatedRank']['current'] ?? null,
                'game_time' => $gameTime ? Carbon::parse($gameTime)->toDateTimeString() : null,
                'is_completed' => $isCompleted,
                'short_name' => $event['shortName'] ?? null,
                'season_year' => $event['season']['year'] ?? null,
                'season_type' => $event['season']['type'] ?? null,
                'season_slug' => $event['season']['slug'] ?? null,
            ]
        );
    }

    protected function saveStatistics($game, $teamType, $statistics)
    {
        $stats = [
            'rebounds' => $this->getStatValue($statistics, 'rebounds'),
            'assists' => $this->getStatValue($statistics, 'assists'),
            'field_goals_attempted' => $this->getStatValue($statistics, 'fieldGoalsAttempted'),
            'field_goals_made' => $this->getStatValue($statistics, 'fieldGoalsMade'),
            'field_goal_percentage' => $this->getStatValue($statistics, 'fieldGoalPct'),
            'free_throw_percentage' => $this->getStatValue($statistics, 'freeThrowPct'),
            'free_throws_attempted' => $this->getStatValue($statistics, 'freeThrowsAttempted'),
            'free_throws_made' => $this->getStatValue($statistics, 'freeThrowsMade'),
            'points' => $this->getStatValue($statistics, 'points'),
            'three_point_field_goals_attempted' => $this->getStatValue($statistics, 'threePointFieldGoalsAttempted'),
            'three_point_field_goals_made' => $this->getStatValue($statistics, 'threePointFieldGoalsMade'),
            'three_point_field_goal_percentage' => $this->getStatValue($statistics, 'threePointFieldGoalPct'),
        ];

        CollegeBasketballGameStatistic::updateOrCreate(
            [
                'game_id' => $game->id,
                'team_type' => $teamType,
            ],
            $stats
        );
    }

    protected function getStatValue($statistics, $name)
    {
        $stat = collect($statistics)->firstWhere('name', $name);
        return $stat['displayValue'] ?? null;
    }
}
