<?php

namespace App\Http\Controllers;

use App\Models\UserSubmission;
use App\Models\NflTeamSchedule;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PickemController extends Controller
{
    /**
     * Show the team schedule for a specific week.
     */
    public function filter(Request $request)
    {
        // Get the selected week_id (game_week)
        $week_id = $request->input('week_id');

        // Fetch all unique game_week values for the dropdown
        $weeks = NflTeamSchedule::select('game_week')->distinct()
            ->where('season_type', 'Regular Season')
            ->get();

        // Fetch schedules for the selected week where the season_type is 'Regular Season'
        $schedules = NflTeamSchedule::with(['awayTeam', 'homeTeam'])
            ->when($week_id, function ($query) use ($week_id) {
                $query->where('game_week', $week_id);
            })
            ->where('season_type', 'Regular Season') // Filter only 'Regular Season' games
            ->get();

        // Pass the schedules, weeks, and week_id to the view
        return view('pickem.show', compact('schedules', 'weeks', 'week_id'));
    }



    public function showTeamSchedule($week_id = null)
    {
        // Fetch all unique game_week values only for regular season
        $weeks = NflTeamSchedule::select('game_week')->distinct()
            ->where('season_type', 'Regular Season') // Only fetch regular season weeks
            ->get();

        // Fetch schedules filtered by game_week if provided and only regular season
        $schedules = NflTeamSchedule::where('season_type', 'Regular Season')
            ->when($week_id, function ($query) use ($week_id) {
                return $query->where('game_week', $week_id);
            })
            ->with(['awayTeam', 'homeTeam'])
            ->get();

        // Return the view with schedules and weeks
        return view('pickem.show', compact('schedules', 'weeks', 'week_id'));
    }


    public function pickWinner(Request $request)
    {
        // Validate the request
        $request->validate([
            'event_id' => 'required|exists:nfl_team_schedules,espn_event_id', // Reference the schedule table
            'team_id' => 'required|exists:nfl_teams,id',
        ]);

        // Get the event and ensure it is from the regular season
        $event = NflTeamSchedule::where('espn_event_id', $request->event_id)
            ->where('season_type', 'Regular Season') // Ensure it's a regular season event
            ->firstOrFail();

        $selectedTeamId = $request->team_id;

        // Initialize isCorrect as false by default
        $isCorrect = false;

        // Check if the event is completed
        if ($event->game_status === 'Completed') {
            // Determine if the user's choice was correct
            $isCorrect = $event->home_pts > $event->away_pts
                ? $selectedTeamId == $event->home_team_id
                : $selectedTeamId == $event->away_team_id;
        }

        // Store the user's submission
        UserSubmission::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'espn_event_id' => $event->espn_event_id,  // Use espn_event_id here
            ],
            [
                'team_id' => $selectedTeamId,
                'is_correct' => $isCorrect,  // Save the correct value
                'week_id' => $event->game_week,  // Save as a string
            ]
        );

        // Redirect back to the same page with success message
        return redirect()->route('pickem.schedule', ['week_id' => $event->game_week])
            ->with('success', 'Your pick has been submitted successfully!')
            ->with('submitted_event_id', $event->espn_event_id);
    }

    /**
     * Show user submissions.
     */
    public function showSubmissions(Request $request, $weekId = null)
    {
        // Get the user's submissions for the specified week
        $userSubmissions = UserSubmission::with(['event', 'team'])
            ->where('user_id', auth()->id())
            ->where('week_id', $weekId)
            ->where('season_type', 'Regular Season')
            ->get();

        // Render submissions view
        return view('pickem.index', compact('userSubmissions', 'weekId'));
    }

    public function showLeaderboard(Request $request)
    {
        $gameWeek = $request->input('game_week');
        $userId = Auth::id(); // Get the logged-in user's ID

        // Fetch distinct game weeks from the schedules
        $games = NflTeamSchedule::select('game_week')->distinct()
            ->where('season_type', 'Regular Season')
            ->get();

        // Fetch leaderboard with correct picks, filter by game_week if provided
        $leaderboard = UserSubmission::with(['user'])
            ->selectRaw('user_id, COUNT(CASE WHEN is_correct THEN 1 END) as correct_picks')
            ->groupBy('user_id')
            ->when($gameWeek, function ($query) use ($gameWeek) {
                $query->whereHas('event', function ($q) use ($gameWeek) {
                    $q->where('game_week', $gameWeek);
                });
            })
            ->orderByDesc('correct_picks')
            ->get();

        // Fetch only the authenticated user's picks for the given game week
        $allPicks = UserSubmission::with(['user', 'event', 'team'])
            ->where('user_id', $userId)  // Filter by the logged-in user's ID
            ->when($gameWeek, function ($query) use ($gameWeek) {
                $query->whereHas('event', function ($q) use ($gameWeek) {
                    $q->where('game_week', $gameWeek);
                });
            })
            ->get();

        return view('pickem.index', compact('leaderboard', 'allPicks', 'games', 'gameWeek'));
    }

}
