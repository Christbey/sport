<?php

namespace App\Console\Commands;

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
    protected $signature = 'fetch:player-odds';

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
        $url = 'https://api.the-odds-api.com/v4/sports/americanfootball_nfl/events/1bccaf2a9388f9d02f8f0c23cbc6b121/odds';
        $currentSeason = config('nfl.seasonYear');
        $cacheKey = "player_odds_{$currentSeason}";

        // Fetch and cache the odds data
        $oddsData = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($url, $apiKey) {
            $response = Http::get($url, [
                'apiKey' => $apiKey,
                'bookmakers' => 'draftkings',
                'markets' => 'player_receptions',
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

        $homeTeamName = $oddsData['home_team'];
        $awayTeamName = $oddsData['away_team'];

        // Match home and away teams using team_abv from nfl_teams
        $homeTeamAbv = DB::table('nfl_teams')
            ->whereRaw("CONCAT(team_city, ' ', team_name) like ?", ["%{$homeTeamName}%"])
            ->value('team_abv');

        $awayTeamAbv = DB::table('nfl_teams')
            ->whereRaw("CONCAT(team_city, ' ', team_name) like ?", ["%{$awayTeamName}%"])
            ->value('team_abv');

        if (!$homeTeamAbv || !$awayTeamAbv) {
            $this->info("No matching teams found for {$homeTeamName} and/or {$awayTeamName}.");
            return 1;
        }

        // Match game in nfl_team_schedules using team_abv
        $game = DB::table('nfl_team_schedules')
            ->where('season', $currentSeason)
            ->where('home_team', $homeTeamAbv)
            ->where('away_team', $awayTeamAbv)
            ->first();

        if (!$game) {
            $this->info("No game found for {$homeTeamName} vs. {$awayTeamName} in the current season.");
            return 1;
        }

        // Pass the odds data to processOdds
        $this->processOdds($oddsData);

        return 0;
    }

    protected function processOdds($oddsData)
    {
        $currentSeason = config('nfl.seasonYear');
        $commenceTime = $oddsData['commence_time'];
        $weekNumber = $this->determineWeek($commenceTime);
        $outcomes = $oddsData['bookmakers'][0]['markets'][0]['outcomes'] ?? [];
        $gameId = $oddsData['id']; // Use the game ID from the API response

        foreach ($outcomes as $outcome) {
            $description = $outcome['description'];
            $point = $outcome['point'];

            // Cache the player statistics
            $playerStats = Cache::remember("player_stats_{$description}_{$currentSeason}", now()->addMinutes(30), function () use ($description, $currentSeason) {
                return DB::table('nfl_player_stats')
                    ->join('nfl_team_schedules', 'nfl_player_stats.game_id', '=', 'nfl_team_schedules.game_id')
                    ->where('nfl_player_stats.long_name', 'like', "%{$description}%")
                    ->where('nfl_team_schedules.season', $currentSeason)
                    ->pluck('nfl_player_stats.receiving'); // Replace 'receiving' with the correct column
            });

            if ($playerStats->isEmpty()) {
                $this->info("No stats found for player: {$description}");
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

            DB::table('player_trends')->insert([
                'player' => $description,
                'point' => $point,
                'over_count' => $overCount,
                'under_count' => $underCount,
                'game_id' => $gameId,
                'odds_api_id' => $oddsData['id'],
                'season' => $currentSeason,
                'week' => $weekNumber,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->info("Player: {$description}");
            $this->info("Point: {$point}");
            $this->info("Over Count: {$overCount}");
            $this->info("Under Count: {$underCount}");
            $this->info("Week: {$weekNumber}");
            $this->info('---');
        }
    }

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

        return null; // Return null if no matching week is found
    }


}
