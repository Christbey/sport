<?php

namespace App\Http\Controllers\Nfl;

use App\Http\Controllers\Controller;
use App\Models\Nfl\{NflSheet, NflTeam, NflTeamSchedule};
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NflSheetController extends Controller
{
    protected $weeksConfig;

    public function __construct()
    {
        $this->weeksConfig = config('nfl.weeks');
    }

    public function store(Request $request)
    {
        // Validate the form data
        $request->validate([
            'team_id' => 'required|exists:nfl_teams,id',
            'game_id' => 'required|exists:nfl_team_schedules,game_id',
            'user_inputted_notes' => 'required|string|min:1', // Added required and min length
        ], [
            'user_inputted_notes.required' => 'Notes cannot be empty.',
            'user_inputted_notes.min' => 'Notes must contain at least 1 character.'
        ]);

        try {
            DB::beginTransaction();

            // Create a new NflSheet entry
            NflSheet::create([
                'team_id' => $request->team_id,
                'game_id' => $request->game_id,
                'user_id' => auth()->id(),
                'user_inputted_notes' => trim($request->user_inputted_notes), // Added trim to remove whitespace
            ]);

            DB::commit();

            return redirect()
                ->route('nfl.detail', [
                    'team_id' => $request->team_id,
                    'game_id' => $request->game_id
                ])
                ->with('success', 'Data saved successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to save notes. Please try again.');
        }
    }

    public function index(Request $request)
    {
        try {
            $data = $this->prepareIndexData($request);
            return view('nfl.detail', $data);
        } catch (Exception $e) {
            return back()->with('error', 'Failed to load data. Please try again.');
        }
    }

    protected function prepareIndexData(Request $request)
    {
        $teams = NflTeam::all();
        $selectedTeamId = $request->input('team_id');
        $selectedGameId = $request->input('game_id');
        $currentWeek = $this->getCurrentWeek();

        $games = $this->getTeamGames($selectedTeamId, $currentWeek);

        // Set default game if none selected
        if ($games->isNotEmpty() && !$selectedGameId) {
            $selectedGameId = $games->first()->game_id;
        }

        $nflSheets = $this->getRecentNflSheets($selectedTeamId);

        return compact('nflSheets', 'teams', 'selectedTeamId', 'games', 'selectedGameId');
    }

    protected function getCurrentWeek(): ?string
    {
        $currentDate = Carbon::now()->format('Y-m-d');

        foreach ($this->weeksConfig as $week => $dates) {
            if ($currentDate >= $dates['start'] && $currentDate <= $dates['end']) {
                return $week;
            }
        }

        return null;
    }

    protected function getTeamGames($teamId, $currentWeek)
    {
        if (!$teamId || !$currentWeek) {
            return collect();
        }

        return NflTeamSchedule::query()
            ->where(function ($query) use ($teamId) {
                $query->where('home_team_id', $teamId)
                    ->orWhere('away_team_id', $teamId);
            })
            // If you want to filter by week dates, uncomment this:
            // ->whereBetween('game_date', [
            //     $this->weeksConfig[$currentWeek]['start'],
            //     $this->weeksConfig[$currentWeek]['end']
            // ])
            ->get();
    }

    protected function getRecentNflSheets($teamId)
    {
        return NflSheet::query()
            ->with(['game', 'user'])
            ->when($teamId, function ($query, $teamId) {
                return $query->where('team_id', $teamId);
            })
            ->latest()
            ->take(5)
            ->get();
    }
}