<?php

namespace App\Http\Controllers\Nfl;

use App\Http\Controllers\Controller;
use App\Models\Nfl\NflSheet;
use App\Models\Nfl\NflTeam;
use App\Models\Nfl\NflTeamSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NflSheetController extends Controller
{
    public function store(Request $request)
    {
        // Validate the form data
        $request->validate([
            'team_id' => 'required|exists:nfl_teams,id',
            //'game_id' => 'required|exists:nfl_team_schedule,game_id',
            'user_inputted_notes' => 'nullable|string',
        ]);

        // Create a new NflSheet entry
        NflSheet::create([
            'team_id' => $request->team_id,
            'game_id' => $request->game_id,
            'user_id' => auth()->id(),
            'user_inputted_notes' => $request->user_inputted_notes,
        ]);

        // Fetch data again to refresh the page with updated records
        return $this->index($request)->with('success', 'Data saved successfully.');
    }

    public function index(Request $request)
    {
        // Fetch all NFL teams for the filter
        $teams = NflTeam::all();

        // Fetch the selected team and game
        $selectedTeamId = $request->input('team_id');
        $selectedGameId = $request->input('game_id');

        // Get current date
        $currentDate = Carbon::now()->format('Y-m-d');

        // Get NFL weeks from config
        $weeks = config('nfl.weeks');

        // Find the current week based on the current date
        $currentWeek = null;
        foreach ($weeks as $week => $dates) {
            if ($currentDate >= $dates['start'] && $currentDate <= $dates['end']) {
                $currentWeek = $week;
                break;
            }
        }

        // Fetch games where the selected team is either the home or away team for the current week
        $games = [];
        if ($selectedTeamId && $currentWeek) {
            $games = NflTeamSchedule::where(function ($query) use ($selectedTeamId) {
                $query->where('home_team_id', $selectedTeamId)
                    ->orWhere('away_team_id', $selectedTeamId);
            })
                // ->whereBetween('game_date', [$weeks[$currentWeek]['start'], $weeks[$currentWeek]['end']])
                ->get();

            // If a game exists in the current week, set the first game as the default selected game
            if ($games->isNotEmpty()) {
                $selectedGameId = $selectedGameId ?: $games->first()->id;
            }
        }

        // Fetch all records, or filter by team and game if selected
        $nflSheets = NflSheet::when($selectedTeamId, function ($query) use ($selectedTeamId) {
            return $query->where('team_id', $selectedTeamId);
        })->when($selectedGameId, function ($query) use ($selectedGameId) {
            return $query->where('game_id', $selectedGameId);
        })->get();

        return view('nfl.detail', compact('nflSheets', 'teams', 'selectedTeamId', 'games', 'selectedGameId'));
    }
}
