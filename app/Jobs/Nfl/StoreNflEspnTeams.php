<?php

namespace App\Jobs\Nfl;

use App\Models\Nfl\NflTeam;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StoreNflEspnTeams implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $espnApiUrl;

    public function __construct()
    {
        $this->espnApiUrl = 'https://site.api.espn.com/apis/site/v2/sports/football/nfl/teams';
    }

    public function handle()
    {
        try {
            $teams = $this->fetchEspnNflTeams();
            $this->storeEspnNflTeams($teams);
            Log::info('ESPN NFL teams data has been successfully updated.');
        } catch (Exception $e) {
            Log::error('Failed to fetch or store ESPN NFL teams data: ' . $e->getMessage());
        }
    }

    private function fetchEspnNflTeams(): array
    {
        $response = Http::get($this->espnApiUrl);

        if ($response->successful()) {
            return $response->json('sports.0.leagues.0.teams');
        } else {
            throw new Exception('Failed to fetch ESPN NFL teams. Status Code: ' . $response->status());
        }
    }

    private function storeEspnNflTeams(array $teams): void
    {
        foreach ($teams as $teamData) {
            $this->storeEspnNflTeam($teamData['team']);
        }
    }

    private function storeEspnNflTeam(array $teamData): void
    {
        $existingTeam = $this->findExistingTeam($teamData);

        if ($existingTeam) {
            $this->updateExistingTeam($existingTeam, $teamData);
        } else {
            $this->createNewTeam($teamData);
        }
    }

    private function findExistingTeam(array $teamData): ?NflTeam
    {
        $logoUrl = $teamData['logos'][0]['href'] ?? null;
        $abbreviation = $teamData['abbreviation'];

        return NflTeam::where('espn_logo1', $logoUrl)
            ->orWhere('team_abv', $abbreviation)
            ->first();
    }

    private function updateExistingTeam(NflTeam $team, array $teamData): void
    {
        $team->update([
            'espn_id' => $teamData['id'],
            'uid' => $teamData['uid'],
            'slug' => $teamData['slug'],
            'color' => $teamData['color'],
            'alternate_color' => $teamData['alternateColor'],
        ]);
    }

    private function createNewTeam(array $teamData): void
    {
        NflTeam::create([
            'espn_id' => $teamData['id'],
            'uid' => $teamData['uid'],
            'slug' => $teamData['slug'],
            'team_abv' => $teamData['abbreviation'],
            'espn_logo1' => $teamData['logos'][0]['href'] ?? null,
            'color' => $teamData['color'],
            'alternate_color' => $teamData['alternateColor'],
        ]);
    }
}