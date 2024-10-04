<?php

namespace App\Http\Controllers;

use App\Models\Nfl\NflTeamSchedule;
use App\Models\UserSubmission;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;

class PickemController extends Controller
{
    public function showTeamSchedule(Request $request, $game_week = null)
    {
        $game_week = $this->determineGameWeek($request, $game_week);
        $weeks = $this->getGameWeeks();
        $schedules = $this->getSchedulesForWeek($game_week);
        $userSubmissions = $this->getUserSubmissionsForWeek($schedules->pluck('espn_event_id'));

        if ($request->expectsJson()) {
            return response()->json(compact('weeks', 'schedules', 'userSubmissions', 'game_week'), 200);
        }

        return view('pickem.show', compact('schedules', 'weeks', 'game_week', 'userSubmissions'));
    }

    private function determineGameWeek(Request $request, $game_week)
    {
        $game_week = $request->input('game_week') ?? $game_week;

        if (!$game_week) {
            $today = Carbon::now();
            foreach (config('nfl.weeks') as $weekName => $range) {
                if (Carbon::now()->between(Carbon::parse($range['start']), Carbon::parse($range['end']))) {
                    return $weekName;
                }
            }
            return 'Week 1';
        }

        return $game_week;
    }

    private function getGameWeeks()
    {
        return NflTeamSchedule::select('game_week')
            ->distinct()
            ->where('season_type', 'Regular Season')
            ->orderByRaw('CAST(SUBSTRING(game_week, 6) AS UNSIGNED) ASC')
            ->get();
    }

    // Helper Methods

    private function getSchedulesForWeek($game_week)
    {
        return NflTeamSchedule::where('season_type', 'Regular Season')
            ->when($game_week, function ($query) use ($game_week) {
                $query->where('game_week', $game_week);
            })
            ->with(['awayTeam', 'homeTeam'])
            ->orderBy('game_date', 'asc')
            ->get();
    }

    private function getUserSubmissionsForWeek($eventIds)
    {
        return UserSubmission::where('user_id', Auth::id())
            ->whereIn('espn_event_id', $eventIds)
            ->get()
            ->keyBy('espn_event_id');
    }

    public function pickWinner(Request $request)
    {
        $this->validatePickRequest($request);

        $userId = Auth::id();
        $now = Carbon::now();
        $eventIds = $request->input('event_ids');
        $teamIds = $request->input('team_ids');

        try {
            foreach ($eventIds as $eventId) {
                $this->processPick($eventId, $teamIds[$eventId] ?? null, $now, $userId, $request);
            }

            return $this->pickResponse($request, 'Your picks have been submitted successfully!', 200);
        } catch (QueryException $e) {
            return $this->pickResponse($request, 'There was an issue submitting your picks. Please try again.', 500);
        }
    }

    private function validatePickRequest($request)
    {
        $request->validate([
            'event_ids' => 'required|array',
            'event_ids.*' => 'exists:nfl_team_schedules,espn_event_id',
            'team_ids' => 'required|array',
            'team_ids.*' => 'exists:nfl_teams,id',
        ]);
    }

    private function processPick($eventId, $selectedTeamId, $now, $userId, $request)
    {
        if (!$selectedTeamId) {
            return;
        }

        $event = NflTeamSchedule::where('espn_event_id', $eventId)
            ->where('season_type', 'Regular Season')
            ->firstOrFail();

        $gameTime = Carbon::createFromTimestamp($event->game_time_epoch, 'America/Chicago');

        if ($now->diffInMinutes($gameTime, false) <= 30) {
            $message = "The game for event {$event->short_name} is locked. You can no longer submit picks.";
            $this->pickResponse($request, $message, 403);
        }

        $isCorrect = $this->isPickCorrect($event, $selectedTeamId);

        UserSubmission::updateOrCreate(
            ['user_id' => $userId, 'espn_event_id' => $eventId],
            ['team_id' => $selectedTeamId, 'is_correct' => $isCorrect, 'week_id' => $event->game_week, 'created_at' => $now, 'updated_at' => $now]
        );
    }

    private function pickResponse($request, $message, $status)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return redirect()->back()->with($status === 200 ? 'success' : 'error', $message);
    }

    private function isPickCorrect($event, $selectedTeamId)
    {
        if ($event->game_status === 'Completed') {
            return $event->home_pts > $event->away_pts
                ? $selectedTeamId == $event->home_team_id
                : $selectedTeamId == $event->away_team_id;
        }

        return false;
    }

    public function showLeaderboard(Request $request)
    {
        $game_week = $request->input('game_week');
        $userId = Auth::id();

        $games = $this->getGameWeeks();
        $leaderboard = $this->getLeaderboard($game_week);
        $allPicks = $this->getUserPicksForWeek($userId, $game_week);

        if ($request->expectsJson()) {
            return response()->json(compact('leaderboard', 'allPicks', 'games', 'game_week'), 200);
        }

        return view('pickem.index', compact('leaderboard', 'allPicks', 'games', 'game_week'));
    }

    private function getLeaderboard($game_week)
    {
        return UserSubmission::with('user')
            ->selectRaw('user_id, COUNT(CASE WHEN is_correct THEN 1 END) as correct_picks')
            ->groupBy('user_id')
            ->when($game_week, function ($query) use ($game_week) {
                $query->whereHas('event', fn($q) => $q->where('game_week', $game_week));
            })
            ->orderByDesc('correct_picks')
            ->get();
    }

    private function getUserPicksForWeek($userId, $game_week)
    {
        return UserSubmission::with(['user', 'event', 'team'])
            ->where('user_id', $userId)
            ->when($game_week, function ($query) use ($game_week) {
                $query->whereHas('event', fn($q) => $q->where('game_week', $game_week));
            })
            ->get();
    }
}
