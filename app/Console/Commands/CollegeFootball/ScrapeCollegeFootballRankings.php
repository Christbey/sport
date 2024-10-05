<?php

namespace App\Console\Commands\CollegeFootball;

use App\Models\CollegeFootball\CollegeFootballTeam;
use App\Models\CollegeFootball\CollegeFootballTeamAlias;
use App\Models\CollegeFootball\Sagarin;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class ScrapeCollegeFootballRankings extends Command
{
    protected $signature = 'scrape:college-football-rankings';
    protected $description = 'Scrapes college football rankings and saves them in the Sagarin table';

    private $client;

    public function __construct(Client $client)
    {
        parent::__construct();
        $this->client = $client;
    }

    public function handle()
    {
        $response = $this->fetchRankings();

        if ($response->getStatusCode() === 200) {
            $this->processRankings($response->getBody()->getContents());
        } else {
            $this->error('Failed to fetch the page. Status code: ' . $response->getStatusCode());
        }
    }

    /**
     * Fetch the rankings from the Sagarin website.
     */
    private function fetchRankings()
    {
        return $this->client->get('http://sagarin.com/sports/cfsend.htm');
    }

    /**
     * Process the rankings from the HTML body content.
     */
    private function processRankings($body)
    {
        $teams = $this->extractTeamsFromBody($body);

        if (!empty($teams)) {
            foreach ($teams as $teamData) {
                $this->saveTeamRanking($teamData['name'], $teamData['rating']);
            }
        } else {
            $this->error('No rankings found on the page.');
        }
    }

    /**
     * Extract team names and ratings from the HTML body content.
     */
    private function extractTeamsFromBody($body)
    {
        preg_match_all('/\d+\s+(.+?)\s+A\s+=\s+([\d.]+)/', $body, $matches);
        $teams = [];

        if (!empty($matches[1])) {
            foreach ($matches[1] as $index => $teamName) {
                $teams[] = [
                    'name' => trim($teamName),
                    'rating' => $matches[2][$index],
                ];
            }
        }

        return $teams;
    }

    /**
     * Find and save the team ranking in the Sagarin table.
     */
    private function saveTeamRanking($scrapedTeam, $rating)
    {
        $team = $this->findTeamByAlias($scrapedTeam);

        if ($team) {
            $this->info("Scraped Team: {$scrapedTeam} | Matched to: {$team->school} | Rating: {$rating}");

            Sagarin::updateOrCreate(
                ['id' => $team->id],  // Use team ID for matching
                [
                    'team_name' => $scrapedTeam,
                    'rating' => $rating,
                ]
            );
        } else {
            $this->warn("Scraped Team: {$scrapedTeam} | No match found | Rating: {$rating}");
        }
    }

    /**
     * Find a team by its name or alias from the alias table.
     */
    private function findTeamByAlias($scrapedTeam)
    {
        // First try to find the team by the exact school name
        $team = CollegeFootballTeam::where('school', $scrapedTeam)->first();

        // If no exact match is found, search in the aliases table
        if (!$team) {
            $alias = CollegeFootballTeamAlias::where('alias_name', $scrapedTeam)->first();
            if ($alias) {
                $team = $alias->team;
            }
        }

        return $team;
    }
}


# @TODO: 'combine records'