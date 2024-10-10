<?php

namespace App\Http\Controllers\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;

class EspnAthleteController extends Controller
{
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function fetchAthletes()
    {
        $cacheKey = 'nfl_active_athletes';
        $cacheDuration = 60 * 60; // Cache for 1 hour

        // Cache the response for 1 hour to reduce unnecessary API calls
        $data = Cache::remember($cacheKey, $cacheDuration, function () {
            $endpoint = 'https://sports.core.api.espn.com/v3/sports/football/nfl/athletes?limit=20000&active=true';

            try {
                // Fetch data from ESPN API
                $response = $this->client->get($endpoint);
                return json_decode($response->getBody()->getContents(), true);

            } catch (RequestException $e) {
                // Return an error message if the API call fails
                return response()->json(['error' => 'Failed to fetch athletes from ESPN API.'], $e->getCode());
            }
        });

        // Return the formatted JSON response
        return response()->json([
            'count' => $data['count'] ?? 0,
            'pageIndex' => $data['pageIndex'] ?? 1,
            'pageSize' => $data['pageSize'] ?? 20000,
            'pageCount' => $data['pageCount'] ?? 1,
            'items' => array_map(function ($item) {
                return [
                    'id' => $item['id'] ?? '',
                    'uid' => $item['uid'] ?? '',
                    'guid' => $item['guid'] ?? '',
                    'firstName' => $item['firstName'] ?? '',
                    'lastName' => $item['lastName'] ?? '',
                    'fullName' => $item['fullName'] ?? '',
                    'displayName' => $item['displayName'] ?? '',
                    'shortName' => $item['shortName'] ?? '',
                    'weight' => $item['weight'] ?? null,
                    'displayWeight' => $item['displayWeight'] ?? '',
                    'height' => $item['height'] ?? null,
                    'displayHeight' => $item['displayHeight'] ?? '',
                    'age' => $item['age'] ?? null,
                    'dateOfBirth' => $item['dateOfBirth'] ?? '',
                    'birthPlace' => [
                        'city' => $item['birthPlace']['city'] ?? '',
                        'state' => $item['birthPlace']['state'] ?? '',
                        'country' => $item['birthPlace']['country'] ?? ''
                    ],
                    'experience' => [
                        'years' => $item['experience']['years'] ?? 0
                    ],
                    'jersey' => $item['jersey'] ?? '',
                    'active' => $item['active'] ?? false
                ];
            }, $data['items'] ?? [])
        ]);
    }
}
