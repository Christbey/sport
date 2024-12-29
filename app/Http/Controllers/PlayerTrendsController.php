<?php

namespace App\Http\Controllers;

use App\Models\Nfl\OddsApiNfl;
use App\Models\Nfl\PlayerTrend;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PlayerTrendsController extends Controller
{
    /**
     * Display a listing of player trends.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request)
    {
        $team = $request->input('team');
        $season = $request->input('season', config('nfl.seasonYear'));
        $week = $request->input('week');
        $eventId = $request->input('event_id');
        $market = $request->input('market', 'player_receptions');

        $noDataMessage = null;

        // Fetch events for dropdown
        $events = $this->getUpcomingEvents();

        // Fetch trends with filters
        $playerTrends = $this->getPlayerTrends($season, $week, $eventId, $market);

        // No data message
        if ($playerTrends->isEmpty()) {
            $noDataMessage = 'No data available for the selected market. Please check again later.';
        }

        return view('nfl.player-trends', compact(
            'playerTrends', 'events', 'team', 'season', 'week', 'eventId', 'market', 'noDataMessage'
        ));
    }

    /**
     * Fetch upcoming events.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getUpcomingEvents()
    {
        return OddsApiNfl::select(['event_id', 'datetime', 'home_team', 'away_team'])
            ->whereDate('datetime', '>=', now())
            ->orderBy('datetime')
            ->get();
    }

    /**
     * Fetch player trends with filters.
     *
     * @param string|null $season
     * @param string|null $week
     * @param string|null $eventId
     * @param string $market
     * @return Collection
     */
    protected function getPlayerTrends($season, $week, $eventId, $market)
    {
        $playerTrends = PlayerTrend::select([
            'player',
            'point',
            DB::raw('SUM(over_count) as total_over_count'),
            DB::raw('SUM(under_count) as total_under_count'),
        ])
            ->when($season, fn($query) => $query->where('season', $season))
            ->when($week && is_numeric($week), fn($query) => $query->where('week', '<', $week))
            ->when($eventId, fn($query) => $query->where('odds_api_id', $eventId))
            ->when($market, fn($query) => $query->where('market', $market))
            ->groupBy('player', 'point')
            ->orderBy('player')
            ->get();

        return $playerTrends->map(function ($trend) use ($week, $season, $market) {
            $this->calculateOverPercentage($trend);
            $this->applyRecentPerformanceAdjustment($trend, $week, $season, $market);
            $this->determineAction($trend);

            return $trend;
        });
    }

    /**
     * Calculate the over percentage.
     *
     * @param object $trend
     */
    protected function calculateOverPercentage(&$trend)
    {
        $totalAttempts = $trend->total_over_count + $trend->total_under_count;
        $trend->over_percentage = $totalAttempts > 0
            ? round(($trend->total_over_count / $totalAttempts) * 100, 2)
            : 0;

        Log::info("Over percentage for {$trend->player}: {$trend->over_percentage}%");
    }

    /**
     * Apply adjustment based on recent performance.
     *
     * @param object $trend
     * @param int|null $week
     * @param int|null $season
     * @param string $market
     */
    protected function applyRecentPerformanceAdjustment(&$trend, $week, $season, $market)
    {


        $marketConfig = config("nfl.markets.{$market}");
        $column = $marketConfig['column'] ?? null;
        $key = $marketConfig['key'] ?? null;

        if (!$column || !$key) {
            $trend->adjustment = 0;
            return;
        }

        $lastThreeStats = DB::table('nfl_player_stats')
            ->join('nfl_team_schedules', 'nfl_player_stats.game_id', '=', 'nfl_team_schedules.game_id')
            ->where('nfl_player_stats.long_name', 'like', "%{$trend->player}%")
            ->where('nfl_team_schedules.season', $season)
            ->orderByDesc('nfl_team_schedules.game_week')
            ->limit(3)
            ->pluck("nfl_player_stats.{$column}");

        Log::info("Last three stats for {$trend->player}: ", $lastThreeStats->toArray());

        $recentPerformanceCount = $lastThreeStats
            ->filter(fn($stat) => isset($stat) && $stat > $trend->point)
            ->count();

        Log::info("Recent Performance Count for {$trend->player}: {$recentPerformanceCount}");

        $adjustment = match ($recentPerformanceCount) {
            3 => 25,
            0 => -10,
            default => 0,
        };

        $trend->over_percentage += $adjustment;
        $trend->adjustment = $adjustment;

        Log::info("Adjustment for {$trend->player}: {$adjustment}");
    }

    /**
     * Determine action based on over percentage.
     *
     * @param object $trend
     */
    protected function determineAction(&$trend)
    {
        $trend->action = match (true) {
            $trend->over_percentage >= 70 => 'Bet',
            $trend->over_percentage >= 50 => 'Consider',
            default => 'Stay Away',
        };

        Log::info("Action for {$trend->player}: {$trend->action}");
    }

    /**
     * Fetch player odds using artisan command.
     *
     * @param Request $request
     * @return RedirectResponse
     */

    public function fetchPlayerOdds(Request $request)
    {
        // Check if the user is authenticated and is an admin
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }
        $eventId = $request->input('event_id');
        $market = $request->input('market', 'player_receptions');

        if (!$eventId || !array_key_exists($market, config('nfl.markets'))) {
            return redirect()->back()->with('error', 'Invalid event or market.');
        }

        try {
            Artisan::call('fetch:player-odds', [
                'odds_api_id' => $eventId,
                '--market' => $market,
            ]);

            return redirect()->back()->with('success', "Player odds fetched successfully for event ID: {$eventId} and market: {$market}");
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to fetch player odds: ' . $e->getMessage());
        }
    }
}
