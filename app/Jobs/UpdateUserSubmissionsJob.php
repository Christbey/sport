<?php

namespace App\Jobs;

use App\Models\Nfl\NflTeamSchedule;
use App\Models\UserSubmission;
use App\Notifications\DiscordCommandCompletionNotification;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class UpdateUserSubmissionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $eventId;

    /**
     * Create a new job instance.
     *
     * @param int|null $eventId
     */
    public function __construct($eventId = null)
    {
        $this->eventId = $eventId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // If event_id is passed, find the specific event, otherwise update all completed games
            $query = NflTeamSchedule::where('game_status', 'Completed');

            if ($this->eventId) {
                $query->where('espn_event_id', $this->eventId);
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
            // Send success notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification('', 'success'));

        } catch (Exception $e) {
            // Send failure notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($e->getMessage(), 'error'));

        }
    }
}
