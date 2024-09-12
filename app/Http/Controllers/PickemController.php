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
    public function showTeamSchedule(Request $request, $game_week = null)
    {
        // Get game_week from the request or the route parameter
        $game_week = $request->input('game_week') ?? $game_week;

        // If no game_week is provided, calculate the current week based on the date
        if (!$game_week) {
            $today = Carbon::now();
            foreach (config('nfl.weeks') as $weekName => $range) {
                $start = Carbon::parse($range['start']);
                $end = Carbon::parse($range['end']);
                if ($today->between($start, $end)) {
                    $game_week = $weekName;
                    break;
                }
            }

            // Default to 'Week 1' if no matching week is found
            if (!$game_week) {
                $game_week = 'Week 1';
            }
        }

        // Fetch all distinct game_week values for the regular season
        $weeks = NflTeamSchedule::select('game_week')
            ->distinct()
            ->where('season_type', 'Regular Season')
            ->orderByRaw('CAST(SUBSTRING(game_week, 6) AS UNSIGNED) ASC') // Order numerically
            ->get();

        // Fetch schedules filtered by game_week for the regular season
        $schedules = NflTeamSchedule::where('season_type', 'Regular Season')
            ->when($game_week, function ($query) use ($game_week) {
                return $query->where('game_week', $game_week);
            })
            ->with(['awayTeam', 'homeTeam'])
            ->orderBy('game_date', 'asc')
            ->get();

        // Fetch the user's previous submissions for displayed events
        $userId = auth()->id();
        $userSubmissions = UserSubmission::where('user_id', $userId)
            ->whereIn('espn_event_id', $schedules->pluck('espn_event_id'))
            ->get()
            ->keyBy('espn_event_id');

        // Return the view with schedules, weeks, and the selected game_week
        return view('pickem.show', compact('schedules', 'weeks', 'game_week', 'userSubmissions'));
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
        $userId = Auth::id();

        $games = NflTeamSchedule::select('game_week')
            ->distinct()
            ->where('season_type', 'Regular Season')
            ->get();

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

        $allPicks = UserSubmission::with(['user', 'event', 'team'])
            ->where('user_id', $userId)
            ->when($gameWeek, function ($query) use ($gameWeek) {
                $query->whereHas('event', function ($q) use ($gameWeek) {
                    $q->where('game_week', $gameWeek);
                });
            })
            ->get();

        return view('pickem.index', compact('leaderboard', 'allPicks', 'games', 'gameWeek'));
    }
}
