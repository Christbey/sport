<?php

namespace App\Console\Commands;

use App\Models\CollegeBasketballTeam;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class FetchCollegeBasketballTeams extends Command
{
    protected $signature = 'fetch:college-basketball-teams';
    protected $description = 'Fetches all college basketball teams from the API and stores them in the database';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $url = 'https://site.api.espn.com/apis/site/v2/sports/basketball/mens-college-basketball/teams';
        $client = new Client();
        $page = 1;

        try {
            do {
                // Fetch data for the current page
                $response = $client->get($url, [
                    'query' => ['page' => $page]
                ]);
                $data = json_decode($response->getBody()->getContents(), true);

                // Check if there are any teams in the response
                if (empty($data['sports'][0]['leagues'][0]['teams'])) {
                    break; // No more teams, exit the loop
                }

                // Traverse the structure: sports -> leagues -> teams -> team
                foreach ($data['sports'][0]['leagues'][0]['teams'] as $teamData) {
                    $team = $teamData['team'];

                    CollegeBasketballTeam::updateOrCreate(
                        ['team_id' => $team['id']],
                        [
                            'uid' => $team['uid'],
                            'slug' => $team['slug'],
                            'abbreviation' => $team['abbreviation'],
                            'display_name' => $team['displayName'],
                            'name' => $team['shortDisplayName'], // saved to `name`
                            'nickname' => $team['nickname'],
                            'location' => $team['location'],
                            'color' => $team['color'] ?? null,
                            'alternate_color' => $team['alternateColor'] ?? null,
                            'is_active' => $team['isActive'],
                            'is_all_star' => $team['isAllStar'],
                            'logo_url' => $team['logos'][0]['href'] ?? null,
                        ]
                    );
                }

                $this->info("Page $page processed.");
                $page++; // Increment to the next page

            } while (true);

            $this->info('All college basketball teams have been successfully stored.');
        } catch (Exception $e) {
            $this->error('Failed to fetch data: ' . $e->getMessage());
        }
    }
}