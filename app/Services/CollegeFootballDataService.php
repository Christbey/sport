<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class CollegeFootballDataService
{
    protected $client;
    protected $apiUrl;
    protected $apiKey;

    /**
     * Initialize the CollegeFootballDataService with configuration settings.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->apiUrl = config('services.college_football_data.fpi_url'); // Updated to match your config structure
        $this->apiKey = config('services.college_football_data.key');
    }

    /**
     * Fetch FPI data from the API.
     *
     * @param int $year
     * @return array|null
     */
    public function fetchFpiData(int $year): ?array
    {
        try {
            $response = $this->client->request('GET', $this->apiUrl, [
                'query' => ['year' => $year],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            Log::error("Failed to fetch FPI data for year {$year}: " . $e->getMessage());
            return null;
        }
    }
}
