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

class StoreNflTeams implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $apiHost;
    protected $apiKey;

    public function __construct()
    {
        $this->apiHost = config('services.rapidapi.host', 'tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com');
        $this->apiKey = config('services.rapidapi.key');
    }

    public function handle()
    {
        try {
            $teams = $this->fetchNflTeams();
            $this->storeNflTeams($teams);
        } catch (Exception $e) {
            Log::error('Failed to store NFL teams: ' . $e->getMessage());
        }
    }

    private function fetchNflTeams(): array
    {
        $response = Http::withHeaders([
            'x-rapidapi-host' => $this->apiHost,
            'x-rapidapi-key' => $this->apiKey,
        ])->get("https://{$this->apiHost}/getNFLTeams");

        if ($response->successful()) {
            return $response->json('body');
        } else {
            throw new Exception('Failed to fetch NFL teams. Status Code: ' . $response->status());
        }
    }

    private function storeNflTeams(array $teams): void
    {
        foreach ($teams as $team) {
            $this->storeNflTeam($team);
        }
    }

    private function storeNflTeam(array $team): void
    {
        NflTeam::updateOrCreate(
            ['team_id' => $team['teamID']],
            $this->prepareTeamData($team)
        );
    }

    private function prepareTeamData(array $team): array
    {
        return [
            'team_abv' => $team['teamAbv'],
            'team_city' => $team['teamCity'],
            'team_name' => $team['teamName'],
            'division' => $team['division'],
            'conference_abv' => $team['conferenceAbv'],
            'conference' => $team['conference'],
            'nfl_com_logo1' => $team['nflComLogo1'],
            'espn_logo1' => $team['espnLogo1'],
            'wins' => $team['wins'],
            'loss' => $team['loss'],
            'tie' => $team['tie'],
            'pf' => $team['pf'],
            'pa' => $team['pa'],
            'current_streak' => json_encode($team['currentStreak']),
        ];
    }
}