<?php

namespace App\Console\Commands\CollegeFootball;

use App\Models\CollegeFootball\CollegeFootballTeam;
use App\Models\CollegeFootball\SpRating;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchSpRatings extends Command
{
    protected $signature = 'fetch:sp-ratings';
    protected $description = 'Fetch SP+ ratings for the year 2024';
    protected Client $client;
    protected mixed $apiKey;

    public function __construct()
    {
        parent::__construct();

        // Initialize the Guzzle client with the base URI
        $this->client = new Client([
            'base_uri' => 'https://api.collegefootballdata.com/',
        ]);

        // Load the API key from the config file
        $this->apiKey = config('services.college_football_data.key');
    }

    public function handle()
    {
        $url = 'ratings/sp?year=2024';

        try {
            // Send a GET request to the API
            $response = $this->client->request('GET', $url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey, // Pass the API key in the Authorization header
                ]
            ]);

            // Check if the response status is 200 OK
            if ($response->getStatusCode() === 200) {
                // Decode the response JSON into an associative array
                $data = json_decode($response->getBody(), true);

                // Process the data
                foreach ($data as $rating) {
                    $this->logSpRating($rating);
                }

                // Success message
                $this->info('SP+ ratings fetched successfully.');
            } else {
                // Log and display error message if the status code is not 200
                $this->error('Failed to fetch SP+ ratings. Status code: ' . $response->getStatusCode());
            }

        } catch (Exception $e) {
            // Log error if an exception occurs
            Log::error('Failed to fetch SP+ ratings. Error: ' . $e->getMessage());
            $this->error('Failed to fetch SP+ ratings. Please check the logs.');
        }
    }

    // Log or process each team's SP+ rating
    private function logSpRating($rating)
    {
        // Try to find the team by name
        $team = CollegeFootballTeam::where('school', $rating['team'])->first();

        // Set team_id to the found team's ID, or null if not found
        $teamId = $team?->id;

        // Store or update the SP+ rating
        SpRating::updateOrCreate(
            ['team' => $rating['team']], // Ensure uniqueness by team name
            [
                'team_id' => $teamId,
                'conference' => $rating['conference'] ?? 'National Average',
                'overall_rating' => $rating['rating'],
                'ranking' => $rating['ranking'] ?? null,
                'offense_ranking' => $rating['offense']['ranking'] ?? null,
                'offense_rating' => $rating['offense']['rating'] ?? null,
                'defense_ranking' => $rating['defense']['ranking'] ?? null,
                'defense_rating' => $rating['defense']['rating'] ?? null,
                'special_teams_rating' => $rating['specialTeams']['rating'] ?? null,
            ]
        );
    }
}
