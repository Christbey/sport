<?php

namespace App\Console\Commands;

use App\Models\CollegeBasketballGame;
use App\Models\CollegeBasketballTeam;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class CollegeBasketballScoreboard extends Command
{
    protected $signature = 'college-basketball:scoreboard {date?}';
    protected $description = 'Fetch and store specific data for college basketball scoreboard';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $client = new Client();

        $dateInput = $this->argument('date') ?? now()->format('Ymd');
        $date = Carbon::createFromFormat('Ymd', $dateInput)->format('Y-m-d');

        $url = 'https://site.api.espn.com/apis/site/v2/sports/basketball/mens-college-basketball/scoreboard?limit=1000';

        try {
            $response = $client->get($url, [
                'query' => [
                    'dates' => $dateInput
                ],
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0'
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data['events'])) {
                $this->error("No games found for date: $date.");
                return;
            }

            foreach ($data['events'] as $event) {
                $eventId = $event['id'];
                $eventUid = $event['uid'];
                $attendance = $event['competitions'][0]['attendance'] ?? null;
                $gameTime = Carbon::parse($event['date'])->format('H:i:s');  // Format game time

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

                    // Find existing team by `team_id` only
                    $team = CollegeBasketballTeam::where('team_id', $teamId)->first();

                    // Skip if team is not found in the database
                    if (!$team) {
                        $this->warn("Team with ID {$teamId} not found in database. Skipping team.");
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

                // Skip the game if either team ID is missing
                if (is_null($homeTeamId) || is_null($awayTeamId)) {
                    $this->warn("Skipping game {$event['name']} due to missing team IDs.");
                    continue;
                }

                // Retrieve or create the game record
                $game = CollegeBasketballGame::firstOrNew([
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId,
                    'game_date' => $date,
                ]);

                // Only update fields if they are currently null or missing
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

                // Save changes only if there are new values to update
                $game->save();

                $this->info("Stored game: {$event['name']} - Attendance: {$attendance}");
            }

            $this->info('Scoreboard data stored successfully.');
        } catch (Exception $e) {
            $this->error('Error fetching data: ' . $e->getMessage());
        }
    }
}
