<?php

namespace App\Http\Controllers;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NflRapidApiController extends Controller
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com/',
        ]);

        $this->apiKey = config('services.rapidapi.key'); // Store the API key in .env
    }

    public function getNFLBettingOdds(Request $request): JsonResponse
    {
        $queryParams = $request->all();
        return $this->fetchData('getNFLBettingOdds', $queryParams);
    }

    protected function fetchData(string $endpoint, array $queryParams = []): JsonResponse
    {
        try {
            $response = $this->client->request('GET', $endpoint, [
                'query' => $queryParams,
                'headers' => [
                    'x-rapidapi-host' => 'tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com',
                    'x-rapidapi-key' => $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getNFLNews(Request $request): JsonResponse
    {
        $queryParams = $request->all();
        return $this->fetchData('getNFLNews', $queryParams);
    }

    public function getNFLScoresOnly(Request $request): JsonResponse
    {
        $queryParams = $request->all();
        return $this->fetchData('getNFLScoresOnly', $queryParams);
    }

    public function getNFLTeamSchedule(Request $request): JsonResponse
    {
        $queryParams = $request->all();
        return $this->fetchData('getNFLTeamSchedule', $queryParams);
    }

    public function getNFLTeams(Request $request): JsonResponse
    {
        $queryParams = $request->all();
        return $this->fetchData('getNFLTeams', $queryParams);
    }

    public function getNFLPlayerInfo(Request $request): JsonResponse
    {
        $queryParams = $request->all();
        return $this->fetchData('getNFLPlayerInfo', $queryParams);
    }

    public function getNFLGamesForPlayer(Request $request): JsonResponse
    {
        $queryParams = $request->all();
        return $this->fetchData('getNFLGamesForPlayer', $queryParams);
    }

    public function getNFLPlayerList(): JsonResponse
    {
        return $this->fetchData('getNFLPlayerList');
    }

    public function getNFLTeamRoster(Request $request): JsonResponse
    {
        // Extract the parameters from the request
        $teamID = $request->input('teamID', 6); // Default to teamID=6
        $teamAbv = $request->input('teamAbv', 'CHI'); // Default to teamAbv=CHI
        $getStats = $request->input('getStats', 'true');
        $fantasyPoints = $request->input('fantasyPoints', 'true');

        // Build query parameters
        $queryParams = [
            'teamID' => $teamID,
            'teamAbv' => $teamAbv,
            'getStats' => $getStats,
            'fantasyPoints' => $fantasyPoints,
        ];

        // Call the fetchData method to retrieve the data from the endpoint
        return $this->fetchData('getNFLTeamRoster', $queryParams);
    }

    public function getNFLBoxScore(Request $request): JsonResponse
    {
        $gameID = $request->input('gameID', '20240810_CHI@BUF');  // Default value for gameID

        try {
            // Make the API request
            $response = $this->client->request('GET', 'getNFLBoxScore', [
                'query' => [
                    'gameID' => $gameID,
                    'playByPlay' => true,
                    'fantasyPoints' => false,
                    #'twoPointConversions' => 2,
                    #'passYards' => 0.04,
                    #'passAttempts' => 0,
                    #'passTD' => 4,
                    #'passCompletions' => 0,
                    #'passInterceptions' => -2,
                    #'pointsPerReception' => 0.5,
                    #'carries' => 0.2,
                    #'rushYards' => 0.1,
                    #'rushTD' => 6,
                    #'fumbles' => -2,
                    #'receivingYards' => 0.1,
                    #'receivingTD' => 6,
                    #'targets' => 0,
                    #'defTD' => 6,
                    #'fgMade' => 3,
                    #'fgMissed' => -3,
                    #'xpMade' => 1,
                    #'xpMissed' => -1,
                ],
                'headers' => [
                    'x-rapidapi-host' => 'tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com',
                    'x-rapidapi-key' => $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            return response()->json($data);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


}
