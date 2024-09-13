<?php

namespace App\Console\Commands\Nfl;

use App\Jobs\FetchNflEspnScheduleJob;
use App\Jobs\Nfl\StoreNflTeamSchedule;
use App\Models\NflTeam;
use Illuminate\Console\Command;

class FetchNFLTeamSchedule extends Command
{
    protected $signature = 'nfl:fetch-team-schedule {season}';

    protected $description = 'Fetch and store the NFL team schedule for all teams and a specific season, then run the ESPN schedule fetch job';

    public function handle()
    {
        // Get the season from the argument
        $season = $this->argument('season');

        // Fetch all the teams from the nfl_teams table
        $teams = NflTeam::all();

        // Define a consistent delay between each job dispatch
        $delayInSeconds = 5;
        $delay = 0; // Start with zero delay

        // Loop through each team and dispatch the StoreNflTeamSchedule job with a consistent delay
        foreach ($teams as $team) {
            // Dispatch the job with the current delay
            StoreNflTeamSchedule::dispatch($team->team_abv, $season)->delay(now()->addSeconds($delay));

            // Log the success message
            $this->info("NFL team schedule for {$team->team_abv} dispatched successfully with a delay of {$delay} seconds.");

            // Increment the delay for the next job
            $delay = $delayInSeconds;
        }

        // Dispatch the FetchNflEspnScheduleJob without delay (or you can add a delay if needed)
        FetchNflEspnScheduleJob::dispatch(2024, 2, 1);

        $this->info('All NFL team schedules dispatched successfully.');
    }
}
