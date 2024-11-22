<?php

namespace App\Console\Commands\Nfl;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Assuming you have a NflTeam model

class FetchNflTeamRoster extends Command
{
    private const NFL_TEAMS = [
        ['id' => 1, 'abbreviation' => 'ARI'],
        ['id' => 2, 'abbreviation' => 'ATL'],
        ['id' => 3, 'abbreviation' => 'BAL'],
        ['id' => 4, 'abbreviation' => 'BUF'],
        ['id' => 5, 'abbreviation' => 'CAR'],
        ['id' => 6, 'abbreviation' => 'CHI'],
        ['id' => 7, 'abbreviation' => 'CIN'],
        ['id' => 8, 'abbreviation' => 'CLE'],
        ['id' => 9, 'abbreviation' => 'DAL'],
        ['id' => 10, 'abbreviation' => 'DEN'],
        ['id' => 11, 'abbreviation' => 'DET'],
        ['id' => 12, 'abbreviation' => 'GB'],
        ['id' => 13, 'abbreviation' => 'HOU'],
        ['id' => 14, 'abbreviation' => 'IND'],
        ['id' => 15, 'abbreviation' => 'JAX'],
        ['id' => 16, 'abbreviation' => 'KC'],
        ['id' => 17, 'abbreviation' => 'LAC'],
        ['id' => 18, 'abbreviation' => 'LAR'],
        ['id' => 19, 'abbreviation' => 'LV'],
        ['id' => 20, 'abbreviation' => 'MIA'],
        ['id' => 21, 'abbreviation' => 'MIN'],
        ['id' => 22, 'abbreviation' => 'NE'],
        ['id' => 23, 'abbreviation' => 'NO'],
        ['id' => 24, 'abbreviation' => 'NYG'],
        ['id' => 25, 'abbreviation' => 'NYJ'],
        ['id' => 26, 'abbreviation' => 'PHI'],
        ['id' => 27, 'abbreviation' => 'PIT'],
        ['id' => 28, 'abbreviation' => 'SEA'],
        ['id' => 29, 'abbreviation' => 'SF'],
        ['id' => 30, 'abbreviation' => 'TB'],
        ['id' => 31, 'abbreviation' => 'TEN'],
        ['id' => 32, 'abbreviation' => 'WSH'],
    ]; // Added sleep option
    protected $signature = 'nfl:fetch-team-roster {--team=} {--sleep=2}';

    // NFL Teams mapping (if you don't have a teams table)
    protected $description = 'Fetch NFL team rosters from the API route and store the response';

    public function handle(): int
    {
        $specificTeam = $this->option('team');
        $sleepSeconds = (int)$this->option('sleep');
        $teams = $this->getTeamsToProcess($specificTeam);

        $this->withProgressBar($teams, function ($team) use ($sleepSeconds) {
            $this->processTeam($team['id'], $team['abbreviation']);

            if ($sleepSeconds > 0) {
                sleep($sleepSeconds); // Avoid rate limiting
            }
        });

        $this->newLine(2);
        $this->info('All team rosters have been processed successfully.');

        return 0;
    }

    private function getTeamsToProcess(?string $specificTeam): array
    {
        if ($specificTeam) {
            return collect(self::NFL_TEAMS)
                ->filter(fn($team) => $team['abbreviation'] === strtoupper($specificTeam))
                ->values()
                ->all();
        }

        return self::NFL_TEAMS;
    }

    private function processTeam(int $teamId, string $teamAbv): void
    {
        try {
            $players = $this->fetchTeamRoster($teamId, $teamAbv);

            if ($players) {
                $this->storeTeamRoster($players);
                Log::info("Successfully processed roster for team: $teamAbv");
            } else {
                Log::error("Failed to fetch roster for team: $teamAbv");
            }
        } catch (Exception $e) {
            Log::error("Error processing team $teamAbv: " . $e->getMessage());
        }
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

            if (isset($data['body']['roster']) && is_array($data['body']['roster'])) {
                return $data['body']['roster'];
            }

            Log::error('Unexpected API response structure', [
                'team' => $teamAbv,
                'response' => $data
            ]);
        } else {
            Log::error('Failed to fetch team roster.', [
                'team' => $teamAbv,
                'status' => $response->status()
            ]);
        }

        return null;
    }

    // Rest of your existing methods remain the same...
}