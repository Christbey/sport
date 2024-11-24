<?php

namespace App\Http\Controllers\Nfl;

use App\Http\Controllers\Controller;
use App\Services\Nfl\TeamStatsService;
use Illuminate\Http\Request;

class TeamStatsController extends Controller
{
    protected TeamStatsService $statsService;

    public function __construct(TeamStatsService $statsService)
    {
        $this->statsService = $statsService;
    }

    public function index()
    {
        $queries = [
            'average_points' => 'Average Points by Quarter',
            'quarter_scoring' => 'Quarter-by-Quarter Analysis',
            'half_scoring' => 'Half-by-Half Scoring Analysis',
            'score_margins' => 'Score Margins Analysis',
            'quarter_comebacks' => 'Comeback Analysis',
            'scoring_streaks' => 'Scoring Streaks Analysis',
            'bestReceivers' => 'Best Receivers',
            'bestRushers' => 'Best Rushers',
            'bestTacklers' => 'Best Tacklers',
            'big_playmakers' => 'Big Play Analysis',
            'defensive_playmakers' => 'Defensive Playmaker Analysis',
            'dual_threat' => 'Dual-Threat Player Analysis',
            'offensive_consistency' => 'Most Consistent Offensive Players',
            'nfl_team_stats' => 'NFL Team Stats',
            'team_analytics' => 'Team Analytics',
            'over_under_analysis' => 'Over/Under Betting Analysis',
            'team_matchup_edge' => 'Team Matchup Edge Analysis',
            'first_half_trends' => 'First Half Betting Trends',
            'team_vs_conference' => 'Team Performance vs Conference',
            'team_vs_division' => 'Team Performance vs Division',
            'player_vs_conference' => 'Player Stats vs Conference',
            'player_vs_division' => 'Player Stats vs Division',
            'conference_stats' => 'Conference Stats',
            'division_stats' => 'Division Stats'
        ];

        return view('nfl.stats.index', compact('queries'));
    }

    public function getStats(Request $request)
    {
        $queryType = $request->input('query');
        $teamFilter = $request->input('team');

        $result = $this->statsService->getStatsByType($queryType, $teamFilter);

        return view('nfl.stats.show')
            ->with('data', $result['data'])
            ->with('tableHeadings', $result['headings'])
            ->with('players', $result['players'] ?? null)
            ->with('query', $queryType)
            ->with('team', $teamFilter);
    }
}