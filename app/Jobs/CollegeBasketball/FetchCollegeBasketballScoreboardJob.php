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
                        // Extract team elements
                        $team1Node = $row->filter('td.events__col .Table__Team a.AnchorLink');
                        $team2Node = $row->filter('td.colspan__col .Table__Team a.AnchorLink');

                        if (!$team1Node->count() || !$team2Node->count()) {
                            Log::warning('Could not find team elements in row, skipping row.', ['row_html' => $row->html()]);
                            return;
                        }

                        $team1Name = trim($team1Node->text());
                        $team2Name = trim($team2Node->text());

                        // Match both teams by name in the database
                        $team1 = CollegeBasketballTeam::where('name', $team1Name)->first();
                        $team2 = CollegeBasketballTeam::where('name', $team2Name)->first();

                        if (!$team1 || !$team2) {
                            Log::warning("No database match for matchup: $team1Name vs. $team2Name");
                            return;
                        }

                        // Extract event_id from the teams__col
                        $eventLinkNode = $row->filter('td.teams__col a.AnchorLink');
                        $eventId = null;

                        if ($eventLinkNode->count()) {
                            $href = $eventLinkNode->attr('href');
                            Log::info('Extracted href', ['href' => $href]);

                            if (preg_match('/gameId\/(\d+)/', $href, $eventIdMatches)) {
                                $eventId = $eventIdMatches[1];
                                Log::info('Extracted event_id', ['event_id' => $eventId]);
                            } else {
                                Log::warning('No gameId found in href', ['href' => $href]);
                            }
                        } else {
                            Log::warning('Event link not found in teams__col.', ['row_html' => $row->html()]);
                            return;
                        }

                        // Format game date (assuming it's passed correctly in the constructor)
                        $formattedGameDate = Carbon::createFromFormat('Ymd', $this->dateInput)->toDateString();

                        // Save game information
                        $game = CollegeBasketballGame::firstOrNew([
                            'home_team_id' => $team2->id,
                            'away_team_id' => $team1->id,
                            'game_date' => $formattedGameDate,
                        ]);

                        $game->event_id = $eventId;
                        $game->is_completed = true;
                        $game->save();

                        Log::info("Stored game: $team1Name vs. $team2Name, event_id: $eventId.");
                    } catch (Exception $e) {
                        Log::error('Error processing row: ' . $e->getMessage(), ['row_html' => $row->html()]);
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
