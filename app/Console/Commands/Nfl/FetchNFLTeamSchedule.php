<?php

namespace App\Console\Commands\Nfl;

use App\Events\Nfl\FetchNflEspnScheduleEvent;
use App\Events\Nfl\StoreNflTeamScheduleEvent;
use App\Helpers\NflCommandHelper;
use App\Models\Nfl\NflTeam;
use App\Notifications\DiscordCommandCompletionNotification;
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
            $weekNumber = config('nfl.weekNumber') ?? NflCommandHelper::getCurrentWeek();

            // Fetch all the teams from the nfl_teams table
            $teams = NflTeam::all();

            // Note: Per-instance delays are not directly supported when dispatching events.
            // Therefore, we will dispatch the events without delays, and rely on the listener's delay settings.

            // Loop through each team and dispatch the StoreNflTeamScheduleEvent
            foreach ($teams as $team) {
                // Dispatch the event
                event(new StoreNflTeamScheduleEvent($team->team_abv, $season));

                // Log the success message
                $this->info("NFL team schedule for {$team->team_abv} event dispatched successfully.");
            }

            // Dispatch the FetchNflEspnScheduleEvent
            event(new FetchNflEspnScheduleEvent($season, $seasonType, $weekNumber));

            $this->info('All NFL team schedules events dispatched successfully.');

            // Send success notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification('', 'success'));

        } catch (Exception $e) {
            // Send failure notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($e->getMessage(), 'error'));

            // Log the exception for debugging
            $this->error('An error occurred: ' . $e->getMessage());
        }
    }
}
