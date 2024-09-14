<?php

namespace App\Console\Commands;

use App\Models\Nfl\NflTeamSchedule;
use App\Models\UserSubmission;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class UpdateUserSubmissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nfl:update-submissions {event_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update user submissions based on NFL team schedule when a game is marked as completed';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Optionally accept an event ID as a parameter to update specific event
        $eventId = $this->argument('event_id');

        // If event_id is passed, find the specific event, otherwise update all completed games
        $query = NflTeamSchedule::where('game_status', 'Completed');

        if ($eventId) {
            $query->where('espn_event_id', $eventId);
        }

        $query->each(function ($nflTeamSchedule) {
            // Determine the team with the most points
            $winningTeamId = $nflTeamSchedule->home_pts > $nflTeamSchedule->away_pts
                ? $nflTeamSchedule->home_team_id
                : $nflTeamSchedule->away_team_id;

            // Update the user submissions for this event
            UserSubmission::where('espn_event_id', $nflTeamSchedule->espn_event_id)
                ->each(function ($submission) use ($winningTeamId) {
                    // Update the is_correct flag based on whether the user's pick matches the winning team
                    $submission->is_correct = $submission->team_id == $winningTeamId;
                    $submission->save();
                });
        });

        $this->info('User submissions have been updated successfully.');

        return CommandAlias::SUCCESS;
    }
}
