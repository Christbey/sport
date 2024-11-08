<?php

namespace App\Jobs;

use App\Models\Nfl\NflBoxScore;
use App\Models\Nfl\NflPlayerStat;
use App\Models\Nfl\NflTeamSchedule;
use App\Models\Nfl\NflTeamStat;
use App\Notifications\DiscordCommandCompletionNotification;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class FetchNflBoxScoreJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected $gameID;

    /**
     * Create a new job instance.
     *
     * @param string $gameID
     */
    public function __construct($gameID)
    {
        $this->gameID = $gameID;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $this->fetchAndStoreBoxScore($this->gameID);

            // Send success notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification('', 'success'));

        } catch (Exception $e) {
            // Log the error
            Log::error("Error fetching box score for game {$this->gameID}: " . $e->getMessage());

            // Send failure notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($e->getMessage(), 'error'));
        }
    }

    protected function fetchAndStoreBoxScore($gameID)
    {
        Log::info("Fetching box score for game: {$gameID}");

        $response = Http::get(route('nfl.boxscore'), ['gameID' => $gameID]);

        if ($response->successful()) {
            $data = $response->json();
            $this->storeBoxScoreData($data);
            Log::info("NFL Box Score for game {$gameID} stored successfully.");
        } else {
            Log::error("Failed to fetch box score for game {$gameID}");
        }
    }

    protected function storeBoxScoreData(array $data)
    {
        $gameData = $data['body'] ?? [];

        if (empty($gameData) || !isset($gameData['gameID'])) {
            Log::warning('Invalid game data received: "gameID" is missing.');
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
                Log::error("No schedule found for game_id {$gameData['gameID']}");
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
                        'opponent_id' => $opponentId,
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
                    'opponent_id',
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

            // Store or update team stats
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
}
