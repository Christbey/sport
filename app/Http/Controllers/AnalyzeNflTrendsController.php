<?php

namespace App\Http\Controllers;

use App\Services\NflTrendsAnalyzer;
use Exception;
use Illuminate\Http\Request;

class AnalyzeNflTrendsController extends Controller
{
    private NflTrendsAnalyzer $nflTrendsAnalyzer;

    public function __construct(NflTrendsAnalyzer $nflTrendsAnalyzer)
    {
        $this->nflTrendsAnalyzer = $nflTrendsAnalyzer;
    }

    public function filter(Request $request)
    {
        $team = $request->input('team');
        $season = $request->input('season');
        $week = $request->input('week');
        $results = [
            'trends' => [] // Default to an empty array
        ];

        try {
            if ($team) {
                $results['trends'] = $this->nflTrendsAnalyzer->analyze($team, $season, $week);
            }
        } catch (Exception $e) {
            return view('nfl-trends.filter', [
                'error' => $e->getMessage(),
                'results' => $results,
            ]);
        }
        //@dd($results);
        return view('nfl-trends.filter', compact('team', 'season', 'week', 'results'));
    }

    public function compareTeams(Request $request)
    {
        $team1 = $request->input('team1');
        $team2 = $request->input('team2');
        $results = [];

        try {
            if ($team1 && $team2) {
                $results = $this->nflTrendsAnalyzer->compareTeams($team1, $team2);
            }
        } catch (Exception $e) {
            return view('nfl-trends.compare', [
                'error' => $e->getMessage(),
                'results' => [],
            ]);
        }

        return view('nfl-trends.compare', compact('team1', 'team2', 'results'));
    }
}
