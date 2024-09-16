<?php

namespace App\Console\Commands;

use App\Models\Nfl\NflTeamSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\NflBoxScore;
use App\Models\NflPlayerStat;
use App\Models\NflTeamStat;
use Carbon\Carbon;

class FetchNflBoxScore extends Command
{
    // Command signature with an optional gameID and 'all' method
    protected $signature = 'nfl:fetch-boxscore {gameID?} {--all}';

    // Command description
    protected $description = 'Fetch NFL box score for a specific game or all missing games before today, and store the data';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Check if the 'all' option is used
        if ($this->option('all')) {
            $this->fetchAllBoxScoresBeforeToday();
            return;
        }

        // Get optional gameID argument
        $gameID = $this->argument('gameID');

        // If a gameID is provided, fetch only that game's box score
        if ($gameID) {
            $this->fetchAndStoreBoxScore($gameID);
        } else {
            // If no gameID is provided, run for all game_ids within the current week's date range
            $this->fetchBoxScoresForCurrentWeek();
        }
    }

    protected function fetchBoxScoresForCurrentWeek()
    {
        // Get the current date
        $currentDate = Carbon::now();

        // Loop through the config weeks and find the current week based on date
        $currentWeekConfig = collect(config('nfl.weeks'))->first(function ($week, $key) use ($currentDate) {
            $startDate = Carbon::parse($week['start']);
            $endDate = Carbon::parse($week['end']);
            return $currentDate->between($startDate, $endDate);
        });

        if (!$currentWeekConfig) {
            $this->error('No matching week found for the current date.');
            return;
        }

        $startDate = $currentWeekConfig['start'];
        $endDate = $currentWeekConfig['end'];

        $this->info("Fetching box scores for games between {$startDate} and {$endDate}");

        // Fetch all game_ids between the start and end date from the nfl_team_schedules table
        $games = NflTeamSchedule::whereBetween('game_date', [$startDate, $endDate])->pluck('game_id');

        if ($games->isEmpty()) {
            $this->error("No games found for the date range {$startDate} to {$endDate}");
            return;
        }

        // Loop through each game_id and fetch/store box score
        foreach ($games as $gameID) {
            $this->fetchAndStoreBoxScore($gameID);
        }
    }

    protected function fetchAndStoreBoxScore($gameID)
    {
        $this->info("Fetching box score for game: {$gameID}");

        // Make an HTTP request to the route you've defined
        $response = Http::get(route('nfl.boxscore'), [
            'gameID' => $gameID,
        ]);

        // Check if the response is successful
        if ($response->successful()) {
            $data = $response->json();
            $this->storeBoxScoreData($data);
            $this->info("NFL Box Score for game {$gameID} stored successfully.");
        } else {
            $this->error("Failed to fetch box score for game {$gameID}");
        }
    }

    protected function storeBoxScoreData(array $data)
    {
        $gameData = $data['body'];

        // Store Box Score
        $boxScore = NflBoxScore::updateOrCreate(
            ['game_id' => $gameData['gameID']],
            [
                'home_team' => $gameData['home'],
                'away_team' => $gameData['away'],
                'home_points' => $gameData['homePts'] ?? 0,
                'away_points' => $gameData['awayPts'] ?? 0,
                'game_date' => $gameData['gameDate'],
                'location' => $gameData['gameLocation'] ?? null,
                'home_line_score' => $gameData['lineScore']['home'] ?? null,
                'away_line_score' => $gameData['lineScore']['away'] ?? null,
                'away_result' => $gameData['awayResult'] ?? null,
                'home_result' => $gameData['homeResult'] ?? null,
                'game_status' => $gameData['gameStatus'] ?? null,
            ]
        );

        // Store Player Stats
        if (isset($gameData['playerStats'])) {
            foreach ($gameData['playerStats'] as $playerID => $playerStats) {
                NflPlayerStat::updateOrCreate(
                    [
                        'player_id' => $playerID,
                        'game_id' => $gameData['gameID']
                    ],
                    [
                        'team_id' => $playerStats['teamID'] ?? null,
                        'team_abv' => $playerStats['teamAbv'] ?? null,
                        'long_name' => $playerStats['longName'] ?? null,  // Storing longName
                        'receiving' => $playerStats['Receiving'] ?? null,
                        'rushing' => $playerStats['Rushing'] ?? null,
                        'kicking' => $playerStats['Kicking'] ?? null,
                        'punting' => $playerStats['Punting'] ?? null,
                        'defense' => $playerStats['Defense'] ?? null,
                    ]
                );
            }
        }

        // Store Team Stats
        if (isset($gameData['teamStats'])) {
            foreach ($gameData['teamStats'] as $teamType => $teamStats) {
                NflTeamStat::updateOrCreate(
                    ['game_id' => $gameData['gameID'], 'team_id' => $teamStats['teamID']],
                    [
                        'team_abv' => $teamStats['teamAbv'] ?? null,
                        'total_yards' => $teamStats['totalYards'] ?? null,
                        'rushing_yards' => $teamStats['rushingYards'] ?? null,
                        'passing_yards' => $teamStats['passingYards'] ?? null,
                        'points_allowed' => $teamStats['ptsAllowed'] ?? null,
                    ]
                );
            }
        }
    }

    protected function fetchAllBoxScoresBeforeToday()
    {
        $today = Carbon::today();
        $this->info("Fetching all box scores for games before {$today}");

        // Get all game_ids from nfl_team_schedules that have not been stored in the nfl_box_scores table
        $games = NflTeamSchedule::where('game_date', '<', $today)
            ->whereNotIn('game_id', NflBoxScore::pluck('game_id'))
            ->pluck('game_id');

        if ($games->isEmpty()) {
            $this->info("No games found before {$today} that are missing box scores.");
            return;
        }

        // Fetch box score for each missing game_id
        foreach ($games as $gameID) {
            $this->fetchAndStoreBoxScore($gameID);
        }
    }
}
