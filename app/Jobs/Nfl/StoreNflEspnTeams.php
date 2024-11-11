<?php

namespace App\Jobs\Nfl;

use App\Models\Nfl\NflTeam;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Log;

class StoreNflEspnTeams implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $espnApiUrl;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->espnApiUrl = 'https://site.api.espn.com/apis/site/v2/sports/football/nfl/teams';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $response = Http::get($this->espnApiUrl);

        if ($response->successful()) {
            $teams = $response->json()['sports'][0]['leagues'][0]['teams'];

            foreach ($teams as $teamData) {
                $team = $teamData['team'];

                // Try to match by espn_logo1 first, then fall back to team_abv if no match found
                $existingTeam = NflTeam::where('espn_logo1', $team['logos'][0]['href'] ?? null)
                    ->orWhere('team_abv', $team['abbreviation'])
                    ->first();

                if ($existingTeam) {
                    // Update the existing team
                    $existingTeam->update([
                        'espn_id' => $team['id'], // ESPN team ID
                        'uid' => $team['uid'], // ESPN unique ID
                        'slug' => $team['slug'], // Team slug
                        'color' => $team['color'], // Team primary color
                        'alternate_color' => $team['alternateColor'], // Team alternate color
                    ]);
                } else {
                    // Create a new team record
                    NflTeam::create([
                        'espn_id' => $team['id'], // ESPN team ID
                        'uid' => $team['uid'], // ESPN unique ID
                        'slug' => $team['slug'], // Team slug
                        'team_abv' => $team['abbreviation'], // Team abbreviation
                        'espn_logo1' => $team['logos'][0]['href'] ?? null, // ESPN logo 1 URL
                        'color' => $team['color'], // Team primary color
                        'alternate_color' => $team['alternateColor'], // Team alternate color
                    ]);
                }
            }

            Log::info('ESPN NFL teams data has been successfully updated.');
        } else {
            Log::error('Failed to fetch ESPN NFL teams data.');
        }
    }
}
