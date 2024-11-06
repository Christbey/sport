<?php

namespace App\Console\Commands\Nfl;

use App\Models\Nfl\NflBoxScore;
use App\Models\Nfl\NflPlayerStat;
use App\Models\Nfl\NflTeamSchedule;
use App\Models\Nfl\NflTeamStat;
use App\Notifications\DiscordCommandCompletionNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

class FetchNflBoxScore extends Command
{
    protected $signature = 'nfl:fetch-boxscore {gameID?} {--all} {--week=}';
    protected $description = 'Fetch NFL box score for a specific game or for games in the specified week';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try {
            $gameID = $this->argument('gameID');
            $weekOption = $this->option('week');

            // Determine which box scores to fetch based on options
            if ($this->option('all')) {
                $this->fetchAllBoxScoresBeforeToday();
            } elseif ($gameID) {
                $this->fetchAndStoreBoxScore($gameID);
            } elseif ($weekOption) {
                $this->fetchBoxScoresForSpecifiedWeek($weekOption);
            } else {
                $this->fetchBoxScoresForCurrentWeek();
            }

            // Send success notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification('', 'success'));

        } catch (Exception $e) {
            // Send failure notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($e->getMessage(), 'error'));
        }
    }

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

        foreach ($games as $gameID) {
            $this->fetchAndStoreBoxScore($gameID);
        }
    }

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

    protected function storeBoxScoreData(array $data)
    {
        $gameData = $data['body'] ?? [];

        if (empty($gameData) || !isset($gameData['gameID'])) {
            $this->info('Invalid game data received: "gameID" is missing.');
            return;
        }

        DB::transaction(function () use ($gameData) {
            // Store or update the box score
            NflBoxScore::updateOrCreate(
                ['game_id' => $gameData['gameID']],
                [
                    'home_team' => $gameData['home'] ?? null,
                    'away_team' => $gameData['away'] ?? null,
                    'home_points' => $gameData['homePts'] ?? 0,
                    'away_points' => $gameData['awayPts'] ?? 0,
                    'game_date' => $gameData['gameDate'] ?? null,
                    'location' => $gameData['gameLocation'] ?? null,
                    'home_line_score' => $gameData['lineScore']['home'] ?? null,
                    'away_line_score' => $gameData['lineScore']['away'] ?? null,
                    'away_result' => $gameData['awayResult'] ?? null,
                    'home_result' => $gameData['homeResult'] ?? null,
                    'game_status' => $gameData['gameStatus'] ?? null,
                ]
            );

            // Fetch game information to determine opponent IDs
            $gameSchedule = NflTeamSchedule::where('game_id', $gameData['gameID'])->first();
            if (!$gameSchedule) {
                $this->error("No schedule found for game_id {$gameData['gameID']}");
                return;
            }

            // Store or update player stats
            if (isset($gameData['playerStats'])) {
                $playerStatsData = [];
                foreach ($gameData['playerStats'] as $playerID => $playerStats) {
                    // Determine the opponent ID
                    $teamId = isset($playerStats['teamID']) ? (int)$playerStats['teamID'] : null;
                    $opponentId = ($teamId === $gameSchedule->home_team_id) ? $gameSchedule->away_team_id : $gameSchedule->home_team_id;

                    $playerStatsData[] = [
                        'player_id' => (int)$playerID,
                        'game_id' => $gameData['gameID'],
                        'team_id' => $teamId,
                        'opponent_id' => $opponentId,  // New field
                        'team_abv' => isset($playerStats['teamAbv']) ? trim($playerStats['teamAbv']) : null,
                        'long_name' => isset($playerStats['longName']) ? trim($playerStats['longName']) : null,
                        'receiving' => isset($playerStats['Receiving']) ? json_encode($playerStats['Receiving']) : null,
                        'rushing' => isset($playerStats['Rushing']) ? json_encode($playerStats['Rushing']) : null,
                        'kicking' => isset($playerStats['Kicking']) ? json_encode($playerStats['Kicking']) : null,
                        'punting' => isset($playerStats['Punting']) ? json_encode($playerStats['Punting']) : null,
                        'defense' => isset($playerStats['Defense']) ? json_encode($playerStats['Defense']) : null,
                    ];
                }

                // Specify the columns to update to prevent duplicates
                $playerUpdateColumns = [
                    'team_id',
                    'opponent_id',  // Ensure opponent_id is updated
                    'team_abv',
                    'long_name',
                    'receiving',
                    'rushing',
                    'kicking',
                    'punting',
                    'defense',
                    'updated_at',
                ];

                NflPlayerStat::upsert($playerStatsData, ['player_id', 'game_id'], $playerUpdateColumns);
            }

            // Store or update team stats (unchanged)
            if (isset($gameData['teamStats'])) {
                $teamStatsData = [];
                foreach ($gameData['teamStats'] as $teamStats) {
                    $teamStatsData[] = [
                        'team_id' => isset($teamStats['teamID']) ? (int)$teamStats['teamID'] : null,
                        'game_id' => $gameData['gameID'],
                        'team_abv' => isset($teamStats['teamAbv']) ? trim($teamStats['teamAbv']) : null,
                        'total_yards' => isset($teamStats['totalYards']) ? (int)$teamStats['totalYards'] : null,
                        'rushing_yards' => isset($teamStats['rushingYards']) ? (int)$teamStats['rushingYards'] : null,
                        'passing_yards' => isset($teamStats['passingYards']) ? (int)$teamStats['passingYards'] : null,
                        'points_allowed' => isset($teamStats['ptsAllowed']) ? (int)$teamStats['ptsAllowed'] : null,
                    ];
                }

                // Specify the columns to update to prevent duplicates
                $teamUpdateColumns = [
                    'team_abv',
                    'total_yards',
                    'rushing_yards',
                    'passing_yards',
                    'points_allowed',
                    'updated_at',
                ];

                NflTeamStat::upsert($teamStatsData, ['team_id', 'game_id'], $teamUpdateColumns);
            }
        });
    }

    protected function fetchBoxScoresForSpecifiedWeek($weekNumber)
    {
        $weeks = config('nfl.weeks');
        $weekConfig = $weeks["Week {$weekNumber}"] ?? null;

        if (!$weekConfig) {
            $this->error("Invalid week number provided: {$weekNumber}");
            return;
        }

        $this->info("Fetching box scores between {$weekConfig['start']} and {$weekConfig['end']} for Week {$weekNumber}");

        $games = NflTeamSchedule::whereBetween('game_date', [$weekConfig['start'], $weekConfig['end']])
            ->pluck('game_id');

        if ($games->isEmpty()) {
            $this->error("No games found for the date range {$weekConfig['start']} to {$weekConfig['end']}");
            return;
        }

        foreach ($games as $gameID) {
            $this->fetchAndStoreBoxScore($gameID);
        }
    }

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

        foreach ($games as $gameID) {
            $this->fetchAndStoreBoxScore($gameID);
        }
    }

    protected function getCurrentWeekConfig($date)
    {
        return collect(config('nfl.weeks'))->first(function ($week) use ($date) {
            return Carbon::parse($week['start'])->lte($date) && Carbon::parse($week['end'])->gte($date);
        });
    }
}
