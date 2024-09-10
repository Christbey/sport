<?php

namespace App\Console\Commands;

use App\Jobs\Nfl\StoreNflTeamSchedule;
use App\Jobs\FetchNflEspnScheduleJob;
use App\Models\NflTeam;
use Illuminate\Console\Command;

class FetchNFLTeamSchedule extends Command
{
    protected $signature = 'nfl:fetch-team-schedule {season}';

    protected $description = 'Fetch and store the NFL team schedule for all teams and a specific season, then run the ESPN schedule fetch job for all weeks and season types';

    public function handle()
    {
        // Get the season from the argument
        $season = $this->argument('season');

        // Fetch all the teams from the nfl_teams table
        $teams = NflTeam::all();

        // Loop through each team and dispatch the StoreNflTeamSchedule job
        foreach ($teams as $team) {
            StoreNflTeamSchedule::dispatch($team->team_abv, $season);
            $this->info("NFL team schedule for {$team->team_abv} dispatched successfully.");
        }

        // Now loop through each season type (1, 2, 3) and each week (1 to 18)
        $this->info('Dispatching ESPN schedule fetch jobs for all weeks and season types...');

        for ($seasonType = 2; $seasonType <= 2; $seasonType++) {
            for ($weekNumber = 1; $weekNumber <= 1; $weekNumber++) {
                FetchNflEspnScheduleJob::dispatch(2024, $seasonType, $weekNumber);
                $this->info("FetchNflEspnScheduleJob dispatched for SeasonType: {$seasonType}, Week: {$weekNumber} of the 2024 season.");
            }
        }

        $this->info('All ESPN schedule fetch jobs dispatched successfully.');
    }
}
