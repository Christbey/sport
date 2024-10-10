<?php

namespace App\Http\Controllers\Api\Espn;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise;
use Illuminate\Support\Facades\Cache;

class EspnQbrController extends Controller
{
    protected $client;
    private $cache = []; // Cache for in-memory data

    // Inject Guzzle client
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function fetchQbrData($week)
    {
        $cacheKey = "qbr_data_week_{$week}";

        // Cache the data for 30 minutes to reduce API load
        $processedItems = Cache::remember($cacheKey, 60 * 30, function () use ($week) {
            $endpoint = "https://sports.core.api.espn.com/v2/sports/football/leagues/nfl/seasons/2024/types/2/weeks/{$week}/qbr/10000";

            $data = $this->fetchDataFromUrl($endpoint);
            $items = $data['items'] ?? [];

            // Process QBR data items with parallel requests for athlete, team, and event data
            return array_map(fn($item) => $this->processItem($item, $week), $items);
        });

        // Return the processed data to the view
        return view('espn.qbr', compact('processedItems', 'week'));
    }

    // Fetch data from a given URL
    private function fetchDataFromUrl($url)
    {
        if (!$url) return [];

        // Check if the response is already cached
        if (isset($this->cache[$url])) {
            return $this->cache[$url];
        }

        try {
            $response = $this->client->get($url);
            $data = json_decode($response->getBody()->getContents(), true);
            $this->cache[$url] = $data; // Cache the response for future use
            return $data;
        } catch (RequestException $e) {
            return []; // Return an empty array if the request fails
        }
    }

    // Process each QBR item, fetching athlete, team, and event data in parallel
    private function processItem($item, $week)
    {
        $promises = [
            'athlete' => $this->client->getAsync($item['athlete']['$ref'] ?? ''),
            'team' => $this->client->getAsync($item['team']['$ref'] ?? ''),
            'event' => $this->client->getAsync($item['event']['$ref'] ?? ''),
        ];

        // Wait for all the requests to complete in parallel
        $responses = Promise\Utils::unwrap($promises);

        $athleteData = json_decode($responses['athlete']->getBody()->getContents(), true);
        $teamData = json_decode($responses['team']->getBody()->getContents(), true);
        $eventData = json_decode($responses['event']->getBody()->getContents(), true);

        $athleteName = $athleteData['fullName'] ?? null;
        $teamName = $teamData['name'] ?? null;

        // Parse the opponent's name from the event name
        $opponentName = $this->parseOpponentName($eventData['name'] ?? '', $teamName);

        // Get the week number from event data, or use the fallback
        $weekNumber = $this->fetchWeekNumber($eventData, $week);

        // Process and filter desired stats
        $processedStats = $this->processStats($item['splits']['categories'][0]['stats'] ?? []);

        return [
            'athlete' => ['id' => $athleteData['id'] ?? null, 'name' => $athleteName],
            'team' => ['name' => $teamName],
            'opponent' => ['name' => $opponentName],
            'season' => 2024,
            'week' => $weekNumber,
            'stats' => $processedStats,
        ];
    }

    // Parse the opponent's name from the event name
    private function parseOpponentName($eventName, $teamName)
    {
        $teams = explode(' at ', $eventName);
        return (count($teams) === 2) ? (str_contains($teams[0], $teamName) ? $teams[1] : $teams[0]) : null;
    }

    // Fetch the week number from the event or use the fallback
    private function fetchWeekNumber($eventData, $week)
    {
        $weekData = $this->fetchDataFromUrl($eventData['week']['$ref'] ?? null);
        return $weekData['number'] ?? $week;
    }

    // Process only the desired stats from the response
    private function processStats($stats)
    {
        $desiredStats = ['qbpaa', 'qbr', 'unqualifiedRank'];
        return array_filter($stats, fn($stat) => in_array($stat['name'], $desiredStats));
    }
}
