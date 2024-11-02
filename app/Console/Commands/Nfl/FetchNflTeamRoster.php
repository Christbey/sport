<?php

namespace App\Console\Commands\Nfl;

use App\Models\Nfl\NflPlayerData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchNflTeamRoster extends Command
{
    // Command signature with optional arguments
    protected $signature = 'nfl:fetch-team-roster {teamID?} {teamAbv?}';

    // Command description
    protected $description = 'Fetch NFL team roster from the API route and display/store the response';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Get optional arguments and provide default values if not provided
        $teamID = $this->argument('teamID') ?? 6;   // Default to team ID 6 (example)
        $teamAbv = $this->argument('teamAbv') ?? 'CHI'; // Default to Chicago Bears

        // Make an HTTP request to the route you've defined
        $response = Http::get(route('nfl.teamRoster'), [
            'teamID' => $teamID,
            'teamAbv' => $teamAbv,
            'getStats' => true,
            'fantasyPoints' => true,
        ]);

        // Check for a successful response
        if ($response->successful()) {
            $data = $response->json();

            // Log the response to inspect its structure
            \Log::info('API Response:', $data);

            // Verify the structure contains 'body' and 'roster'
            if (isset($data['body']['roster']) && is_array($data['body']['roster'])) {
                $players = $data['body']['roster'];
                $this->storeNFLTeamRoster($players);
                $this->info('Team roster data has been saved successfully.');
            } else {
                $this->error('Unexpected response structure: no roster data found.');
                \Log::error('Unexpected API response structure', ['response' => $data]);
            }
        } else {
            $this->error('Failed to fetch team roster.');
        }
    }

    protected function storeNFLTeamRoster(array $players)
    {
        foreach ($players as $player) {
            // Check if the player data is in the expected format
            if (!isset($player['playerID'])) {
                \Log::warning('Player data missing playerID', ['player' => $player]);
                continue; // Skip this player if playerID is missing
            }

            // Handle "R" (rookie) in the 'exp' field
            $exp = isset($player['exp']) && $player['exp'] === 'R' ? 0 : (int)($player['exp'] ?? 0); // Set to 0 if Rookie, else cast to integer

            NflPlayerData::updateOrCreate(
                ['playerID' => $player['playerID']],
                [
                    'fantasyProsLink' => $player['fantasyProsLink'] ?? null,
                    'jerseyNum' => $player['jerseyNum'] ?? null,
                    'espnName' => $player['espnName'] ?? null,
                    'cbsLongName' => $player['cbsLongName'] ?? null,
                    'yahooLink' => $player['yahooLink'] ?? null,
                    'sleeperBotID' => $player['sleeperBotID'] ?? null,
                    'fantasyProsPlayerID' => $player['fantasyProsPlayerID'] ?? null,
                    'lastGamePlayed' => $player['lastGamePlayed'] ?? null,
                    'espnLink' => $player['espnLink'] ?? null,
                    'yahooPlayerID' => $player['yahooPlayerID'] ?? null,
                    'isFreeAgent' => $player['isFreeAgent'] === 'True',
                    'pos' => $player['pos'] ?? null,
                    'school' => $player['school'] ?? null,
                    'teamID' => $player['teamID'] ?? null,
                    'cbsShortName' => $player['cbsShortName'] ?? null,
                    'injury_return_date' => $player['injury']['injReturnDate'] ?? null,
                    'injury_description' => $player['injury']['description'] ?? null,
                    'injury_date' => $player['injury']['injDate'] ?? null,
                    'injury_designation' => $player['injury']['designation'] ?? null,
                    'rotoWirePlayerIDFull' => $player['rotoWirePlayerIDFull'] ?? null,
                    'rotoWirePlayerID' => $player['rotoWirePlayerID'] ?? null,
                    'exp' => $exp, // Store the experience value, handle 'R' for rookies
                    'height' => $player['height'] ?? null,
                    'espnHeadshot' => $player['espnHeadshot'] ?? null,
                    'fRefID' => $player['fRefID'] ?? null,
                    'weight' => $player['weight'] ?? null,
                    'team' => $player['team'] ?? null,
                    'espnIDFull' => $player['espnIDFull'] ?? null,
                    'bDay' => $player['bDay'] ?? null,
                    'age' => $player['age'] ?? null,
                    'longName' => $player['longName'] ?? null,
                ]
            );
        }
    }
}
