<?php

namespace App\Http\Controllers;

use App\Models\UserSubmission;
use App\Models\NflTeamSchedule;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PickemController extends Controller
{

    public function filter(Request $request)
    {
        // Get the selected week_id (game_week)
        $week_id = $request->input('week_id');

        // Fetch all unique game_week values for the dropdown
        $weeks = NflTeamSchedule::select('game_week')->distinct()
            ->where('season_type', 'Regular Season')
            ->orderBy('game_week', 'asc') // Order the weeks
            ->get();

        // Fetch schedules for the selected week where the season_type is 'Regular Season'
        $schedules = NflTeamSchedule::with(['awayTeam', 'homeTeam'])
            ->when($week_id, function ($query) use ($week_id) {
                $query->where('game_week', $week_id);
            })
            ->where('season_type', 'Regular Season') // Filter only 'Regular Season' games
            ->orderBy('game_date', 'asc') // Order by game_date to show the games from earliest to latest
            ->get();

        // Fetch the user's previous submissions for the displayed events
        $userId = auth()->id();
        $userSubmissions = UserSubmission::where('user_id', $userId)
            ->whereIn('espn_event_id', $schedules->pluck('espn_event_id'))
            ->get()
            ->keyBy('espn_event_id'); // Key submissions by event ID for easy lookup

        // Pass the schedules, weeks, user submissions, and week_id to the view
        return view('pickem.show', compact('schedules', 'weeks', 'week_id', 'userSubmissions'));
    }

    public function showTeamSchedule($week_id = null)
    {
        // If no week_id is provided, calculate the current week based on the date
        if (!$week_id) {
            $today = Carbon::now();

            // Loop through the weeks from the config to find the current week
            foreach (config('nfl.weeks') as $weekName => $range) {
                $start = Carbon::parse($range['start']);
                $end = Carbon::parse($range['end']);

                if ($today->between($start, $end)) {
                    $week_id = $weekName; // Set week_id to "Week X"
                    break;
                }
            }

            // If no matching week is found, default to 'Week 1'
            if (!$week_id) {
                $week_id = 'Week 1';
            }
        }

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

        // Return the view with schedules, weeks, and the selected week_id
        return view('pickem.show', compact('schedules', 'weeks', 'week_id'));
    }
    public function pickWinner(Request $request)
    {
        // Validate the request to ensure the picks are valid
        $request->validate([
            'event_ids' => 'required|array',
            'event_ids.*' => 'exists:nfl_team_schedules,espn_event_id', // Validate each event ID
            'team_ids' => 'required|array',
            'team_ids.*' => 'exists:nfl_teams,id', // Validate each team pick
        ]);

        $userId = auth()->id(); // Get the authenticated user's ID
        $teamIds = $request->input('team_ids'); // Retrieve the selected teams
        $eventIds = $request->input('event_ids'); // Retrieve the event IDs
        $now = Carbon::now(); // Current time

        try {
            // Loop through each event and update the user's pick
            foreach ($eventIds as $eventId) {
                $selectedTeamId = $teamIds[$eventId] ?? null; // Get the selected team for this event

                if ($selectedTeamId) {
                    // Find the event for this submission
                    $event = NflTeamSchedule::where('espn_event_id', $eventId)
                        ->where('season_type', 'Regular Season')
                        ->firstOrFail();

                    // Convert the game_time_epoch to a Carbon instance
                    $gameTime = Carbon::createFromTimestamp($event->game_time_epoch, 'America/Chicago');

                    // Check if the current time is within 30 minutes of the game time
                    if ($now->diffInMinutes($gameTime, false) <= 30) {
                        return redirect()->back()->with('error', "The game for event {$event->short_name} is locked. You can no longer submit picks.");
                    }

                    // Initialize isCorrect as false by default
                    $isCorrect = false;

                    // Check if the event is completed and determine if the user's choice was correct
                    if ($event->game_status === 'Completed') {
                        $isCorrect = $event->home_pts > $event->away_pts
                            ? $selectedTeamId == $event->home_team_id
                            : $selectedTeamId == $event->away_team_id;
                    }

                    // Use updateOrCreate to update the user's submission or create a new one
                    UserSubmission::updateOrCreate(
                        [
                            'user_id' => $userId,
                            'espn_event_id' => $eventId, // Use espn_event_id to find existing submission
                        ],
                        [
                            'team_id' => $selectedTeamId,
                            'is_correct' => $isCorrect,
                            'week_id' => $event->game_week,
                            'created_at' => $now,   // Store the submission time in CST
                            'updated_at' => $now,   // Store the updated time in CST
                        ]
                    );
                }
            }

            // Redirect back with a success message after storing all picks
            return redirect()->back()->with('success', 'Your picks have been submitted successfully!');
        } catch (QueryException $e) {
            // Catch any database query errors and flash an error message
            return redirect()->back()->with('error', 'There was an issue submitting your picks. Please try again.');
        }
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
