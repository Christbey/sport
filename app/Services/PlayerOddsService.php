<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PlayerOddsService
{
    protected $apiKey;
    protected $url;

    public function __construct()
    {
        $this->apiKey = '248f37a4449cfd5b98a6400b7997214e'; // Replace with your actual API key
        $this->url = 'https://api.the-odds-api.com/v4/sports/americanfootball_nfl/events/1bccaf2a9388f9d02f8f0c23cbc6b121/odds';
    }

    public function fetchOddsData()
    {
        $currentSeason = config('nfl.seasonYear');
        $cacheKey = "player_odds_{$currentSeason}";

        // Cache the API response
        return Cache::remember($cacheKey, now()->addMinutes(30), function () {
            $response = Http::get($this->url, [
                'apiKey' => $this->apiKey,
                'bookmakers' => 'draftkings',
                'markets' => 'player_receptions',
                'oddsFormat' => 'american',
            ]);

            if ($response->failed()) {
                return null;
            }

            return $response->json();
        });
    }

    public function processOdds($oddsData)
    {
        $currentSeason = config('nfl.seasonYear');
        $outcomes = $oddsData['bookmakers'][0]['markets'][0]['outcomes'] ?? [];
        $results = [];

        foreach ($outcomes as $outcome) {
            $description = $outcome['description'];
            $point = $outcome['point'];

            // Cache the player statistics
            $playerStats = Cache::remember("player_stats_{$description}_{$currentSeason}", now()->addMinutes(30), function () use ($description, $currentSeason) {
                return DB::table('nfl_player_stats')
                    ->join('nfl_team_schedules', 'nfl_player_stats.game_id', '=', 'nfl_team_schedules.game_id')
                    ->where('nfl_player_stats.long_name', $description)
                    ->where('nfl_team_schedules.season', $currentSeason)
                    ->pluck('nfl_player_stats.receiving'); // Replace 'receiving' with the correct column
            });

            if ($playerStats->isEmpty()) {
                continue;
            }

            $overCount = 0;
            $underCount = 0;

            foreach ($playerStats as $statsJson) {
                $stats = json_decode($statsJson, true);
                $receptions = isset($stats['receptions']) ? (int)$stats['receptions'] : 0;

                if ($receptions > $point) {
                    $overCount++;
                } else {
                    $underCount++;
                }
            }

            $results[] = [
                'player' => $description,
                'point' => $point,
                'overCount' => $overCount,
                'underCount' => $underCount,
            ];
        }

        return $results;
    }
}
