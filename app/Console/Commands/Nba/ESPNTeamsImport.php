<?php

namespace App\Console\Commands\Nba;

use App\Models\NbaTeam;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

// <-- Import your model

class ESPNTeamsImport extends Command
{
    protected $signature = 'espn:teams-import';
    protected $description = 'Imports NBA teams data from ESPN API for the 2024 season.';

    public function handle()
    {
        $client = new Client();
        $endpoint = 'http://sports.core.api.espn.com/v2/sports/basketball/leagues/nba/seasons/2024/teams';

        $currentPage = 1;
        do {
            $paginatedUrl = $endpoint . '?page=' . $currentPage;
            $response = $client->get($paginatedUrl);
            $data = json_decode($response->getBody(), true);

            $items = $data['items'] ?? [];

            foreach ($items as $item) {
                if (!isset($item['$ref'])) {
                    continue;
                }

                $teamUrl = $item['$ref'];
                $teamResponse = $client->get($teamUrl);
                $teamData = json_decode($teamResponse->getBody(), true);

                $this->info('Storing Team: ' . ($teamData['displayName'] ?? 'Unknown Team'));

                // Save in the database via your NbaTeam model
                NbaTeam::updateOrCreate(
                // "Unique" field in your table:
                    ['espn_id' => $teamData['id']],  // Where espn_id matches ESPN's "id"
                    [
                        'guid' => $teamData['guid'] ?? null,
                        'uid' => $teamData['uid'] ?? null,
                        'slug' => $teamData['slug'] ?? null,
                        'location' => $teamData['location'] ?? null,
                        'name' => $teamData['name'] ?? null,
                        'abbreviation' => $teamData['abbreviation'] ?? null,
                        'display_name' => $teamData['displayName'] ?? null,
                        'short_display_name' => $teamData['shortDisplayName'] ?? null,
                        'color' => $teamData['color'] ?? null,
                        'alternate_color' => $teamData['alternateColor'] ?? null,
                        'is_active' => (isset($teamData['isActive']) && $teamData['isActive']),
                    ]
                );

                $this->line('--------------------');
            }

            $pageCount = $data['pageCount'] ?? 1;
            $pageIndex = $data['pageIndex'] ?? 1;

            $currentPage++;
        } while ($currentPage <= $pageCount);

        $this->info('All pages fetched!');
        return Command::SUCCESS;
    }
}
