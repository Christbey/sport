<?php

namespace App\Jobs;

use App\Models\Nfl\NflTeamSchedule;
use App\Models\User;
use App\Models\UserSubmission;
use App\Notifications\DiscordCommandCompletionNotification;
use App\Notifications\PicksResultsNotification;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\{Notification};

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
                $this->sendUserNotifications($updatedGames);
            }

            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification('', 'success'));

        } catch (Exception $e) {
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($e->getMessage(), 'error'));
        }
    }

    protected function sendUserNotifications($updatedGames)
    {
        $gameWeek = $updatedGames->first()->game_week;

        $userIds = UserSubmission::whereIn('espn_event_id', $updatedGames->pluck('espn_event_id'))
            ->select('user_id')
            ->distinct()
            ->pluck('user_id');

        foreach ($userIds as $userId) {
            $user = User::find($userId);

            // Get weekly results
            $weeklyResults = UserSubmission::with(['event', 'team'])
                ->where('user_id', $userId)
                ->where('week_id', $gameWeek)
                ->whereIn('espn_event_id', $updatedGames->pluck('espn_event_id'))
                ->get()
                ->map(function ($submission) {
                    return [
                        'game' => $submission->event->short_name,
                        'team_name' => $submission->team->team_name,
                        'is_correct' => $submission->is_correct,
                    ];
                })
                ->toArray();

            // Calculate weekly stats
            $weeklyStats = $this->calculateStats(
                UserSubmission::where('user_id', $userId)
                    ->where('week_id', $gameWeek)
                    ->get()
            );

            // Calculate overall stats
            $overallStats = $this->calculateStats(
                UserSubmission::where('user_id', $userId)
                    ->whereNotNull('is_correct')
                    ->get()
            );

            // Send notification to user
            $user->notify(new PicksResultsNotification(
                $weeklyResults,
                $gameWeek,
                $weeklyStats,
                $overallStats
            ));
        }
    }

    protected function calculateStats($submissions)
    {
        $total = $submissions->count();
        $correct = $submissions->where('is_correct', true)->count();
        $percentage = $total > 0 ? round(($correct / $total) * 100, 1) : 0;

        return [
            'total' => $total,
            'correct' => $correct,
            'percentage' => $percentage
        ];
    }
}