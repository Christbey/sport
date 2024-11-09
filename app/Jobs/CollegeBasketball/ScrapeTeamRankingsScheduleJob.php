<?php

namespace App\Jobs\CollegeBasketball;

use App\Models\CollegeBasketballGame;
use App\Models\CollegeBasketballTeam;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeTeamRankingsScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $date;

    public function __construct($date)
    {
        $this->date = $date;
    }

    public function handle()
    {
        $client = new Client();
        $url = 'https://www.teamrankings.com/ncb/schedules/?date=' . $this->date;

        try {
            Log::info("Scraping data for date: {$this->date}");
            $response = $client->request('GET', $url);
            $htmlContent = $response->getBody()->getContents();
            $crawler = new Crawler($htmlContent);

            $crawler->filter('.tr-table.datatable.scrollable tbody tr')->each(function ($row) {
                $rank = $row->filter('td')->eq(0)->text();
                $hotnessScore = $row->filter('td')->eq(1)->text();
                $matchup = $row->filter('td')->eq(2)->text();
                $gameTime = $row->filter('td')->eq(3)->text();
                $location = $row->filter('td')->eq(4)->text();

                // Extract team names and ranks from the matchup
                preg_match_all('/#(\d+)\s([A-Za-z\s]+)\s(vs\.|at)\s#(\d+)\s([A-Za-z\s]+)/', $matchup, $matches);

                if (isset($matches[1][0], $matches[2][0], $matches[4][0], $matches[5][0])) {
                    $awayRank = (int)$matches[1][0];
                    $awayTeamName = trim($matches[2][0]);
                    $homeRank = (int)$matches[4][0];
                    $homeTeamName = trim($matches[5][0]);

                    $awayTeam = CollegeBasketballTeam::where('name', $awayTeamName)->first();
                    $homeTeam = CollegeBasketballTeam::where('name', $homeTeamName)->first();

                    if (is_null($homeTeam) || is_null($awayTeam)) {
                        Log::warning("Skipping game $matchup for date {$this->date} due to missing team IDs.");
                        return;
                    }

                    $game = CollegeBasketballGame::firstOrNew([
                        'home_team_id' => $homeTeam->id,
                        'away_team_id' => $awayTeam->id,
                        'game_date' => $this->date,
                    ]);

                    $game->hotness_score = $game->hotness_score ?? (float)$hotnessScore;
                    $game->game_time = $game->game_time ?? $gameTime;
                    $game->location = $game->location ?? $location;
                    $game->matchup = $game->matchup ?? $matchup;
                    $game->home_rank = $game->home_rank ?? $homeRank;
                    $game->away_rank = $game->away_rank ?? $awayRank;
                    $game->home_team = $game->home_team ?? $homeTeamName;
                    $game->away_team = $game->away_team ?? $awayTeamName;

                    $game->save();

                    Log::info("Stored game for date {$this->date}: $matchup - Hotness Score: $hotnessScore, Time: $gameTime, Location: $location, Home Rank: $homeRank, Away Rank: $awayRank");
                } else {
                    Log::info("Could not parse teams for matchup on {$this->date}: $matchup");
                }
            });
        } catch (Exception $e) {
            Log::error("Error scraping data for date {$this->date}: " . $e->getMessage());
        }
    }
}
