<?php

namespace App\Console\Commands;

use App\Models\Nfl\OddsApiNfl;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class FetchPlayerOdds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:player-odds {odds_api_id?} {--market=player_receptions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch player odds from the API and count over occurrences in the database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $apiKey = '248f37a4449cfd5b98a6400b7997214e'; // Replace with your actual API key
        $currentSeason = config('nfl.seasonYear');

        // Get the `odds_api_id` from the command argument or dynamically fetch the latest one
        $oddsApiId = $this->argument('odds_api_id') ?? $this->getLatestOddsApiId();
        $market = $this->option('market');

        if (!$oddsApiId) {
            $this->error('No odds_api_id provided or found in the database.');
            return 1;
        }

        if (!array_key_exists($market, config('nfl.markets'))) {
            $this->error('Invalid market selected.');
            return 1;
        }

        $url = "https://api.the-odds-api.com/v4/sports/americanfootball_nfl/events/{$oddsApiId}/odds";
        $cacheKey = "player_odds_{$currentSeason}_{$oddsApiId}_{$market}";

        // Fetch and cache the odds data
        $oddsData = Cache::remember($cacheKey, now()->addMinutes(120), function () use ($url, $apiKey, $market) {
            $response = Http::get($url, [
                'apiKey' => $apiKey,
                'bookmakers' => 'draftkings',
                'markets' => $market,
                'oddsFormat' => 'american',
            ]);

            if ($response->failed()) {
                $this->error('Failed to fetch API data: ' . $response->status());
                return null;
            }

            return $response->json();
        });

        if (!$oddsData) {
            $this->info('No data fetched from the API or cache.');
            return 1;
        }

        $this->processOdds($oddsData, $market);

        return 0;
    }

    /**
     * Get the latest `odds_api_id` from the database.
     *
     * @return string|null
     */
    protected function getLatestOddsApiId()
    {
        $oddsApiRecord = OddsApiNfl::latest('datetime')->first();

        return $oddsApiRecord ? $oddsApiRecord->event_id : null;
    }

    /**
     * Process odds data and update the database.
     *
     * @param array $oddsData
     * @param string $market
     * @return void
     */
    protected function processOdds($oddsData, $market)
    {
        $marketConfig = config("nfl.markets.{$market}");

        if (!$marketConfig || !$marketConfig['column']) {
            $this->info("Market {$market} is not supported or not configured.");
            return;
        }

        $column = $marketConfig['column'];
        $key = $marketConfig['key'];

        $currentSeason = config('nfl.seasonYear');
        $commenceTime = $oddsData['commence_time'];
        $weekNumber = $this->determineWeek($commenceTime);
        $outcomes = $oddsData['bookmakers'][0]['markets'][0]['outcomes'] ?? [];
        $gameId = $oddsData['id'];

        foreach ($outcomes as $outcome) {
            $description = $outcome['description'];
            $point = $outcome['point'];

            // Fetch stats dynamically based on column and key
            $playerStats = Cache::remember("player_stats_{$description}_{$market}_{$currentSeason}", now()->addMinutes(30), function () use ($description, $currentSeason, $column) {
                return DB::table('nfl_player_stats')
                    ->join('nfl_team_schedules', 'nfl_player_stats.game_id', '=', 'nfl_team_schedules.game_id')
                    ->where('nfl_player_stats.long_name', 'like', "%{$description}%")
                    ->where('nfl_team_schedules.season', $currentSeason)
                    ->pluck("nfl_player_stats.{$column}");
            });

            if ($playerStats->isEmpty()) {
                $this->info("No stats found for player: {$description} in market: {$market}");
                continue;
            }

            $overCount = 0;
            $underCount = 0;

            foreach ($playerStats as $statsJson) {
                $stats = json_decode($statsJson, true);
                $statValue = $key ? ($stats[$key] ?? 0) : 0; // Fetch specific key or default to 0

                if ($statValue > $point) {
                    $overCount++;
                } else {
                    $underCount++;
                }
            }

            DB::table('player_trends')->updateOrInsert(
                [
                    'player' => $description, // Condition to check for an existing record
                    'game_id' => $gameId,     // Add additional conditions as needed
                    'market' => $market,
                    'season' => $currentSeason,
                    'week' => $weekNumber,
                ],
                [
                    'point' => $point,
                    'over_count' => $overCount,
                    'under_count' => $underCount,
                    'odds_api_id' => $oddsData['id'],
                    'updated_at' => now(), // Always update the timestamp
                    'created_at' => now(), // Only for new records
                ]
            );


            $this->info("Processed player: {$description} for market: {$market}");
        }
    }

    /**
     * Determine the week of the season based on commence time.
     *
     * @param string $commenceTime
     * @return int|null
     */
    protected function determineWeek($commenceTime)
    {
        $weeks = config('nfl.weeks');
        $gameDate = new DateTime($commenceTime);

        foreach ($weeks as $week => $dates) {
            $start = new DateTime($dates['start']);
            $end = new DateTime($dates['end']);

            if ($gameDate >= $start && $gameDate <= $end) {
                return (int)$week;
            }
        }

        return null;
    }
}
