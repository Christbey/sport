<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TeamRankingController extends Controller
{
    protected $client;

    public function __construct()
    {
        // Initialize Guzzle Client with base URL
        $this->client = new Client([
            'base_uri' => 'https://www.teamrankings.com',
            'timeout'  => 10.0,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-User' => '?1',
                'Sec-Fetch-Dest' => 'document',
            ],
        ]);

    }


    public function fetchStatData($category, $stat)
    {
        // Dynamically build the URL based on the category and stat type
        $url = "/nfl/stat/{$stat}";

        try {
            // Fetch the HTML page using Guzzle
            $response = $this->client->request('GET', $url);
            $html = $response->getBody()->getContents();

            // Use DomCrawler to parse the HTML and extract data
            $crawler = new Crawler($html);
            $rows = $crawler->filter('table.tr-table.datatable.scrollable tbody tr')->each(function (Crawler $row) {
                return [
                    'rank' => $row->filter('td')->eq(0)->text(),
                    'team' => $row->filter('td')->eq(1)->text(),
                    'team_link' => $row->filter('td')->eq(1)->filter('a')->attr('href'),
                    '2024' => $row->filter('td')->eq(2)->text(),
                    'last_3' => $row->filter('td')->eq(3)->text(),
                    'last_1' => $row->filter('td')->eq(4)->text(),
                    'home' => $row->filter('td')->eq(5)->text(),
                    'away' => $row->filter('td')->eq(6)->text(),
                    '2023' => $row->filter('td')->eq(7)->text(),
                ];
            });

            // Return a view with the data
            return view('team_rankings.stat_data', ['rows' => $rows, 'stat' => $stat]);

        } catch (\Exception $e) {
            // Handle exception
            return response()->json(['error' => 'Unable to fetch data', 'message' => $e->getMessage()], 500);
        }
    }

    public function fetchRankings($rankingType)
    {
        // URL for fetching the table data
        $url = "https://www.teamrankings.com/nfl/ranking/{$rankingType}";

        try {
            // Fetch the HTML page using Guzzle
            $response = $this->client->request('GET', $url);
            $html = $response->getBody()->getContents();

            // Use DomCrawler to parse the HTML and extract the table
            $crawler = new Crawler($html);

            // Check if the table exists
            if (!$crawler->filter('table.tr-table.datatable.scrollable')->count()) {
                Log::warning('Table not found for the provided ranking type: ' . $rankingType);
                return response()->json(['error' => 'Unable to fetch data', 'message' => 'Table not found in the HTML.'], 404);
            }

            // Check if there are rows in the table
            if ($crawler->filter('table.tr-table.datatable.scrollable tbody tr')->count() === 0) {
                Log::warning('No table rows found for the provided ranking type: ' . $rankingType);
                return response()->json(['error' => 'Unable to fetch data', 'message' => 'No data in table.'], 404);
            }

            // Extract table rows with ranking information
            $rows = $crawler->filter('table.tr-table.datatable.scrollable tbody tr')->each(function (Crawler $row) {
                // Default row structure
                $data = [
                    'rank' => $row->filter('td')->eq(0)->text(),
                    'team' => $row->filter('td')->eq(1)->text(),
                    'team_link' => $row->filter('td')->eq(1)->filter('a')->attr('href'),
                    'rating' => $row->filter('td')->eq(2)->text(),
                    'high' => $row->filter('td')->eq(3)->text(),
                    'low' => $row->filter('td')->eq(4)->text(),
                    'last' => $row->filter('td')->eq(5)->text(),
                ];

                // If the table includes v_1_5, v_6_10, v_11_16, add these fields
                if ($row->filter('td')->count() > 6) {
                    $data['v_1_5'] = $row->filter('td')->eq(3)->text();
                    $data['v_6_10'] = $row->filter('td')->eq(4)->text();
                    $data['v_11_16'] = $row->filter('td')->eq(5)->text();
                    $data['high'] = $row->filter('td')->eq(6)->text();
                    $data['low'] = $row->filter('td')->eq(7)->text();
                    $data['last'] = $row->filter('td')->eq(8)->text();
                }

                return $data;
            });

            // Return the extracted data to a view
            return view('team_rankings.ranking_data', ['rows' => $rows, 'rankingType' => $rankingType]);

        } catch (\Exception $e) {
            // Log the error message for debugging
            Log::error('Error fetching data for ranking type: ' . $rankingType . ' - ' . $e->getMessage());
            return response()->json(['error' => 'Unable to fetch data', 'message' => $e->getMessage()], 500);
        }
    }

}
