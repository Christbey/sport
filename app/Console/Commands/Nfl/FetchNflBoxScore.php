<?php

namespace App\Console\Commands\Nfl;

use App\Models\Nfl\NflTeamSchedule;
use App\Models\NflBoxScore;
use App\Models\NflPlayerStat;
use App\Models\NflTeamStat;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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
        } elseif ($gameID = $this->argument('gameID')) {
            $this->fetchAndStoreBoxScore($gameID);
        } else {
            $this->fetchBoxScoresForCurrentWeek();
        }
    }

    /**
     * Fetch all box scores before today's date.
     */
    protected function fetchAllBoxScoresBeforeToday()
    {
        $today = Carbon::today();
        $this->info("Fetching all box scores for games before {$today}");

        $games = NflTeamSchedule::where('game_date', '<', $today)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('nfl_box_scores')
                    ->whereColumn('nfl_box_scores.game_id', 'nfl_team_schedules.game_id');
            })
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

    /**
     * Fetch and store a box score for a given game ID.
     */
    protected function fetchAndStoreBoxScore($gameID)
    {
        $this->info("Fetching box score for game: {$gameID}");

        $response = Http::get(route('nfl.boxscore'), ['gameID' => $gameID]);

        if ($response->successful()) {
            $data = $response->json();
            $this->storeBoxScoreData($data);
            $this->info("NFL Box Score for game {$gameID} stored successfully.");
        } else {
            $this->error("Failed to fetch box score for game {$gameID}");
        }
    }

    /**
     * Store the box score data, including player stats and team stats.
     */
    protected function storeBoxScoreData(array $data)
    {
        $gameData = $data['body'];

        // Use a DB transaction for safe multi-step database operations
        DB::transaction(function () use ($gameData) {
            // Store Box Score
            NflBoxScore::updateOrCreate(
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

            // Batch Insert/Update Player Stats
            if (isset($gameData['playerStats'])) {
                $playerStatsData = [];
                foreach ($gameData['playerStats'] as $playerID => $playerStats) {
                    $playerStatsData[] = [
                        'player_id' => $playerID,
                        'game_id' => $gameData['gameID'],
                        'team_id' => $playerStats['teamID'] ?? null,
                        'team_abv' => $playerStats['teamAbv'] ?? null,
                        'long_name' => $playerStats['longName'] ?? null,
                        'receiving' => isset($playerStats['Receiving']) ? json_encode($playerStats['Receiving']) : null,
                        'rushing' => isset($playerStats['Rushing']) ? json_encode($playerStats['Rushing']) : null,
                        'kicking' => isset($playerStats['Kicking']) ? json_encode($playerStats['Kicking']) : null,
                        'punting' => isset($playerStats['Punting']) ? json_encode($playerStats['Punting']) : null,
                        'defense' => isset($playerStats['Defense']) ? json_encode($playerStats['Defense']) : null,
                    ];
                }
                NflPlayerStat::upsert($playerStatsData, ['player_id', 'game_id']);
            }

            // Batch Insert/Update Team Stats
            if (isset($gameData['teamStats'])) {
                $teamStatsData = [];
                foreach ($gameData['teamStats'] as $teamStats) {
                    $teamStatsData[] = [
                        'team_id' => $teamStats['teamID'],
                        'game_id' => $gameData['gameID'],
                        'team_abv' => $teamStats['teamAbv'] ?? null,
                        'total_yards' => $teamStats['totalYards'] ?? null,
                        'rushing_yards' => $teamStats['rushingYards'] ?? null,
                        'passing_yards' => $teamStats['passingYards'] ?? null,
                        'points_allowed' => $teamStats['ptsAllowed'] ?? null,
                    ];
                }
                NflTeamStat::upsert($teamStatsData, ['team_id', 'game_id']);
            }
        });
    }

    /**
     * Fetch box scores for the current week based on the NFL schedule.
     */
    protected function fetchBoxScoresForCurrentWeek()
    {
        $currentDate = Carbon::now();
        $weekConfig = $this->getCurrentWeekConfig($currentDate);

        if (!$weekConfig) {
            $this->error('No matching week found for the current date.');
            return;
        }

        $this->info("Fetching box scores between {$weekConfig['start']} and {$weekConfig['end']}");
        $games = NflTeamSchedule::whereBetween('game_date', [$weekConfig['start'], $weekConfig['end']])
            ->pluck('game_id');

        if ($games->isEmpty()) {
            $this->error("No games found for the date range {$weekConfig['start']} to {$weekConfig['end']}");
            return;
        }

        // Fetch and store box scores for each game
        foreach ($games as $gameID) {
            $this->fetchAndStoreBoxScore($gameID);
        }
    }

    /**
     * Get the current week configuration based on the date.
     */
    protected function getCurrentWeekConfig($date)
    {
        return collect(config('nfl.weeks'))->first(function ($week) use ($date) {
            return Carbon::parse($week['start'])->lte($date) && Carbon::parse($week['end'])->gte($date);
        });
    }
}
