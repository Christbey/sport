<?php

namespace App\Observers;

use App\Models\Nfl\NflTeamSchedule;
use App\Models\UserSubmission;

class NflTeamScheduleObserver
{
    /**
     * Handle the NflTeamSchedule "updated" event.
     *
     * @param  \App\Models\Nfl\NflTeamSchedule  $nflTeamSchedule
     * @return void
     */
    public function updated(NflTeamSchedule $nflTeamSchedule)
    {
        // Only update user submissions if the game is marked as "Completed"
        if ($nflTeamSchedule->game_status === 'Completed') {

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
        }
    }
}