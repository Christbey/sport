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

        $this->apiKey = env('RAPIDAPI_KEY'); // Store the API key in .env
    }

    public function getNFLBettingOdds(Request $request): JsonResponse
    {
        $queryParams = $request->all();
        return $this->fetchData('getNFLBettingOdds', $queryParams);
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
}
