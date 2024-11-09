<?php

namespace App\Console\Commands;

use App\Models\CollegeBasketballGame;
use App\Models\CollegeBasketballTeam;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeTeamRankingsSchedule extends Command
{
    protected $signature = 'scrape:team-rankings-schedule';
    protected $description = 'Scrapes the Team Rankings schedule table for the next 30 days';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $client = new Client();
        $startDate = Carbon::today();

        for ($i = 0; $i < 30; $i++) {
            $currentDate = $startDate->copy()->addDays($i)->toDateString();
            $url = 'https://www.teamrankings.com/ncb/schedules/?date=' . $currentDate;

            $this->info("Scraping data for date: $currentDate");
            $response = $client->request('GET', $url);
            $htmlContent = $response->getBody()->getContents();
            $crawler = new Crawler($htmlContent);

            $crawler->filter('.tr-table.datatable.scrollable tbody tr')->each(function ($row) use ($currentDate) {
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

                    // Retrieve existing teams by name
                    $awayTeam = CollegeBasketballTeam::where('name', $awayTeamName)->first();
                    $homeTeam = CollegeBasketballTeam::where('name', $homeTeamName)->first();

                    // Skip if either `home_team_id` or `away_team_id` is null
                    if (is_null($homeTeam) || is_null($awayTeam)) {
                        $this->warn("Skipping game $matchup for date $currentDate due to missing team IDs.");
                        return;
                    }

                    // Store or update the game data
                    $game = CollegeBasketballGame::firstOrNew([
                        'home_team_id' => $homeTeam->id,
                        'away_team_id' => $awayTeam->id,
                        'game_date' => $currentDate,
                    ]);

                    // Only update fields that are currently null or empty
                    $game->hotness_score = $game->hotness_score ?? (float)$hotnessScore;
                    $game->game_time = $game->game_time ?? $gameTime;
                    $game->location = $game->location ?? $location;
                    $game->matchup = $game->matchup ?? $matchup;
                    $game->home_rank = $game->home_rank ?? $homeRank;
                    $game->away_rank = $game->away_rank ?? $awayRank;
                    $game->home_team = $game->home_team ?? $homeTeamName;
                    $game->away_team = $game->away_team ?? $awayTeamName;

                    $game->save();

                    $this->info("Stored game for date $currentDate: $matchup - Hotness Score: $hotnessScore, Time: $gameTime, Location: $location, Home Rank: $homeRank, Away Rank: $awayRank");
                } else {
                    $this->info("Could not parse teams for matchup on $currentDate: $matchup");
                }
            });

            sleep(5); // Sleep before scraping the next day
        }

        $this->info('Scraping completed for the next 30 days.');
    }
}
