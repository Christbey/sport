<?php

namespace App\Console\Commands;

use App\Models\CollegeBasketballRankings;
use App\Models\CollegeBasketballTeam;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeKenPom extends Command
{
    protected $signature = 'scrape:kenpom';
    protected $description = 'Scrape the KenPom rankings page';

    public function handle()
    {
        $client = new Client();
        $url = 'https://kenpom.com/';

        try {
            // Fetch HTML content
            $response = $client->request('GET', $url, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                ],
            ]);
            $html = (string)$response->getBody();
            $crawler = new Crawler($html);

            // Scrape each row in the rankings table body
            $crawler->filter('#ratings-table tr')->each(function (Crawler $node, $index) {
                // Check and skip if the row is a header row or within <thead>
                $rowClass = $node->attr('class');
                if ($rowClass && preg_match('/thead|bold-bottom/', $rowClass)) {
                    $this->info('Skipping header row at index: ' . ($index + 1));
                    return;
                }

                // Skip unexpected <thead> tags
                if ($node->nodeName() === 'thead') {
                    $this->info('Skipping unexpected <thead> section at index: ' . ($index + 1));
                    return;
                }

                try {
                    // Extract data for valid rows
                    $rank = $node->filter('.hard_left')->count() ? (int)trim($node->filter('.hard_left')->text()) : null;
                    $team = $node->filter('.next_left')->count() ? trim($node->filter('.next_left')->text()) : null;
                    $conference = $node->filter('.conf')->count() ? trim($node->filter('.conf')->text()) : null;
                    $record = $node->filter('.wl')->count() ? trim($node->filter('.wl')->text()) : null;
                    $netRating = $node->filter('td')->eq(4)->count() ? (float)str_replace('+', '', $node->filter('td')->eq(4)->text()) : null;
                    $offenseRating = $node->filter('.td-left.divide')->eq(1)->count() ? (float)$node->filter('.td-left.divide')->eq(1)->text() : null;
                    $defenseRating = $node->filter('.td-left')->eq(3)->count() ? (float)$node->filter('.td-left')->eq(3)->text() : null;
                    $tempo = $node->filter('.td-left.divide')->eq(5)->count() ? $node->filter('.td-left.divide')->eq(5)->text() : null;

                    // Attempt to find a matching team in the college_basketball_teams table or its aliases
                    $teamRecord = CollegeBasketballTeam::where('name', 'LIKE', "%$team%")
                        ->orWhereHas('aliases', function ($query) use ($team) {
                            $query->where('alias', 'LIKE', "%$team%");
                        })
                        ->first();

                    // Determine match status for display
                    if ($teamRecord) {
                        $teamStatus = "\033[32mFound: {$teamRecord->name} (ID: {$teamRecord->id})\033[0m";
                        $teamId = $teamRecord->id;
                    } else {
                        $teamStatus = "\033[31mNot Found: $team\033[0m";
                        $teamId = null;
                    }

                    $this->info("Rank: $rank | Team: $team | Status: $teamStatus");

                    // Save data to the database with team_id if matched
                    CollegeBasketballRankings::updateOrCreate(
                        ['team' => $team, 'conference' => $conference],
                        [
                            'rank' => $rank,
                            'conference' => $conference,
                            'record' => $record,
                            'net_rating' => $netRating,
                            'offensive_rating' => $offenseRating,
                            'defensive_rating' => $defenseRating,
                            'tempo' => $tempo,
                            'team_id' => $teamId,
                        ]
                    );
                } catch (Exception $e) {
                    $this->error('Error processing row: ' . $e->getMessage());
                }
            });

            $this->info('Scraping completed successfully.');
        } catch (Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}