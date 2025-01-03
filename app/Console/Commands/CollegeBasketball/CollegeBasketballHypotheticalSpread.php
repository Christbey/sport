<?php

namespace App\Console\Commands\CollegeBasketball;

use App\Models\CollegeBasketballGame;
use App\Models\CollegeBasketballHypothetical;
use App\Models\CollegeBasketballRankings;
use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;

class CollegeBasketballHypotheticalSpread extends Command
{
    protected $signature = 'college-basketball:hypothetical-spread';
    protected $description = 'Calculate and store the hypothetical spread for each game';

    public function handle()
    {
        // Retrieve games with both home_team_id and away_team_id set
        $games = CollegeBasketballGame::whereNotNull('home_team_id')
            ->whereNotNull('away_team_id')
            ->get();

        foreach ($games as $game) {
            // Ensure both teams have matching rankings
            $homeTeamRanking = CollegeBasketballRankings::where('team_id', $game->home_team_id)->first();
            $awayTeamRanking = CollegeBasketballRankings::where('team_id', $game->away_team_id)->first();

            if (!$homeTeamRanking || !$awayTeamRanking) {
                // Skip this game if either team does not have a ranking
                $this->info("Skipping game {$game->matchup} due to missing rankings.");
                continue;
            }

            // Calculate the hypothetical spread, offense difference, and defense difference
            $hypotheticalSpread = $awayTeamRanking->net_rating - $homeTeamRanking->net_rating;
            $offenseDifference = $awayTeamRanking->offensive_rating - $homeTeamRanking->offensive_rating;
            $defenseDifference = $awayTeamRanking->defensive_rating - $homeTeamRanking->defensive_rating;

            // Store or update the hypothetical data in the database
            CollegeBasketballHypothetical::updateOrCreate(
                [
                    'game_id' => $game->id,
                    'home_id' => $game->home_team_id,
                    'away_id' => $game->away_team_id,
                ],
                [
                    'game_date' => $game->game_date,
                    'home_team' => $game->home_team,
                    'away_team' => $game->away_team,
                    'hypothetical_spread' => $hypotheticalSpread,
                    'offense_difference' => $offenseDifference,
                    'defense_difference' => $defenseDifference,
                ]
            );

            $this->info("Stored hypothetical spread for game {$game->matchup}: Spread = {$hypotheticalSpread}");
        }

        // Scrape additional data from the provided CSS selector
        $htmlContent = file_get_contents('https://www.espn.com/mens-college-basketball/'); // Example URL
        $crawler = new Crawler($htmlContent);

        $crawler->filter('#fittPageContainer > div.pageContent > div.page-container.cf > div > div > div:nth-child(1) > section > div > div:nth-child(4) > div > div > div:nth-child(2) > div > div > div.Table__Scroller > table > tbody > tr:nth-child(1) > td.teams__col.Table__TD > a')->each(function ($node) {
            $linkText = $node->text();
            $href = $node->attr('href');

            $this->info("Scraped data: {$linkText} ({$href})");
        });

        $this->info('College basketball hypothetical spread calculation and data scraping completed.');
    }
}
