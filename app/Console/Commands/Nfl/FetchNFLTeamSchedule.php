<?php

namespace App\Console\Commands\Nfl;

use App\Jobs\Nfl\FetchNflEspnScheduleJob;
use App\Jobs\Nfl\StoreNflTeamSchedule;
use App\Models\NflTeam;
use App\Notifications\DiscordCommandCompletionNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class FetchNFLTeamSchedule extends Command
{
    protected $signature = 'nfl:fetch-team-schedule {season?}';

    protected $description = 'Fetch and store the NFL team schedule for all teams and a specific season, then run the ESPN schedule fetch job';

    public function handle()
    {
        try {
            // Get the season from the argument or fallback to the config value
            $season = $this->argument('season') ?? config('nfl.seasonYear');
            $seasonType = config('nfl.seasonType');

            // Determine the current week based on today’s date if not defined in the config
            $weekNumber = $this->getCurrentWeek() ?? config('nfl.weekNumber');

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

                // delay for the next job
                $delay = $delayInSeconds;
            }

            // Dispatch Job with variables from config
            FetchNflEspnScheduleJob::dispatch($season, $seasonType, $weekNumber);

            $this->info('All NFL team schedules dispatched successfully.');
            // Send success notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification('', 'success'));

        } catch (Exception $e) {
            // Send failure notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($e->getMessage(), 'error'));

        }
    }

    /**
     * Determine the current NFL week based on today's date.
     */
    private function getCurrentWeek(): ?int
    {
        $today = Carbon::today();
        $weeks = config('nfl.weeks');

        foreach ($weeks as $weekLabel => $dates) {
            $weekNumber = (int)filter_var($weekLabel, FILTER_SANITIZE_NUMBER_INT);
            $start = Carbon::parse($dates['start']);
            $end = Carbon::parse($dates['end']);

            if ($today->between($start, $end)) {
                return $weekNumber;
            }
        }

        // Return null if no matching week is found
        return null;
    }
}
