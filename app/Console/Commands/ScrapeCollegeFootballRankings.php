<?php

namespace App\Console\Commands;

use App\Models\CollegeFootball\CollegeFootballTeam;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Models\Sagarin;

class ScrapeCollegeFootballRankings extends Command
{
    protected $signature = 'scrape:college-football-rankings';
    protected $description = 'Scrapes college football rankings and saves them in the Sagarin table';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $client = new Client();
        $response = $client->get('http://sagarin.com/sports/cfsend.htm');

        if ($response->getStatusCode() === 200) {
            $body = $response->getBody()->getContents();

            // Adjust the regex pattern to match the provided data more accurately
            preg_match_all('/\d+\s+(.+?)\s+A\s+=\s+([\d.]+)/', $body, $matches);

            if (!empty($matches[1])) {
                foreach ($matches[1] as $index => $scrapedTeam) {
                    $rating = $matches[2][$index];

                    // Trim and clean the scraped team name
                    $scrapedTeam = trim($scrapedTeam);

                    // Try to find the team by exact match first
                    $team = CollegeFootballTeam::where('school', $scrapedTeam)
                        ->orWhere('alt_name_1', $scrapedTeam)
                        ->orWhere('alt_name_2', $scrapedTeam)
                        ->orWhere('alt_name_3', $scrapedTeam)
                        ->first();

                    // Handle cases like "Miami-Florida" or "Southern California"
                    if (!$team) {
                        $team = CollegeFootballTeam::where(function ($query) use ($scrapedTeam) {
                            $query->where('school', 'LIKE', "%{$scrapedTeam}%")
                                ->orWhere('alt_name_1', 'LIKE', "%{$scrapedTeam}%")
                                ->orWhere('alt_name_2', 'LIKE', "%{$scrapedTeam}%")
                                ->orWhere('alt_name_3', 'LIKE', "%{$scrapedTeam}%");
                        })->first();
                    }

                    // Log the result and save to the Sagarin table
                    if ($team) {
                        $this->info("Scraped Team: {$scrapedTeam} | Matched to: {$team->school} | Rating: {$rating}");

                        Sagarin::updateOrCreate(
                            ['id' => $team->id], // Using the team's id as the id in Sagarin
                            [
                                'team_name' => $scrapedTeam,
                                'rating' => $rating,
                            ]
                        );
                    } else {
                        $this->warn("Scraped Team: {$scrapedTeam} | No match found | Rating: {$rating}");
                    }
                }
            } else {
                $this->error('No rankings found on the page.');
            }
        } else {
            $this->error('Failed to fetch the page. Status code: ' . $response->getStatusCode());
        }
    }
}
