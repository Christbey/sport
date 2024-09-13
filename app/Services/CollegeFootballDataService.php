<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Venue;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class CollegeFootballDataService
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.collegefootballdata.com/',
        ]);

        $this->apiKey = config('services.college_football_data.key');
    }

    public function fetchAndStoreVenues(): array
    {
        try {
            $response = $this->client->request('GET', 'venues', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $venues = json_decode($response->getBody(), true);

            foreach ($venues as $venueData) {
                Venue::updateOrCreate(
                    ['venue_id' => $venueData['id']],
                    [
                        'name' => $venueData['name'],
                        'city' => $venueData['city'],
                        'state' => $venueData['state'],
                        'country_code' => $venueData['country'],
                        'timezone' => $venueData['timezone'],
                        'latitude' => $venueData['latitude'] ?? null,
                        'longitude' => $venueData['longitude'] ?? null,
                        'capacity' => $venueData['capacity'] ?? null,
                        'year_constructed' => $venueData['year_constructed'] ?? null,
                    ]
                );
            }

            return $venues;
        } catch (Exception $e) {
            Log::error('Error fetching venues: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    public function fetchAndStoreTeams(): array
    {
        try {
            $response = $this->client->request('GET', 'teams', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $teams = json_decode($response->getBody(), true);

            foreach ($teams as $teamData) {
                // Get or create the venue
                $venue = Venue::where('venue_id', $teamData['venue_id'])->first();

                Team::updateOrCreate(
                    ['team_id' => $teamData['id']],
                    [
                        'school' => $teamData['school'],
                        'mascot' => $teamData['mascot'],
                        'abbreviation' => $teamData['abbreviation'],
                        'alt_name1' => $teamData['alt_name1'],
                        'alt_name2' => $teamData['alt_name2'],
                        'alt_name3' => $teamData['alt_name3'],
                        'color' => $teamData['color'],
                        'alt_color' => $teamData['alt_color'],
                        'logos' => json_encode($teamData['logos']),
                        'conference' => $teamData['conference'],
                        'venue_id' => $venue ? $venue->id : null,
                    ]
                );
            }

            return $teams;
        } catch (Exception $e) {
            Log::error('Error fetching teams: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}
