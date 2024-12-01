<?php

namespace App\Console\Commands\Nfl;

use App\Models\Nfl\NflPlayerData;
use App\Models\Nfl\NflTeam;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchNflTeamRoster extends Command
{
    protected $signature = 'fetch:nfl-team-roster {teamID?} {teamAbv?}';

    protected $description = 'Fetch NFL team roster data for a specific team or all teams.';

    public function handle()
    {
        $teamID = $this->argument('teamID');
        $teamAbv = $this->argument('teamAbv');

        if ($teamID && $teamAbv) {
            $this->fetchRoster($teamID, $teamAbv);
        } else {
            $teams = NflTeam::all(); // Fetch all teams from the nfl_teams table
            foreach ($teams as $team) {
                $this->fetchRoster($team->id, $team->team_abv); // Adjust field names as needed
            }
        }

        $this->info('NFL team roster fetch completed.');
    }

    protected function fetchRoster($teamID, $teamAbv)
    {
        try {
            $client = new Client();
            $response = $client->request('GET', 'https://tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com/getNFLTeamRoster', [
                'query' => [
                    'teamID' => $teamID,
                    'teamAbv' => $teamAbv,
                    'getStats' => 'true',
                    'fantasyPoints' => 'true',
                ],
                'headers' => [
                    'x-rapidapi-host' => 'tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com',
                    'x-rapidapi-key' => config('services.rapidapi.key') // Replace with your API key
                ],
            ]);
            $data = json_decode($response->getBody(), true);

            if (isset($data['body']['roster']) && is_array($data['body']['roster'])) {
                foreach ($data['body']['roster'] as $player) {
                    try {
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
                                'isFreeAgent' => strtolower($player['isFreeAgent']) === 'true' ? 1 : 0,
                                'pos' => $player['pos'] ?? null,
                                'school' => $player['school'] ?? null,
                                'teamID' => $teamID,
                                'cbsShortName' => $player['cbsShortName'] ?? null,
                                'injury_return_date' => $player['injury']['injReturnDate'] ?? null,
                                'injury_description' => $player['injury']['description'] ?? null,
                                'injury_date' => $player['injury']['injDate'] ?? null,
                                'injury_designation' => $player['injury']['designation'] ?? null,
                                'rotoWirePlayerIDFull' => $player['rotoWirePlayerIDFull'] ?? null,
                                'rotoWirePlayerID' => $player['rotoWirePlayerID'] ?? null,
                                'exp' => is_numeric($player['exp']) ? (int)$player['exp'] : ($player['exp'] === 'R' ? 0 : null),
                                'height' => $player['height'] ?? null,
                                'espnHeadshot' => $player['espnHeadshot'] ?? null,
                                'fRefID' => $player['fRefID'] ?? null,
                                'weight' => is_numeric($player['weight']) ? (int)$player['weight'] : null,
                                'team' => $player['team'] ?? null,
                                'espnIDFull' => $player['espnIDFull'] ?? null,
                                'bDay' => $player['bDay'] ?? null,
                                'age' => is_numeric($player['age']) ? (int)$player['age'] : null,
                                'longName' => $player['longName'] ?? null,
                            ]
                        );
                    } catch (Exception $e) {
                        Log::error('Failed to save player data for playerID: ' . ($player['playerID'] ?? 'unknown') . '. Error: ' . $e->getMessage());
                        $this->error('Failed to save player data for playerID: ' . ($player['playerID'] ?? 'unknown') . '. Error: ' . $e->getMessage());
                    }
                }
                $this->info("Roster fetched for team: $teamAbv");
            } else {
                $this->warn("No roster data found for team: $teamAbv");
            }

        } catch (Exception $e) {
            Log::error('Error fetching NFL team roster: ' . $e->getMessage());
            $this->error('Error fetching NFL team roster: ' . $e->getMessage());
        }
    }
}
