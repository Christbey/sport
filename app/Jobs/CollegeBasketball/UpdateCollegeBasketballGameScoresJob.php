<?php

namespace App\Jobs\CollegeBasketball;

use App\Models\CollegeBasketballGame;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use Symfony\Component\DomCrawler\Crawler;

class UpdateCollegeBasketballGameScoresJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $eventId;

    public function __construct($eventId)
    {
        $this->eventId = $eventId;
    }

    public function handle()
    {
        $client = new Client();
        $url = "https://www.espn.com/mens-college-basketball/game/_/gameId/{$this->eventId}";

        try {
            Log::info("Fetching game data from URL: $url.");
            $response = $client->get($url, [
                'headers' => ['User-Agent' => 'Mozilla/5.0'],
            ]);

            $html = $response->getBody()->getContents();
            $crawler = new Crawler($html);

            // Locate the game time/status
            $gameTimeNode = $crawler->filter('.ScoreCell__Time--in, .ScoreCell__Time');
            $gameTime = $gameTimeNode->count() ? trim($gameTimeNode->text()) : 'Final';

            // Check if the game is live (e.g., game time includes "1st" or "2nd")
            $isLive = strpos($gameTime, '1st') !== false || strpos($gameTime, '2nd') !== false;

            // Locate scores for home and away teams
            $teamScoreNodes = $crawler->filter('.Table__TBODY .Table__TR');
            if ($teamScoreNodes->count() < 2) {
                Log::warning("Could not find score data for event ID: {$this->eventId}");
                return;
            }

            // Assuming first row is home team and second row is away team in Table__TR structure
            $homeTeamScore = (int)$teamScoreNodes->eq(0)->filter('.Table__TD')->last()->text();
            $awayTeamScore = (int)$teamScoreNodes->eq(1)->filter('.Table__TD')->last()->text();

            // Retrieve and update the game record in the database
            $game = CollegeBasketballGame::where('event_id', $this->eventId)->first();

            if ($game) {
                $game->home_team_score = $homeTeamScore;
                $game->away_team_score = $awayTeamScore;
                $game->game_time = $isLive ? $gameTime : 'Final';
                $game->is_completed = !$isLive;

                $game->save();

                Log::info("Updated game: {$game->matchup} with scores - Home: {$homeTeamScore}, Away: {$awayTeamScore}, Time: {$gameTime}");
            } else {
                Log::warning("No game record found for event ID: {$this->eventId}");
            }
        } catch (Exception $e) {
            Log::error("Error fetching game data for event ID: {$this->eventId} - " . $e->getMessage());
        }
    }
}
