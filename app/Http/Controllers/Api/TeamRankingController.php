<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class TeamRankingController extends Controller
{
    protected $client;

    public function __construct()
    {
        $this->client = $this->initializeClient();
    }

    // Initialize Guzzle Client
    protected function initializeClient()
    {
        return new Client([
            'base_uri' => 'https://www.teamrankings.com',

        ]);
    }

    // General method to fetch data from a URL and return a Crawler object
    public function getStat($category, $stat)
    {
        $url = "/nfl/stat/{$stat}";

        try {
            $crawler = $this->fetchHtml($url);

            $rows = $this->parseTableRows($crawler, [
                0 => 'rank',
                1 => 'team',
                2 => '2024',
                3 => 'last_3',
                4 => 'last_1',
                5 => 'home',
                6 => 'away',
                7 => '2023'
            ]);

            return response()->json(['data' => $rows, 'stat' => $stat]);

        } catch (Exception $e) {
            return $this->handleError($e);
        }
    }

    // General method to parse table rows
    protected function fetchHtml($url)
    {
        try {
            $response = $this->client->request('GET', $url);
            return new Crawler($response->getBody()->getContents());
        } catch (Exception $e) {
            Log::error("Error fetching URL: $url - " . $e->getMessage());
            throw new Exception('Unable to fetch data');
        }
    }

    // Fetch and return stat data
    protected function parseTableRows(Crawler $crawler, array $mapping)
    {
        return $crawler->filter('table.tr-table.datatable.scrollable tbody tr')->each(function (Crawler $row) use ($mapping) {
            $data = [];
            foreach ($mapping as $index => $field) {
                $data[$field] = $row->filter('td')->eq($index)->text();
            }

            // Check for team link if it's part of the mapping
            if (isset($mapping['team_link'])) {
                $data['team_link'] = $row->filter('td')->eq(1)->filter('a')->attr('href');
            }

            return $data;
        });
    }

    // Fetch and return ranking data
    protected function handleError(Exception $e)
    {
        Log::error('Error fetching data: ' . $e->getMessage());
        return response()->json(['error' => 'Unable to fetch data', 'message' => $e->getMessage()], 500);
    }

    // Common error handler
    public function getRanking($rankingType)
    {
        $url = "/nfl/ranking/{$rankingType}";

        try {
            $crawler = $this->fetchHtml($url);

            if (!$crawler->filter('table.tr-table.datatable.scrollable')->count()) {
                Log::warning('Table not found for the provided ranking type: ' . $rankingType);
                return response()->json(['error' => 'Table not found'], 404);
            }

            $rows = $this->parseTableRows($crawler, [
                0 => 'rank',
                1 => 'team',
                2 => 'rating',
                3 => 'high',
                4 => 'low',
                5 => 'last'
            ]);

            return response()->json(['data' => $rows, 'rankingType' => $rankingType]);

        } catch (Exception $e) {
            return $this->handleError($e);
        }
    }

    // Method to load scoring view
    public function showScoring()
    {
        return view('team_rankings.scoring'); // This loads the 'scoring.blade.php' view file
    }

    // Method to load rankings view
    public function showRankings()
    {
        return view('team_rankings.ranking'); // This loads the 'rankings.blade.php' view file
    }
}
