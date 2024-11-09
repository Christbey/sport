<?php

namespace App\Jobs\CollegeBasketball;

use App\Models\CollegeBasketballGame;
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
    }

    public function handle()
    {
        $client = new Client();
        $date = Carbon::createFromFormat('Ymd', $this->dateInput)->format('Y-m-d');
        $url = 'https://site.api.espn.com/apis/site/v2/sports/basketball/mens-college-basketball/scoreboard?limit=1000';

        try {
            $response = $client->get($url, [
                'query' => ['dates' => $this->dateInput],
                'headers' => ['User-Agent' => 'Mozilla/5.0'],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data['events'])) {
                Log::info("No games found for date: $date.");
                return;
            }

            foreach ($data['events'] as $event) {
                $eventId = $event['id'];
                $eventUid = $event['uid'];
                $attendance = $event['competitions'][0]['attendance'] ?? null;
                $gameTime = Carbon::parse($event['date'])->format('H:i:s');

                $homeTeamScore = null;
                $awayTeamScore = null;
                $homeTeamId = null;
                $awayTeamId = null;
                $isCompleted = false;

                foreach ($event['competitions'][0]['competitors'] as $competitor) {
                    $teamData = $competitor['team'];
                    $teamId = $teamData['id'];
                    $teamScore = (int)($competitor['score'] ?? 0);
                    $homeAway = $competitor['homeAway'];
                    $isWinner = isset($competitor['winner']) ? (bool)$competitor['winner'] : null;
                    $teamRank = $competitor['rank'] ?? null;

                    $team = CollegeBasketballTeam::where('team_id', $teamId)->first();

                    if (!$team) {
                        Log::warning("Team with ID {$teamId} not found in database. Skipping team.");
                        continue;
                    }

                    if ($homeAway === 'home') {
                        $homeTeamScore = $teamScore;
                        $homeTeamId = $team->id;
                        $homeTeamRank = $teamRank;
                        $homeTeamName = $teamData['displayName'];
                    } else {
                        $awayTeamScore = $teamScore;
                        $awayTeamId = $team->id;
                        $awayTeamRank = $teamRank;
                        $awayTeamName = $teamData['displayName'];
                    }

                    if ($isWinner !== null) {
                        $isCompleted = true;
                    }
                }

                if (is_null($homeTeamId) || is_null($awayTeamId)) {
                    Log::warning("Skipping game {$event['name']} due to missing team IDs.");
                    continue;
                }

                $game = CollegeBasketballGame::firstOrNew([
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId,
                    'game_date' => $date,
                ]);

                $game->event_id = $game->event_id ?? $eventId;
                $game->event_uid = $game->event_uid ?? $eventUid;
                $game->attendance = $game->attendance ?? $attendance;
                $game->game_time = $game->game_time ?? $gameTime;
                $game->home_team_score = $game->home_team_score ?? $homeTeamScore;
                $game->away_team_score = $game->away_team_score ?? $awayTeamScore;
                $game->is_completed = $game->is_completed || $isCompleted;
                $game->home_rank = $game->home_rank ?? $homeTeamRank;
                $game->away_rank = $game->away_rank ?? $awayTeamRank;
                $game->home_team = $game->home_team ?? $homeTeamName;
                $game->away_team = $game->away_team ?? $awayTeamName;

                $game->save();

                Log::info("Stored game: {$event['name']} - Attendance: {$attendance}");
            }

            Log::info('Scoreboard data stored successfully.');
        } catch (Exception $e) {
            Log::error('Error fetching data: ' . $e->getMessage());
        }
    }
}
