<?php

namespace App\Jobs;

use App\Events\GameResultsProcessed;
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

    public function __construct($eventId = null)
    {
        $this->eventId = $eventId;
    }

    public function handle()
    {
        try {
            $query = NflTeamSchedule::where('game_status', 'Completed');

            if ($this->eventId) {
                $query->where('espn_event_id', $this->eventId);
            }

            $updatedGames = collect();

            $query->each(function ($nflTeamSchedule) use ($updatedGames) {
                $winningTeamId = $nflTeamSchedule->home_pts > $nflTeamSchedule->away_pts
                    ? $nflTeamSchedule->home_team_id
                    : $nflTeamSchedule->away_team_id;

                UserSubmission::where('espn_event_id', $nflTeamSchedule->espn_event_id)
                    ->each(function ($submission) use ($winningTeamId) {
                        $submission->is_correct = $submission->team_id == $winningTeamId;
                        $submission->save();
                    });

                $updatedGames->push($nflTeamSchedule);
            });

            if ($updatedGames->isNotEmpty()) {
                GameResultsProcessed::dispatch(
                    $updatedGames->toArray(),
                    $updatedGames->first()->game_week
                );
            }

            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification('', 'success'));

        } catch (Exception $e) {
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($e->getMessage(), 'error'));
        }
    }
}