<?php

namespace App\Http\Controllers\Api;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class EspnQbrController extends Controller
{
    protected $client;

    // Constructor to initialize Guzzle client
    public function __construct()
    {
        $this->client = new Client();
    }

    public function fetchQbrData($week)
    {
        // Define the ESPN API endpoint with dynamic week parameter
        $endpoint = "https://sports.core.api.espn.com/v2/sports/football/leagues/nfl/seasons/2024/types/2/weeks/{$week}/qbr/10000";

        try {
            // Fetch the main data from the ESPN API using Guzzle
            $response = $this->client->get($endpoint);
            $data = json_decode($response->getBody()->getContents(), true);
            $items = $data['items'] ?? [];

            // Process all QBR data items
            $processedItems = array_map(function ($item) use ($week) {
                return $this->processItem($item, $week);
            }, $items);

            // Pass the processed data to a Blade view
            return view('espn.qbr', compact('processedItems', 'week'));

        } catch (RequestException $e) {
            return response()->json(['error' => 'Failed to fetch data from ESPN API.'], $e->getCode());
        } catch (Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    // Helper method to process each QBR item
    private function processItem($item, $week)
    {
        // Fetch athlete, team, and event data
        $athleteData = $this->fetchDataFromUrl($item['athlete']['$ref'] ?? null);
        $teamData = $this->fetchDataFromUrl($item['team']['$ref'] ?? null);
        $eventData = $this->fetchDataFromUrl($item['event']['$ref'] ?? null);

        $athleteName = $athleteData['fullName'] ?? null;
        $athleteId = $athleteData['id'] ?? null;
        $teamName = $teamData['name'] ?? null;

        // Parse opponent's name from the event name
        $opponentName = $this->parseOpponentName($eventData['name'] ?? '', $teamName);

        // Fetch the week number or use the passed week as a fallback
        $weekNumber = $this->fetchWeekNumber($eventData, $week);

        // Process and filter only the desired stats
        $stats = $item['splits']['categories'][0]['stats'] ?? [];
        $processedStats = $this->processStats($stats);

        // Prepare the result for the athlete
        return [
            'athlete' => [
                'id' => $athleteId,
                'name' => $athleteName,
            ],
            'team' => [
                'name' => $teamName,
            ],
            'opponent' => [
                'name' => $opponentName,
            ],
            'season' => 2024, // Static as it's a 2024 endpoint
            'week' => $weekNumber,
            'stats' => $processedStats,
        ];
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

    // Helper method to parse opponent's name from event name
    private function parseOpponentName($eventName, $teamName)
    {
        $teams = explode(' at ', $eventName);

        if (count($teams) === 2) {
            return (strpos($teams[0], $teamName) !== false) ? $teams[1] : $teams[0];
        }

        return null; // Fallback if event name format is unexpected
    }

    // Helper method to fetch week number from event or fallback to passed week
    private function fetchWeekNumber($eventData, $week)
    {
        $weekData = $this->fetchDataFromUrl($eventData['week']['$ref'] ?? null);
        return $weekData['number'] ?? $week;
    }

    // Helper method to process only the desired stats
    private function processStats($stats)
    {
        $desiredStats = ['qbpaa', 'qbr', 'unqualifiedRank'];

        // Use array_filter to optimize stat processing
        return array_filter($stats, function ($stat) use ($desiredStats) {
            return in_array($stat['name'], $desiredStats);
        });
    }
}
