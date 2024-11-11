<?php

namespace App\Services;

use App\Models\Nfl\NflTeamSchedule;
use App\Models\User;
use App\Models\UserSubmission;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameWeekService
{
    public function determineGameWeek(Request $request, $game_week = null): int
    {
        $game_week = $request->input('game_week') ?? $game_week;

        if (!$game_week) {
            foreach (config('nfl.weeks') as $weekNumber => $range) {
                if (Carbon::now()->between(
                    Carbon::parse($range['start']),
                    Carbon::parse($range['end'])
                )) {
                    return (int)$weekNumber;
                }
            }
            return 1; // Default to Week 1
        }

        // If $game_week is a string like 'Week 1', extract the number
        if (is_string($game_week)) {
            $game_week = (int)str_replace('Week ', '', $game_week);
        }

        return (int)$game_week;
    }

    public function getGameWeeks()
    {
        return NflTeamSchedule::select('game_week')
            ->distinct()
            ->where('season_type', 'Regular Season')
            ->orderByRaw('CAST(SUBSTRING(game_week, 6) AS UNSIGNED) ASC')
            ->get();
    }
}

class PickemService
{
    public function getSchedulesForWeek($game_week)
    {
        return NflTeamSchedule::where('season_type', 'Regular Season')
            ->when($game_week, function ($query) use ($game_week) {
                $query->where(DB::raw('CAST(SUBSTRING(game_week, 6) AS UNSIGNED)'), $game_week);
            })
            ->with(['awayTeam', 'homeTeam'])
            ->orderBy('game_date', 'asc')
            ->get();
    }


    public function getUserSubmissionsForWeek($eventIds, $userId, $game_week = null)
    {
        return UserSubmission::where('user_id', $userId)
            ->whereIn('espn_event_id', $eventIds)
            ->when($game_week, function ($query) use ($game_week) {
                $query->whereHas('event', function ($q) use ($game_week) {
                    $q->where(DB::raw('CAST(SUBSTRING(game_week, 6) AS UNSIGNED)'), $game_week);
                });
            })
            ->get()
            ->keyBy('espn_event_id');
    }

    public function processUserPicks(array $eventIds, array $teamIds, int $userId, Carbon $now): void
    {
        foreach ($eventIds as $eventId) {
            $this->processSinglePick($eventId, $teamIds[$eventId] ?? null, $userId, $now);
        }
    }

    protected function processSinglePick($eventId, $selectedTeamId, $userId, Carbon $now): void
    {
        if (!$selectedTeamId) {
            return;
        }

        $event = NflTeamSchedule::where('espn_event_id', $eventId)
            ->where('season_type', 'Regular Season')
            ->firstOrFail();

        if ($this->isGameLocked($event, $now)) {
            return;
        }

        UserSubmission::updateOrCreate(
            ['user_id' => $userId, 'espn_event_id' => $eventId],
            [
                'team_id' => $selectedTeamId,
                'is_correct' => $this->isPickCorrect($event, $selectedTeamId),
                'week_id' => (int)str_replace('Week ', '', $event->game_week),
                'created_at' => $now,
                'updated_at' => $now
            ]
        );
    }

    protected function isGameLocked(NflTeamSchedule $event, Carbon $now): bool
    {
        $gameTime = Carbon::createFromTimestamp($event->game_time_epoch, 'America/Chicago');
        return $now->diffInMinutes($gameTime, false) <= 30;
    }

    protected function isPickCorrect($event, $selectedTeamId): bool
    {
        if ($event->game_status !== 'Completed') {
            return false;
        }

        return $event->home_pts > $event->away_pts
            ? $selectedTeamId == $event->home_team_id
            : $selectedTeamId == $event->away_team_id;
    }

    public function getUserPicksForEmail($userId, $eventIds): array
    {
        $picks = UserSubmission::with(['event', 'team'])
            ->where('user_id', $userId)
            ->whereIn('espn_event_id', $eventIds)
            ->get();

        return $picks->map(fn($pick) => [
            'game' => $pick->event->short_name ?? 'Unknown Game',
            'team_name' => $pick->team->team_name ?? 'Unknown Team',
        ])->toArray();
    }

    public function generateResponse(Request $request, string $message, int $status)
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message], $status)
            : redirect()->back()->with(
                $status === 200 ? 'success' : 'error',
                $message
            );
    }
}

class LeaderboardService
{
    public function getLeaderboard($game_week, $team_id, $sort = 'correct_picks', $direction = 'desc')
    {
        // Calculate the 3-week period based on the provided game week
        $period = max(0, floor(($game_week - 1) / 3));
        $period_start_week = $period * 3 + 1;
        $period_end_week = $period_start_week + 2;

        $users = User::withCount([
            // Correct picks for the specified game week
            'submissions as correct_picks' => function ($query) use ($game_week) {
                $query->where('is_correct', true)
                    ->whereHas('event', function ($q) use ($game_week) {
                        $q->where(DB::raw('CAST(SUBSTRING(game_week, 6) AS UNSIGNED)'), $game_week);
                    });
            },
            // Total correct picks across all weeks
            'submissions as total_points' => function ($query) {
                $query->where('is_correct', true);
            },
            // Correct picks within the 3-week period
            'submissions as period_points' => function ($query) use ($period_start_week, $period_end_week) {
                $query->where('is_correct', true)
                    ->whereHas('event', function ($q) use ($period_start_week, $period_end_week) {
                        $q->whereBetween(DB::raw('CAST(SUBSTRING(game_week, 6) AS UNSIGNED)'), [$period_start_week, $period_end_week]);
                    });
            },
        ])
            // Filter users by team
            ->whereHas('teams', function ($q) use ($team_id) {
                $q->where('team_id', $team_id);
            })
            // Order the results
            ->orderBy($sort, $direction)
            ->get();

        return $users;
    }

    public function getUserPicksForWeek($userId, $game_week, $team_id)
    {
        return UserSubmission::with(['user', 'event', 'team'])
            ->whereHas('user', function ($query) use ($team_id) {
                $query->whereHas('teams', fn($q) => $q->where('team_id', $team_id));
            })
            ->where('user_id', $userId)
            ->when($game_week, function ($query) use ($game_week) {
                $query->whereHas('event', function ($q) use ($game_week) {
                    $q->where(DB::raw('CAST(SUBSTRING(game_week, 6) AS UNSIGNED)'), $game_week);
                });
            })
            ->get();
    }

}