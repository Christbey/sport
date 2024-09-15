<?php

namespace App\Jobs\Nfl;

use App\Models\NFLTeam;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StoreNflTeams implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $apiUrl;
    protected $apiKey;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->apiUrl = 'https://tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com/getNFLTeams';
        $this->apiKey = env('RAPIDAPI_KEY');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', 'https://tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com/getNFLTeams', [
            'headers' => [
                'x-rapidapi-host' => 'tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com',
                'x-rapidapi-key' => $this->apiKey,
            ],
        ]);

        $teams = json_decode($response->getBody(), true)['body'];

        foreach ($teams as $team) {
            NFLTeam::updateOrCreate(
                ['team_id' => $team['teamID']],
                [
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
                ]
            );
        }
    }
}
