<?php

namespace App\Services;

use App\Models\Nfl\NflTeamSchedule;
use App\Models\UserSubmission;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GameWeekService
{
    public function determineGameWeek(Request $request, $game_week = null): string
    {
        $game_week = $request->input('game_week') ?? $game_week;

        if (!$game_week) {
            foreach (config('nfl.weeks') as $weekName => $range) {
                if (Carbon::now()->between(
                    Carbon::parse($range['start']),
                    Carbon::parse($range['end'])
                )) {
                    return $weekName;
                }
            }
            return 'Week 1';
        }

        return $game_week;
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
            ->when($game_week, fn($query) => $query->where('game_week', $game_week))
            ->with(['awayTeam', 'homeTeam'])
            ->orderBy('game_date', 'asc')
            ->get();
    }

    public function getUserSubmissionsForWeek($eventIds, $userId)
    {
        return UserSubmission::where('user_id', $userId)
            ->whereIn('espn_event_id', $eventIds)
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
                'week_id' => $event->game_week,
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
        return UserSubmission::with('user')
            ->whereHas('user', function ($query) use ($team_id) {
                $query->whereHas('teams', fn($q) => $q->where('team_id', $team_id));
            })
            ->selectRaw('user_id, COUNT(CASE WHEN is_correct THEN 1 END) as correct_picks')
            ->groupBy('user_id')
            ->when($game_week, function ($query) use ($game_week) {
                $query->whereHas('event', fn($q) => $q->where('game_week', $game_week));
            })
            ->orderBy($sort, $direction)
            ->get();
    }

    public function getUserPicksForWeek($userId, $game_week, $team_id)
    {
        return UserSubmission::with(['user', 'event', 'team'])
            ->whereHas('user', function ($query) use ($team_id) {
                $query->whereHas('teams', fn($q) => $q->where('team_id', $team_id));
            })
            ->where('user_id', $userId)
            ->when($game_week, function ($query) use ($game_week) {
                $query->whereHas('event', fn($q) => $q->where('game_week', $game_week));
            })
            ->get();
    }
}