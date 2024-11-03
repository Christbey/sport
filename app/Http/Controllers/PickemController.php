<?php

namespace App\Http\Controllers;

use App\Mail\PicksSubmittedMail;
use App\Models\Nfl\NflTeamSchedule;
use App\Models\UserSubmission;
use Carbon\Carbon;
use DB;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mail;

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
        $gameWeek = $request->input('game_week') ?? $this->determineGameWeek($request, null);

        try {
            foreach ($eventIds as $eventId) {
                $this->processPick($eventId, $teamIds[$eventId] ?? null, $now, $userId, $request);
            }

            // Fetch the user's picks to include in the email
            $userPicks = $this->getUserPicksForEmail($userId, $eventIds);

            // Send the email to the user
            $user = Auth::user();
            Mail::to($user->email)->send(new PicksSubmittedMail($user, $userPicks, $gameWeek));

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

    private function getUserPicksForEmail($userId, $eventIds)
    {
        $picks = UserSubmission::with(['event', 'team'])
            ->where('user_id', $userId)
            ->whereIn('espn_event_id', $eventIds)
            ->get();

        $pickData = [];

        foreach ($picks as $pick) {
            $game = $pick->event->short_name ?? 'Unknown Game';
            $teamName = $pick->team->team_name ?? 'Unknown Team';

            $pickData[] = [
                'game' => $game,
                'team_name' => $teamName,
            ];
        }

        return $pickData;
    }

    public function showLeaderboard(Request $request)
    {
        $game_week = $request->input('game_week');
        $userId = Auth::id();
        $user = Auth::user();

        // Check if the user's current_team_id is in the team_user table
        $isAuthorized = DB::table('team_user')
            ->where('user_id', $userId)
            ->where('team_id', $user->current_team_id)
            ->exists();

        if (!$isAuthorized) {
            abort(403, 'Unauthorized access to this team’s leaderboard.');
        }

        $games = $this->getGameWeeks();
        $leaderboard = $this->getLeaderboard($game_week, $user->current_team_id);
        $allPicks = $this->getUserPicksForWeek($userId, $game_week, $user->current_team_id);

        if ($request->expectsJson()) {
            return response()->json(compact('leaderboard', 'allPicks', 'games', 'game_week'), 200);
        }

        return view('pickem.index', compact('leaderboard', 'allPicks', 'games', 'game_week'));
    }

    private function getLeaderboard($game_week, $team_id)
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
            ->orderByDesc('correct_picks')
            ->get();
    }

    private function getUserPicksForWeek($userId, $game_week, $team_id)
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
