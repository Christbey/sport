<?php

namespace App\Listeners;

use App\Events\GameResultsProcessed;
use App\Events\UserPicksProcessed;
use App\Models\User;
use App\Models\UserSubmission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ProcessUserPickResults implements ShouldQueue
{
    public function handle(GameResultsProcessed $event): void
    {
        $userIds = UserSubmission::whereIn('espn_event_id', collect($event->updatedGames)->pluck('espn_event_id'))
            ->select('user_id')
            ->distinct()
            ->pluck('user_id');

        foreach ($userIds as $userId) {
            $user = User::find($userId);

            // Get weekly results with all necessary relationships
            $weeklyResults = UserSubmission::with(['event.homeTeam', 'event.awayTeam', 'team'])
                ->where('user_id', $userId)
                ->where('week_id', $event->gameWeek)
                ->whereIn('espn_event_id', collect($event->updatedGames)->pluck('espn_event_id'))
                ->get()
                ->map(function ($submission) {
                    return [
                        'game' => $submission->event->short_name,
                        'team_name' => $submission->team->team_name,
                        'home_team' => $submission->event->homeTeam->team_name ?? 'Unknown Team',
                        'away_team' => $submission->event->awayTeam->team_name ?? 'Unknown Team',
                        'is_correct' => $submission->is_correct,
                    ];
                })
                ->toArray();

            // Add logging to debug the data
            Log::info('Weekly Results Data:', [
                'userId' => $userId,
                'gameWeek' => $event->gameWeek,
                'results' => $weeklyResults
            ]);

            // Calculate stats
            $weeklyStats = $this->calculateStats(
                UserSubmission::where('user_id', $userId)
                    ->where('week_id', $event->gameWeek)
                    ->get()
            );

            $overallStats = $this->calculateStats(
                UserSubmission::where('user_id', $userId)
                    ->whereNotNull('is_correct')
                    ->get()
            );

            // Dispatch event for user notifications
            UserPicksProcessed::dispatch(
                $user,
                $weeklyResults,
                $event->gameWeek,
                $weeklyStats,
                $overallStats
            );
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