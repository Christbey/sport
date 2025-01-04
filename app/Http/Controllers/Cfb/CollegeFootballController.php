<?php

namespace App\Http\Controllers\Cfb;

use App\Http\Controllers\Controller;
use App\Models\CollegeFootball\CollegeFootballElo;
use Illuminate\View\View;
use Log;

class CollegeFootballController extends Controller
{
    /**
     * Display a listing of college football teams and their ELO ratings.
     *
     * @return View
     */
    public function elo()
    {
        // Explicitly set the year to 2024
        $selectedYear = request('year', 2024);
        $selectedWeek = request('week', 1);
        $eloFilter = request('elo_filter', 'high');

        // Get available years
        $years = CollegeFootballElo::distinct('year')
            ->orderBy('year', 'desc')
            ->pluck('year');

        $query = CollegeFootballElo::query();
        $perPage = $selectedWeek ? 10 : 25;

        // Apply filters
        $query->where('year', $selectedYear)
            ->where('week', $selectedWeek);

        // Apply ELO filter
        switch ($eloFilter) {
            case 'high':
                $query->where('elo', '>=', 1600);
                break;
            case 'medium':
                $query->whereBetween('elo', [1400, 1599]);
                break;
            case 'low':
                $query->where('elo', '<', 1400);
                break;
        }

        // Always order by ELO
        $query->orderBy('elo', 'desc');

        // Diagnostic logging
        Log::info('ELO Query Debug', [
            'year' => $selectedYear,
            'week' => $selectedWeek,
            'total_records' => $query->count(),
            'query_sql' => $query->toSql(),
            'query_bindings' => $query->getBindings()
        ]);

        if ($selectedWeek > 1) {
            // Get paginated results with ELO changes
            $teams = $query->paginate($perPage)->through(function ($team) use ($selectedWeek, $selectedYear) {
                $previousWeekElo = CollegeFootballElo::where([
                    'team' => $team->team,
                    'year' => $selectedYear,
                    'week' => ($selectedWeek - 1)
                ])->value('elo');

                $team->elo_change = $previousWeekElo ? $team->elo - $previousWeekElo : null;
                return $team;
            });
        } else {
            $teams = $query->paginate($perPage);
        }

        return view('college-football.elo', [
            'teams' => $teams,
            'years' => $years,
            'selectedWeek' => $selectedWeek,
            'selectedYear' => $selectedYear,
            'eloFilter' => $eloFilter
        ]);
    }
}