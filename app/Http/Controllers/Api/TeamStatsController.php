<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Nfl\NflTeam;
use App\Repositories\Nfl\TeamStatsRepository;
use Illuminate\Http\Request;

class TeamStatsController extends Controller
{
    const QUERIES = [
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
        'player_vs_conference' => 'Player Stats vs Conference',
        'conference_stats' => 'Conference Stats',
        'situational_performance' => 'Situational Performance',
    ];

    protected TeamStatsRepository $teamStatsRepository;

    public function __construct(TeamStatsRepository $teamStatsRepository)
    {
        $this->teamStatsRepository = $teamStatsRepository;
    }

    /**
     * Display the NFL stats page.
     */
    public function index()
    {
        return view('nfl.stats.index', [
            'queries' => self::QUERIES,
            'metadata' => [],
        ]);
    }

    /**
     * API endpoint for getting analysis data
     */
    public function getAnalysisData(Request $request, string $queryType)
    {
        $result = $this->getAnalysisResult($request, $queryType);
        return response()->json($result);
    }

    /**
     * Get analysis result based on query type
     */
    private function getAnalysisResult(Request $request, string $queryType): array
    {
        $filters = $this->getFilters($request);

        return match ($queryType) {
            'average_points' => $this->teamStatsRepository->getAveragePoints(
                $filters['teamFilter'],
                $filters['locationFilter'],
                $filters['conferenceFilter'],
                $filters['divisionFilter']
            ),
            'quarter_scoring' => $this->teamStatsRepository->getQuarterScoring(
                $filters['teamFilter'],
                $filters['locationFilter'],
                $filters['quarterFilter']
            ),
            'situational_performance' => $this->teamStatsRepository->getSituationalPerformance($filters['teamFilter']),
            'half_scoring' => $this->teamStatsRepository->getHalfScoring($filters['teamFilter']),
            'score_margins' => $this->teamStatsRepository->getScoreMargins($filters['teamFilter'], $filters['locationFilter']),
            'quarter_comebacks' => $this->teamStatsRepository->getQuarterComebacks($filters['teamFilter']),
            'scoring_streaks' => $this->teamStatsRepository->getScoringStreaks($filters['teamFilter']),
            'bestReceivers' => $this->teamStatsRepository->getBestReceivers($filters['teamFilter']),
            'bestRushers' => $this->teamStatsRepository->getBestRushers($filters['teamFilter']),
            'bestTacklers' => $this->teamStatsRepository->getBestTacklers($filters['teamFilter']),
            'big_playmakers' => $this->teamStatsRepository->getBigPlaymakers($filters['teamFilter']),
            'defensive_playmakers' => $this->teamStatsRepository->getDefensivePlaymakers($filters['teamFilter']),
            'dual_threat' => $this->teamStatsRepository->getDualThreatPlayers($filters['teamFilter']),
            'offensive_consistency' => $this->teamStatsRepository->getOffensiveConsistency($filters['teamFilter']),
            'nfl_team_stats' => $this->teamStatsRepository->getNflTeamStats($filters['teamFilter']),
            'over_under_analysis' => $this->teamStatsRepository->getOverUnderAnalysis($filters['teamFilter']),
            'team_matchup_edge' => $this->teamStatsRepository->getTeamMatchupEdge($filters['teamFilter']),
            'first_half_trends' => $this->teamStatsRepository->getFirstHalfTendencies($filters['teamFilter']),
            'team_vs_conference' => $this->teamStatsRepository->getTeamVsConference(
                $filters['teamFilter'],
                $filters['locationFilter'],
                $filters['conferenceFilter'],
                $filters['divisionFilter']
            ),
            'player_vs_conference' => $this->teamStatsRepository->getPlayerVsConference($filters['teamFilter']),
            default => ['data' => [], 'headings' => [], 'metadata' => []],
        };
    }

    private function getFilters(Request $request): array
    {
        return [
            'teamFilter' => $request->input('teamFilter', null),
            'locationFilter' => $request->input('locationFilter', null),
            'quarterFilter' => $request->input('quarterFilter', null),
            'conferenceFilter' => $request->input('conferenceFilter', null),
            'divisionFilter' => $request->input('divisionFilter', null),
        ];
    }

    /**
     * Web route for showing analysis page
     */
    public function showAnalysis(Request $request, string $queryType)
    {
        $filters = $this->getFilters($request);

        // Fetch the analysis result based on the query type and filters
        $result = $this->getAnalysisResult($request, $queryType, $filters);

        // Fetch teams, conferences, and divisions to populate filter dropdowns
        $teamsList = NflTeam::pluck('team_abv')->toArray();
        $conferencesList = ['AFC', 'NFC'];
        $divisionsList = ['North', 'South', 'East', 'West'];

        return view('nfl.stats.show', [
            'queries' => self::QUERIES, // Add this line
            'data' => $result['data'],
            'tableHeadings' => $result['headings'],
            'queryType' => $queryType,
            'metadata' => $result['metadata'] ?? [],
            'selectedTeam' => $filters['teamFilter'],
            'selectedLocation' => $filters['locationFilter'],
            'selectedQuarter' => $filters['quarterFilter'],
            'selectedConference' => $filters['conferenceFilter'],
            'selectedDivision' => $filters['divisionFilter'],
            'teamsList' => $teamsList,
            'conferencesList' => $conferencesList,
            'divisionsList' => $divisionsList,
        ]);
    }

    /**
     * Get recent games for a team.
     */
    public function getRecentGames(Request $request)
    {
        $teamFilter = $request->query('teamFilter');
        $data = $this->teamStatsRepository->getRecentGames($teamFilter);
        return response()->json($data);
    }
}
