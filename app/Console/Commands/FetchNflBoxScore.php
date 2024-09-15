<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\NflBoxScore;
use App\Models\NflPlayerStat;
use App\Models\NflTeamStat;

class FetchNflBoxScore extends Command
{
    // Command signature with optional arguments
    protected $signature = 'nfl:fetch-boxscore {gameID?}';

    // Command description
    protected $description = 'Fetch NFL box score from the API route and store the data';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Get optional arguments and provide default values if not provided
        $gameID = $this->argument('gameID') ?? '20240810_CHI@BUF';  // Default gameID

        // Make an HTTP request to the route you've defined
        $response = Http::get(route('nfl.boxscore'), [
            'gameID' => $gameID,
        ]);

        // Check for a successful response
        if ($response->successful()) {
            $data = $response->json();
            $this->storeBoxScoreData($data);
            $this->info('NFL Box Score data stored successfully.');
        } else {
            $this->error('Failed to fetch box score.');
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
}
