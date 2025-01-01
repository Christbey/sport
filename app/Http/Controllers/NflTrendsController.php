<?php

namespace App\Http\Controllers;

use App\Models\Nfl\NflTeamSchedule;
use App\Repositories\NflTeamScheduleRepository;
use App\Services\NflTrendsAnalyzer;
use Exception;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Log;

class NflTrendsController extends Controller
{
    private const VALID_TEAMS = [
        'ARI', 'ATL', 'BAL', 'BUF', 'CAR', 'CHI', 'CIN', 'CLE',
        'DAL', 'DEN', 'DET', 'GB', 'HOU', 'IND', 'JAX', 'KC',
        'LAC', 'LAR', 'LV', 'MIA', 'MIN', 'NE', 'NO', 'NYG',
        'NYJ', 'PHI', 'PIT', 'SEA', 'SF', 'TB', 'TEN', 'WAS'
    ];

    public function __construct(
        private readonly NflTrendsAnalyzer         $trendsAnalyzer,
        private readonly NflTeamScheduleRepository $scheduleRepository,
    )
    {
    }

    public function show(Request $request): View
    {
        if (!$request->has('team') || empty($request->input('team'))) {
            // Define all trends
            $allTrends = [
                'The Kansas City Chiefs have won 8 of their last 10 home games.',
                'The Buffalo Bills have covered the spread in 5 of their last 6 games as underdogs.',
                'The Dallas Cowboys have gone over the total in 7 of their last 9 games.',
                'The Green Bay Packers have lost 4 of their last 5 away games by 10+ points.',
                'The Philadelphia Eagles are 6-1 in their last 7 games as favorites.',
                'The Miami Dolphins have scored over 30 points in 4 of their last 5 games.',
            ];

            // Shuffle and select random trends
            $randomTrends = collect($allTrends)->shuffle()->take(4);

            return view('nfl.trends.config', [
                'randomTrends' => $randomTrends,
            ]);
        }

        $request->validate([
            'team' => 'required|string',
            'season' => 'nullable|integer',
            'games' => 'nullable|integer|min:1|max:100',
        ]);

        $team = strtoupper($request->input('team'));

        if (!in_array($team, self::VALID_TEAMS)) {
            return view('nfl.trends.config')->withErrors(['Invalid team selected']);
        }

        $season = $request->input('season');
        $games = $request->input('games', 10);

        $trends = $this->trendsAnalyzer->analyze($team, $season, $games);

        if (empty($trends)) {
            return view('nfl.trends.config')->withErrors(['No games found for ' . $team]);
        }

        return view('nfl.trends.config', [
            'selectedTeam' => $team,
            'trends' => $trends,
            'totalGames' => count($trends['general'] ?? []),
            'season' => $season,
            'games' => $games,
        ]);
    }

    public function compare(Request $request)
    {
        try {
            $upcomingGames = NflTeamSchedule::where('season_type', 'Regular Season')
                ->where('game_date', '>', now())
                ->orderBy('game_date')
                ->get()
                ->groupBy('game_week');

            $selectedWeek = $request->input('week');
            $weekGames = collect();
            $selectedGame = null;
            $comparison = null;
            $team1 = null;
            $team2 = null;

            if ($selectedWeek) {
                $gameIds = $upcomingGames->get($selectedWeek, collect())->pluck('game_id');

                if ($gameIds->isEmpty()) {
                    throw new Exception("No games found for week {$selectedWeek}");
                }

                $weekGames = $this->scheduleRepository->getSchedulesByGameIds($gameIds);
            }

            if ($request->filled('game')) {
                $gameId = $request->input('game');

                // Parse game ID format: YYYYMMDD_AWAY@HOME
                preg_match('/\d{8}_(\w+)@(\w+)/', $gameId, $matches);

                if (!isset($matches[1]) || !isset($matches[2])) {
                    throw new Exception('Invalid game ID format');
                }

                $awayTeam = $matches[1];
                $homeTeam = $matches[2];

                $comparison = $this->trendsAnalyzer->compareTeams($homeTeam, $awayTeam);

                return $request->ajax()
                    ? response()->json([
                        'status' => 'success',
                        'comparison' => $comparison,
                        'team1' => $homeTeam,
                        'team2' => $awayTeam
                    ])
                    : view('nfl.trends.comparison', [
                        'upcomingGames' => $upcomingGames,
                        'weekGames' => $weekGames,
                        'selectedWeek' => $selectedWeek,
                        'selectedGame' => $selectedGame,
                        'comparison' => $comparison,
                        'team1' => $homeTeam,
                        'team2' => $awayTeam
                    ]);
            }

        } catch (Exception $e) {
            Log::error('Comparison Error: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 422);
            }

            return back()->withErrors($e->getMessage());
        }
        return view('nfl.trends.comparison', [
            'upcomingGames' => $upcomingGames,
            'weekGames' => $weekGames,
            'selectedWeek' => $selectedWeek,
            'selectedGame' => $selectedGame,
            'comparison' => $comparison,
            'team1' => $team1,
            'team2' => $team2
        ]);
    }
}