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
        $season = $this->argument('season');

        $this->dispatchTeamSchedules($season);
        $this->dispatchEspnScheduleJobs();

        $this->info('All jobs dispatched successfully.');
    }

    /**
     * Dispatch jobs to store NFL team schedules.
     *
     * @param string $season
     * @return void
     */
    private function dispatchTeamSchedules(string $season): void
    {
        $teams = NflTeam::all();

        foreach ($teams as $team) {
            StoreNflTeamSchedule::dispatch($team->team_abv, $season);
            $this->info("NFL team schedule for {$team->team_abv} dispatched successfully.");
        }
    }

    /**
     * Dispatch ESPN schedule fetch jobs for all season types and weeks.
     *
     * @return void
     */
    private function dispatchEspnScheduleJobs(): void
    {
        $this->info('Dispatching ESPN schedule fetch jobs for all weeks and season types...');

        foreach (range(1, 3) as $seasonType) {
            foreach (range(1, 18) as $weekNumber) {
                FetchNflEspnScheduleJob::dispatch(2024, $seasonType, $weekNumber);
                $this->info("FetchNflEspnScheduleJob dispatched for SeasonType: {$seasonType}, Week: {$weekNumber} of the 2024 season.");
            }
        }
    }
}