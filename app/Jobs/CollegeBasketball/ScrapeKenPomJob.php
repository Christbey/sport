<?php

namespace App\Jobs\CollegeBasketball;

use App\Models\CollegeBasketballRankings;
use App\Models\CollegeBasketballTeam;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeKenPomJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $client = new Client();
        $url = 'https://kenpom.com/';

        try {
            $response = $client->request('GET', $url, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                ],
            ]);
            $html = (string)$response->getBody();
            $crawler = new Crawler($html);

            $crawler->filter('#ratings-table tr')->each(function (Crawler $node, $index) {
                $rowClass = $node->attr('class');
                if ($rowClass && preg_match('/thead|bold-bottom/', $rowClass)) {
                    Log::info('Skipping header row at index: ' . ($index + 1));
                    return;
                }

                if ($node->nodeName() === 'thead') {
                    Log::info('Skipping unexpected <thead> section at index: ' . ($index + 1));
                    return;
                }

                try {
                    $rank = $node->filter('.hard_left')->count() ? (int)trim($node->filter('.hard_left')->text()) : null;
                    $team = $node->filter('.next_left')->count() ? trim($node->filter('.next_left')->text()) : null;
                    $conference = $node->filter('.conf')->count() ? trim($node->filter('.conf')->text()) : null;
                    $record = $node->filter('.wl')->count() ? trim($node->filter('.wl')->text()) : null;
                    $netRating = $node->filter('td')->eq(4)->count() ? (float)str_replace('+', '', $node->filter('td')->eq(4)->text()) : null;
                    $offenseRating = $node->filter('.td-left.divide')->eq(1)->count() ? (float)$node->filter('.td-left.divide')->eq(1)->text() : null;
                    $defenseRating = $node->filter('.td-left')->eq(3)->count() ? (float)$node->filter('.td-left')->eq(3)->text() : null;
                    $tempo = $node->filter('.td-left.divide')->eq(5)->count() ? $node->filter('.td-left.divide')->eq(5)->text() : null;

                    $standardizedTeamName = strtolower(str_replace(['St', 'St.'], 'State', $team));

                    $teamRecord = CollegeBasketballTeam::whereRaw('LOWER(REPLACE(name, "St", "State")) = ?', [$standardizedTeamName])
                        ->orWhereHas('aliases', function ($query) use ($standardizedTeamName) {
                            $query->whereRaw('LOWER(REPLACE(alias, "St", "State")) = ?', [$standardizedTeamName]);
                        })
                        ->first();

                    if (!$teamRecord) {
                        $teamRecord = CollegeBasketballTeam::whereRaw('SOUNDEX(name) = SOUNDEX(?)', [$team])
                            ->orWhereHas('aliases', function ($query) use ($team) {
                                $query->whereRaw('SOUNDEX(alias) = SOUNDEX(?)', [$team]);
                            })
                            ->first();
                    }

                    $teamId = $teamRecord ? $teamRecord->id : null;
                    Log::info("Rank: $rank | Team: $team | Status: " . ($teamRecord ? 'Found' : 'Not Found'));

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
                    Log::error('Error processing row: ' . $e->getMessage());
                }
            });

            Log::info('Scraping completed successfully.');
        } catch (Exception $e) {
            Log::error('Error: ' . $e->getMessage());
        }
    }
}
