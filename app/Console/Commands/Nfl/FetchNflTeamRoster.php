<?php

namespace App\Console\Commands\Nfl;

use App\Models\Nfl\NflPlayerData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchNflTeamRoster extends Command
{
    protected $signature = 'nfl:fetch-team-roster {teamID?} {teamAbv?}';
    protected $description = 'Fetch NFL team roster from the API route and store the response';

    public function handle(): int
    {
        $teamId = $this->getTeamId();
        $teamAbv = $this->getTeamAbv();

        $players = $this->fetchTeamRoster($teamId, $teamAbv);

        if ($players) {
            $this->storeTeamRoster($players);
            $this->info('Team roster data has been saved successfully.');
        } else {
            $this->error('Failed to fetch team roster.');
        }

        return 0;
    }

    private function getTeamId(): int
    {
        return (int)($this->argument('teamID') ?? 5);
    }

    private function getTeamAbv(): string
    {
        return $this->argument('teamAbv') ?? 'BAL';
    }

    private function fetchTeamRoster(int $teamId, string $teamAbv): ?array
    {
        $response = Http::get(route('nfl.teamRoster'), [
            'teamID' => $teamId,
            'teamAbv' => $teamAbv,
            'getStats' => true,
            'fantasyPoints' => true,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            Log::info('API Response:', $data);

            if (isset($data['body']['roster']) && is_array($data['body']['roster'])) {
                return $data['body']['roster'];
            } else {
                $this->error('Unexpected response structure: no roster data found.');
                Log::error('Unexpected API response structure', ['response' => $data]);
            }
        } else {
            Log::error('Failed to fetch team roster.', ['status' => $response->status()]);
        }

        return null;
    }

    private function storeTeamRoster(array $players): void
    {
        foreach ($players as $player) {
            if (isset($player['playerID'])) {
                $this->storePlayer($player);
            } else {
                Log::warning('Player data missing playerID', ['player' => $player]);
            }
        }
    }

    private function storePlayer(array $player): void
    {
        NflPlayerData::updateOrCreate(
            ['playerID' => $player['playerID']],
            $this->preparePlayerData($player)
        );
    }

    private function preparePlayerData(array $player): array
    {
        return [
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
            'exp' => $this->getPlayerExperience($player),
            'height' => $player['height'] ?? null,
            'espnHeadshot' => $player['espnHeadshot'] ?? null,
            'fRefID' => $player['fRefID'] ?? null,
            'weight' => $player['weight'] ?? null,
            'team' => $player['team'] ?? null,
            'espnIDFull' => $player['espnIDFull'] ?? null,
            'bDay' => $player['bDay'] ?? null,
            'age' => $player['age'] ?? null,
            'longName' => $player['longName'] ?? null,
        ];
    }

    private function getPlayerExperience(array $player): int
    {
        $exp = $player['exp'] ?? null;
        return $exp === 'R' ? 0 : (int)$exp;
    }
}