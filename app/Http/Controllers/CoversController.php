<?php

namespace App\Http\Controllers;

use Exception;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class CoversController extends Controller
{
    protected $client;

    public function __construct()
    {
        // Initialize Guzzle Client with the base URL for Covers
        $this->client = new Client([
            'base_uri' => 'https://www.covers.com',
            'timeout' => 10.0,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ],
        ]);
    }

    // Fetch all games and pass them to the view
    public function showGames()
    {
        // Fetch game data as an array
        $gameData = $this->getCoversGameIds();

        // Pass the game data to the view
        return view('covers.games', ['games' => $gameData]);
    }

    // Method to fetch all games
    public function getCoversGameIds()
    {
        $url = '/sports/nfl/matchups';
        try {
            $response = $this->client->request('GET', $url);
            $html = $response->getBody()->getContents();
            $crawler = new Crawler($html);

            // Extract the game IDs and names
            $games = $crawler->filter('article.covers-CoversScoreboard-gameBox')->each(function (Crawler $node) {
                // Extract only the game ID, removing the leading '/sports/game/'
                $covers_game_id = basename($node->attr('data-url')); // Extract only the game ID part

                return [
                    'covers_game_id' => $covers_game_id, // Only the game ID
                    'away_team' => $node->attr('data-away-team-fullname'),
                    'home_team' => $node->attr('data-home-team-fullname'),
                    'game_time' => $node->filter('.covers-CoversScoreboard-gameBox-Status span')->text(),
                ];
            });

            // Return the array of games instead of a JSON response
            return $games;

        } catch (Exception $e) {
            Log::error('Error fetching game IDs: ' . $e->getMessage());
            return []; // Return an empty array if there's an error
        }
    }

    // Method to fetch game data by covers_game_id
    public function getGameData($covers_game_id)
    {
        $url = "/sport/football/nfl/matchup/{$covers_game_id}/trends"; // URL for the specific game trends

        try {
            // Request the HTML content from the Covers matchup trends page
            $response = $this->client->request('GET', $url);
            $html = $response->getBody()->getContents();
            $crawler = new Crawler($html);

            // Extract the data from the <h4> with class "High"
            $trends = $crawler->filter('h4.High')->each(function (Crawler $node) {
                return $node->text();
            });

            // Return the extracted trends in JSON format
            return response()->json(['trends' => $trends]);

        } catch (Exception $e) {
            // Log and handle errors
            Log::error('Error fetching game details: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to fetch game data', 'message' => $e->getMessage()], 500);
        }
    }
}
