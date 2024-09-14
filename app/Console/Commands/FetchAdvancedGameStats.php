<?php

namespace App\Console\Commands;

use App\Models\AdvancedGameStat;
use App\Models\CollegeFootball\CollegeFootballTeam;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchAdvancedGameStats extends Command
{
    // The name and signature of the console command.
    protected $signature = 'fetch:advanced-game-stats';

    // The console command description.
    protected $description = 'Fetch advanced game stats for the year 2024';

    // Guzzle client and API key
    protected $client;
    protected $apiKey;

    // Constructor to initialize Guzzle client and API key
    public function __construct()
    {
        parent::__construct();

        // Initialize Guzzle client with base URI
        $this->client = new Client([
            'base_uri' => 'https://apinext.collegefootballdata.com/',
        ]);

        // Retrieve the API key from the config file
        $this->apiKey = config('services.college_football_data.key');
    }

    // Execute the console command
    public function handle()
    {
        $url = 'stats/game/advanced?year=2024';  // The endpoint URL

        try {
            // Send a GET request to the API
            $response = $this->client->request('GET', $url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,  // Pass the API key in the Authorization header
                ]
            ]);

            // Decode the response body into an array
            $data = json_decode($response->getBody(), true);

            // Process the data
            foreach ($data as $gameStat) {
                // Call the function to save each game stat
                $this->saveGameStat($gameStat);
            }

            $this->info('Advanced game stats fetched and saved successfully.');

        } catch (\Exception $e) {
            // Log any errors
            Log::error('Failed to fetch advanced game stats. Error: ' . $e->getMessage());
            $this->error('Failed to fetch advanced game stats.');
        }
    }

    private function saveGameStat($gameStat)
    {
        // Find the team_id based on the team name
        $team = CollegeFootballTeam::where('school', $gameStat['team'])->first();
        $teamId = $team ? $team->id : null; // If team not found, set team_id to null

        AdvancedGameStat::updateOrCreate(
            ['game_id' => $gameStat['gameId'], 'team' => $gameStat['team']],
            [
                'team_id' => $teamId, // Store the matched team_id
                'season' => $gameStat['season'],
                'week' => $gameStat['week'],
                'opponent' => $gameStat['opponent'],

                // Offense Stats
                'offense_plays' => $gameStat['offense']['plays'],
                'offense_drives' => $gameStat['offense']['drives'],
                'offense_ppa' => $gameStat['offense']['ppa'],
                'offense_total_ppa' => $gameStat['offense']['totalPPA'],
                'offense_success_rate' => $gameStat['offense']['successRate'],
                'offense_explosiveness' => $gameStat['offense']['explosiveness'],
                'offense_power_success' => $gameStat['offense']['powerSuccess'] ?? null,
                'offense_stuff_rate' => $gameStat['offense']['stuffRate'] ?? null,
                'offense_line_yards' => $gameStat['offense']['lineYards'] ?? null,
                'offense_line_yards_total' => $gameStat['offense']['lineYardsTotal'] ?? null,
                'offense_second_level_yards' => $gameStat['offense']['secondLevelYards'] ?? null,
                'offense_second_level_yards_total' => $gameStat['offense']['secondLevelYardsTotal'] ?? null,
                'offense_open_field_yards' => $gameStat['offense']['openFieldYards'] ?? null,
                'offense_open_field_yards_total' => $gameStat['offense']['openFieldYardsTotal'] ?? null,

                // Standard Downs Stats
                'offense_standard_downs_ppa' => $gameStat['offense']['standardDowns']['ppa'] ?? null,
                'offense_standard_downs_success_rate' => $gameStat['offense']['standardDowns']['successRate'] ?? null,
                'offense_standard_downs_explosiveness' => $gameStat['offense']['standardDowns']['explosiveness'] ?? null,

                // Passing Downs Stats
                'offense_passing_downs_ppa' => $gameStat['offense']['passingDowns']['ppa'] ?? null,
                'offense_passing_downs_success_rate' => $gameStat['offense']['passingDowns']['successRate'] ?? null,
                'offense_passing_downs_explosiveness' => $gameStat['offense']['passingDowns']['explosiveness'] ?? null,

                // Rushing Plays
                'offense_rushing_ppa' => $gameStat['offense']['rushingPlays']['ppa'] ?? null,
                'offense_rushing_total_ppa' => $gameStat['offense']['rushingPlays']['totalPPA'] ?? null,
                'offense_rushing_success_rate' => $gameStat['offense']['rushingPlays']['successRate'] ?? null,
                'offense_rushing_explosiveness' => $gameStat['offense']['rushingPlays']['explosiveness'] ?? null,

                // Passing Plays
                'offense_passing_ppa' => $gameStat['offense']['passingPlays']['ppa'] ?? null,
                'offense_passing_total_ppa' => $gameStat['offense']['passingPlays']['totalPPA'] ?? null,
                'offense_passing_success_rate' => $gameStat['offense']['passingPlays']['successRate'] ?? null,
                'offense_passing_explosiveness' => $gameStat['offense']['passingPlays']['explosiveness'] ?? null,

                // Defense Stats
                'defense_plays' => $gameStat['defense']['plays'],
                'defense_drives' => $gameStat['defense']['drives'],
                'defense_ppa' => $gameStat['defense']['ppa'],
                'defense_total_ppa' => $gameStat['defense']['totalPPA'],
                'defense_success_rate' => $gameStat['defense']['successRate'],
                'defense_explosiveness' => $gameStat['defense']['explosiveness'],
                'defense_power_success' => $gameStat['defense']['powerSuccess'] ?? null,
                'defense_stuff_rate' => $gameStat['defense']['stuffRate'] ?? null,
                'defense_line_yards' => $gameStat['defense']['lineYards'] ?? null,
                'defense_line_yards_total' => $gameStat['defense']['lineYardsTotal'] ?? null,
                'defense_second_level_yards' => $gameStat['defense']['secondLevelYards'] ?? null,
                'defense_second_level_yards_total' => $gameStat['defense']['secondLevelYardsTotal'] ?? null,
                'defense_open_field_yards' => $gameStat['defense']['openFieldYards'] ?? null,
                'defense_open_field_yards_total' => $gameStat['defense']['openFieldYardsTotal'] ?? null,

                // Standard Downs Stats
                'defense_standard_downs_ppa' => $gameStat['defense']['standardDowns']['ppa'] ?? null,
                'defense_standard_downs_success_rate' => $gameStat['defense']['standardDowns']['successRate'] ?? null,
                'defense_standard_downs_explosiveness' => $gameStat['defense']['standardDowns']['explosiveness'] ?? null,

                // Passing Downs Stats
                'defense_passing_downs_ppa' => $gameStat['defense']['passingDowns']['ppa'] ?? null,
                'defense_passing_downs_success_rate' => $gameStat['defense']['passingDowns']['successRate'] ?? null,
                'defense_passing_downs_explosiveness' => $gameStat['defense']['passingDowns']['explosiveness'] ?? null,

                // Rushing Plays
                'defense_rushing_ppa' => $gameStat['defense']['rushingPlays']['ppa'] ?? null,
                'defense_rushing_total_ppa' => $gameStat['defense']['rushingPlays']['totalPPA'] ?? null,
                'defense_rushing_success_rate' => $gameStat['defense']['rushingPlays']['successRate'] ?? null,
                'defense_rushing_explosiveness' => $gameStat['defense']['rushingPlays']['explosiveness'] ?? null,

                // Passing Plays
                'defense_passing_ppa' => $gameStat['defense']['passingPlays']['ppa'] ?? null,
                'defense_passing_total_ppa' => $gameStat['defense']['passingPlays']['totalPPA'] ?? null,
                'defense_passing_success_rate' => $gameStat['defense']['passingPlays']['successRate'] ?? null,
                'defense_passing_explosiveness' => $gameStat['defense']['passingPlays']['explosiveness'] ?? null,
            ]
        );

        Log::info('Saved Game Stat for: ' . $gameStat['team']);
    }
}
