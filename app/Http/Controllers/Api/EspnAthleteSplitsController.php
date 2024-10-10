<?php

namespace App\Http\Controllers\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;

class EspnAthleteSplitsController extends Controller
{
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function fetchAthleteSplits($athleteId)
    {
        $cacheKey = "nfl_athlete_splits_{$athleteId}";
        $cacheDuration = 60 * 60; // Cache for 1 hour

        // Cache the response to reduce unnecessary API calls
        $data = Cache::remember($cacheKey, $cacheDuration, function () use ($athleteId) {
            $endpoint = "https://site.web.api.espn.com/apis/common/v3/sports/football/nfl/athletes/{$athleteId}/splits";

            try {
                // Fetch data from ESPN API
                $response = $this->client->get($endpoint);
                return json_decode($response->getBody()->getContents(), true);

            } catch (RequestException $e) {
                // Return an error message if the API call fails
                return response()->json(['error' => 'Failed to fetch athlete splits from ESPN API.'], $e->getCode());
            }
        });

        // Return the formatted JSON response
        return response()->json([
            'filters' => $data['filters'] ?? [],
            'displayName' => $data['displayName'] ?? '',
            'categories' => $data['categories'] ?? [],
            'labels' => $data['labels'] ?? [],
            'names' => $data['names'] ?? [],
            'displayNames' => $data['displayNames'] ?? [],
            'descriptions' => $data['descriptions'] ?? [],
            'splitCategories' => $data['splitCategories'] ?? []
        ]);
    }
}
