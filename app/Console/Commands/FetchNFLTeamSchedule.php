<?php

namespace App\Console\Commands;

use App\Jobs\Nfl\StoreNflTeamSchedule;
use App\Jobs\FetchNflEspnScheduleJob;
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

        // Loop through each team and dispatch the StoreNflTeamSchedule job
        foreach ($teams as $team) {
            StoreNflTeamSchedule::dispatch($team->team_abv, $season);
            sleep(2);
            $this->info("NFL team schedule for {$team->team_abv} dispatched successfully.");
        }

        // After dispatching all team schedules, dispatch the FetchNflEspnScheduleJob
       FetchNflEspnScheduleJob::dispatch(2024, 2, 1); // Example: Year=2024, SeasonType=2 (Regular Season), WeekNumber=1

        $this->info('All NFL team schedules dispatched successfully.');
       // $this->info('FetchNflEspnScheduleJob dispatched for Week 1 of the 2024 season.');
    }
}
