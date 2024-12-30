<?php

namespace App\Console\Commands;

use App\Models\NbaPlayer;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

// <--- Import your player model

class ESPNPlayersImport extends Command
{
    protected $signature = 'espn:players-import';
    protected $description = 'Imports NBA athletes data from ESPN API for the 2024 season.';

    public function handle()
    {
        $client = new Client();
        $endpoint = 'http://sports.core.api.espn.com/v2/sports/basketball/leagues/nba/seasons/2024/athletes';
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

                $athleteUrl = $item['$ref'];
                $athleteResponse = $client->get($athleteUrl);
                $athleteData = json_decode($athleteResponse->getBody(), true);

                // Attempt to parse the team ESPN ID from the athleteData['team']['$ref'] if available
                $teamEspnId = null;
                if (!empty($athleteData['team']['$ref'])) {
                    $teamRef = $athleteData['team']['$ref'];
                    // Typically looks like: .../teams/14?lang=en&region=us
                    if (preg_match('/teams\/(\d+)\?/', $teamRef, $matches)) {
                        $teamEspnId = $matches[1]; // e.g. "14"
                    }
                }

                // Attempt to get birth info (city/state/country)
                $birthCity = $athleteData['birthPlace']['city'] ?? null;
                $birthState = $athleteData['birthPlace']['state'] ?? null;
                $birthCountry = $athleteData['birthPlace']['country'] ?? null;

                // Contract details
                $salary = $athleteData['contract']['salary'] ?? null;
                $salaryRemaining = $athleteData['contract']['salaryRemaining'] ?? null;
                $yearsRemaining = $athleteData['contract']['yearsRemaining'] ?? null;
                $contractActive = (!empty($athleteData['contract']['active']));

                // Draft info
                $draftYear = $athleteData['draft']['year'] ?? null;
                $draftRound = $athleteData['draft']['round'] ?? null;
                $draftSelection = $athleteData['draft']['selection'] ?? null;

                // Save/Update in DB
                NbaPlayer::updateOrCreate(
                    ['espn_id' => $athleteData['id']],  // Where "espn_id" matches ESPN's player ID
                    [
                        'team_espn_id' => $teamEspnId,
                        'first_name' => $athleteData['firstName'] ?? null,
                        'last_name' => $athleteData['lastName'] ?? null,
                        'full_name' => $athleteData['fullName'] ?? null,
                        'display_name' => $athleteData['displayName'] ?? null,
                        'slug' => $athleteData['slug'] ?? null,
                        'position' => $athleteData['position']['displayName'] ?? null,
                        'jersey' => $athleteData['jersey'] ?? null,
                        'height' => $athleteData['displayHeight'] ?? null,
                        'weight' => $athleteData['displayWeight'] ?? null,
                        'birth_city' => $birthCity,
                        'birth_state' => $birthState,
                        'birth_country' => $birthCountry,
                        'salary' => $salary,
                        'salary_remaining' => $salaryRemaining,
                        'years_remaining' => $yearsRemaining,
                        'contract_active' => $contractActive,
                        'draft_year' => $draftYear,
                        'draft_round' => $draftRound,
                        'draft_selection' => $draftSelection,
                        'is_active' => (isset($athleteData['active']) && $athleteData['active']),
                    ]
                );

                $this->info('Imported Athlete: ' . ($athleteData['displayName'] ?? 'Unknown'));
            }

            $pageCount = $data['pageCount'] ?? 1;
            $pageIndex = $data['pageIndex'] ?? 1;
            $currentPage++;
        } while ($currentPage <= $pageCount);

        $this->info('All athlete pages fetched!');
        return Command::SUCCESS;
    }
}
