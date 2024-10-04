<?php

namespace App\Http\Controllers\Api;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Http\Controllers\Controller;

class EspnTeamProjectionController extends Controller
{
    protected $client;

    // Constructor to initialize Guzzle client
    public function __construct()
    {
        $this->client = new Client();
    }

    public function fetchTeamProjection($teamId)
    {
        // Define the ESPN API endpoint with dynamic team ID parameter
        $endpoint = "https://sports.core.api.espn.com/v2/sports/football/leagues/nfl/seasons/2024/teams/{$teamId}/projection";

        try {
            // Fetch the team projection data from the ESPN API
            $response = $this->client->get($endpoint);
            $data = json_decode($response->getBody()->getContents(), true);

            // Extract the relevant data
            $teamData = $this->fetchDataFromUrl($data['team']['$ref'] ?? null);
            $teamName = $teamData['name'] ?? 'Unknown Team';

            $teamProjection = [
                'team' => $teamName,
                'chanceToWinThisWeek' => $data['chanceToWinThisWeek'] ?? null,
                'chanceToWinDivision' => $data['chanceToWinDivision'] ?? null,
                'projectedWins' => $data['projectedWins'] ?? null,
                'projectedLosses' => $data['projectedLosses'] ?? null,
            ];

            return response()->json($teamProjection, 200);

        } catch (RequestException $e) {
            return response()->json(['error' => 'Failed to fetch team projection from ESPN API.'], $e->getCode());
        } catch (Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    // Helper method to fetch data from referenced URLs using Guzzle
    private function fetchDataFromUrl($url)
    {
        if (!$url) return [];

        try {
            $response = $this->client->get($url);
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            return []; // Return an empty array if the request fails
        }
    }
}
