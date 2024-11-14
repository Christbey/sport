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
use Symfony\Component\DomCrawler\Crawler;

class FetchCollegeBasketballScoreboardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $dateInput;

    public function __construct($dateInput)
    {
        $this->dateInput = $dateInput;
        Log::info("Job initialized for date: $dateInput.");
    }

    public function handle()
    {
        $client = new Client();
        $url = "https://www.espn.com/mens-college-basketball/schedule/_/date/{$this->dateInput}";

        try {
            Log::info("Fetching schedule data from URL: $url.");
            $response = $client->get($url, [
                'headers' => ['User-Agent' => 'Mozilla/5.0'],
            ]);

            $html = $response->getBody()->getContents();
            Log::info('Fetched HTML content.');

            $crawler = new Crawler($html);

            // Locate the schedule table with the specified class names
            $crawler->filter('.ScheduleTables.mb5.ScheduleTables--ncaam.ScheduleTables--basketball')->each(function (Crawler $table) {
                $table->filter('tr.Table__TR')->each(function (Crawler $row) {
                    try {
                        // Check if team elements exist before accessing
                        $team1Data = $row->filter('.away a.AnchorLink');
                        $team2Data = $row->filter('.colspan__col .Table__Team a.AnchorLink');

                        if (!$team1Data->count() || !$team2Data->count()) {
                            Log::warning('Could not find team elements in row, skipping row.');
                            return;
                        }

                        // Extract team1 and team2 IDs and names
                        $team1Url = $team1Data->first()->attr('href');
                        $team1Id = $this->extractTeamIdFromUrl($team1Url);
                        $team1Name = trim($team1Data->first()->text());

                        $team2Url = $team2Data->first()->attr('href');
                        $team2Id = $this->extractTeamIdFromUrl($team2Url);
                        $team2Name = trim($team2Data->first()->text());

                        // Match both teams by team_id in the database
                        $team1 = CollegeBasketballTeam::where('team_id', $team1Id)->first();
                        $team2 = CollegeBasketballTeam::where('team_id', $team2Id)->first();

                        if (!$team1 || !$team2) {
                            Log::warning("No database match for matchup: {$team1Name} (ID: $team1Id) vs. {$team2Name} (ID: $team2Id)");
                            return;
                        }

                        // Check for rank if present in span.pr2
                        $team1RankNode = $row->filter('.away .pr2');
                        $team2RankNode = $row->filter('.colspan__col .pr2');

                        $homeRank = $team2RankNode->count() ? (int)$team2RankNode->text() : null;
                        $awayRank = $team1RankNode->count() ? (int)$team1RankNode->text() : null;

                        // Extract game time from the relevant link
                        $gameTimeNode = $row->filter('.date__col a.AnchorLink');
                        $gameTime = $gameTimeNode->count() ? trim($gameTimeNode->text()) : null;
                        $formattedGameTime = $gameTime ? Carbon::parse($gameTime)->format('H:i:s') : null;

                        // Extract event_id from the link containing the gameId
                        $eventLinkNode = $row->filter('.Schedule__liveLink');
                        $eventId = null;
                        if ($eventLinkNode->count()) {
                            preg_match('/gameId\/(\d+)/', $eventLinkNode->attr('href'), $eventIdMatches);
                            $eventId = $eventIdMatches[1] ?? null;
                        }

                        // Format the game date
                        $formattedGameDate = Carbon::createFromFormat('Ymd', $this->dateInput)->toDateString();

                        // Store game information in the CollegeBasketballGame model
                        $game = CollegeBasketballGame::firstOrNew([
                            'home_team_id' => $team2->id, // Assuming team2 is home based on '@' symbol in text
                            'away_team_id' => $team1->id,
                            'game_date' => $formattedGameDate,
                        ]);

                        $game->event_id = $eventId;
                        $game->game_time = $formattedGameTime;
                        $game->location = $row->filter('.venue__col div')->count() ? $row->filter('.venue__col div')->text() : 'Unknown Location';
                        $game->matchup = "{$team1->name} vs. {$team2->name}";
                        $game->home_team = $team2->name; // Using team name from database
                        $game->away_team = $team1->name; // Using team name from database
                        $game->home_rank = $homeRank;
                        $game->away_rank = $awayRank;
                        $game->is_completed = false;

                        $game->save();

                        Log::info("Stored game: {$game->matchup} at {$game->location} on {$formattedGameDate} at {$formattedGameTime}");
                    } catch (Exception $e) {
                        Log::error('Error processing row: ' . $e->getMessage());
                    }
                });
            });

            Log::info('Schedule data processed successfully.');
        } catch (Exception $e) {
            Log::error('Error fetching schedule data: ' . $e->getMessage());
        }
    }

    /**
     * Extracts team_id from the team URL.
     */
    private function extractTeamIdFromUrl($url)
    {
        preg_match('/\/id\/(\d+)\//', $url, $matches);
        return $matches[1] ?? null;
    }
}
