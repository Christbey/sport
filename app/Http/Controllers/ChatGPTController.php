<?php

namespace App\Http\Controllers;

use App\Helpers\OpenAI;
use App\Jobs\ProcessChatMessage;
use App\Models\Conversation;
use App\Repositories\Nfl\NflBettingOddsRepository;
use App\Repositories\Nfl\NflBoxScoreRepository;
use App\Repositories\Nfl\NflEloPredictionRepository;
use App\Repositories\Nfl\NflPlayerDataRepository;
use App\Repositories\Nfl\NflPlayerStatRepository;
use App\Repositories\Nfl\NflTeamStatRepository;
use App\Repositories\Nfl\TeamStatsRepository;
use App\Repositories\NflTeamScheduleRepository;
use App\Services\OpenAIChatService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Log;

class ChatGPTController extends Controller
{
    protected OpenAIChatService $chatService;
    protected NflEloPredictionRepository $repository;
    protected TeamStatsRepository $teamStatsRepository;
    protected NflBettingOddsRepository $bettingOddsRepository;
    private NflPlayerDataRepository $playerDataRepository;

    private NflBoxScoreRepository $boxScoreRepository;
    private NflPlayerStatRepository $playerStatRepository;
    private NflTeamScheduleRepository $scheduleRepository;
    private NflTeamStatRepository $nflTeamStatsRepository;

    public function __construct(OpenAIChatService $chatService, NflEloPredictionRepository $repository, NflBoxScoreRepository $boxScoreRepository
    )
    {
        $this->chatService = $chatService;
        $this->repository = $repository;
        $this->teamStatsRepository = new TeamStatsRepository();
        $this->playerDataRepository = new NflPlayerDataRepository();
        $this->bettingOddsRepository = new NflBettingOddsRepository();
        $this->scheduleRepository = new NflTeamScheduleRepository();
        $this->nflTeamStatsRepository = new NflTeamStatRepository();
        $this->playerStatRepository = new NflPlayerStatRepository();
        $this->boxScoreRepository = $boxScoreRepository;


    }

    /**
     * Show the chat view.
     */
    public function showChat()
    {
        $userId = auth()->id();
        $conversations = Conversation::where('user_id', $userId)
            ->orderBy('created_at', 'asc') // Ensure messages are in chronological order
            ->get();

        return view('openai.index', compact('conversations'));
    }

    /**
     * Handle chat messages and OpenAI function calling.
     */

    public function ask(Request $request)
    {
        // Validate input
        $request->validate(['question' => 'required|string|max:500']);

        $userMessage = $request->input('question');
        $userId = $request->user()->id;

        try {
            // Dispatch the job to process the chat message
            ProcessChatMessage::dispatch($userId, $userMessage);

            // Return a quick response indicating the request is being processed
            return response()->json(['status' => 'processing', 'input' => $userMessage]);
        } catch (Exception $e) {
            Log::error('Error dispatching ProcessChatMessage job:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'An error occurred while processing your request.'], 500);
        }
    }

    public function clearConversations()
    {
        session()->forget('conversations');
        return response()->json(['status' => 'success']);
    }

    /**
     * Handle OpenAI response processing, including recursive function calls.
     */

    public function invokeFunction(string $functionName, array $arguments)
    {
        switch ($functionName) {

            case 'get_predictions_by_week':
                return $this->repository->getPredictions($arguments['week']);
            case 'find_prediction_by_game_id':
                return $this->repository->findByGameId($arguments['game_id']);
            case 'get_predictions_by_team':
                return $this->repository->findByTeam(
                    $arguments['team_abv'] ?? null,
                    $arguments['start_date'] ?? null,
                    $arguments['end_date'] ?? null,
                    $arguments['opponent'] ?? null,
                    $arguments['week'] ?? null
                );

            case 'get_quarterly_points_analysis':
                $teams = $arguments['teams'] ?? null;
                $season = $arguments['season'] ?? now()->year; // Default to current year if season is missing
                $conferenceFilter = $arguments['conferenceFilter'] ?? null;
                $divisionFilter = $arguments['divisionFilter'] ?? null;
                $returnType = $arguments['returnType'] ?? 'both';

                if (empty($teams)) {
                    throw new Exception('Teams parameter is required for quarterly points analysis.');
                }

                $result = $this->boxScoreRepository->getQuarterlyPointsAnalysis($teams, $season);

                // Log the result for debugging
                Log::info('Response from getQuarterlyPointsAnalysis:', ['result' => $result]);

                // Post-process the result based on `returnType`
                switch ($returnType) {
                    case 'team_stats':
                        return [
                            'team_quarterly_stats' => $result['team_quarterly_stats']
                        ];
                    case 'comparison':
                        return [
                            'team_comparison' => $result['team_comparison']
                        ];
                    case 'both':
                    default:
                        return $result;
                }

            case 'analyze_team_quarterly_performance':
                $response = $this->boxScoreRepository->analyzeTeamQuarterlyPerformance(
                    $arguments['teamAbv'] ?? null,
                    $arguments['season'] ?? now()->year,
                    $arguments['locationFilter'] ?? null,
                    $arguments['performanceMetrics'] ?? ['points'],
                    $arguments['aggregationType'] ?? 'detailed'
                );

                if (isset($response['error'])) {
                    Log::warning('Error in analyzeTeamQuarterlyPerformance:', ['error' => $response['error']]);
                    return ['message' => $response['error']];
                }

                Log::info('Valid response from analyzeTeamQuarterlyPerformance:', ['response' => $response]);

                return $response;


            case 'get_schedule':
                $scheduleResult = $this->scheduleRepository->getSchedule(
                    $arguments['teamId'] ?? null,
                    $arguments['teamFilter'] ?? null,
                    $arguments['startDate'] ?? null,
                    $arguments['endDate'] ?? null,
                    $arguments['conferenceFilter'] ?? null,
                    $arguments['season'] ?? 2024,
                    $arguments['week'] ?? null
                );

                if (empty($scheduleResult)) {
                    return [
                        'success' => false,
                        'message' => 'No schedule data found for the specified criteria.'
                    ];
                }

                $formattedSchedule = $this->formatSchedule($scheduleResult);

                return [
                    'success' => true,
                    'schedule' => $formattedSchedule
                ];

            case 'get_recent_games':
                return $this->teamStatsRepository->getRecentGames();
            case 'get_average_points':
                return $this->teamStatsRepository->getAveragePoints(
                    $arguments['teamFilter'] ?? null,
                    $arguments['locationFilter'] ?? null,
                    $arguments['conferenceFilter'] ?? null,
                    $arguments['divisionFilter'] ?? null
                );


            case 'get_nfl_team_stats':
                return $this->handleGetNflTeamStats($arguments);

            case 'compare_teams_stats':
                return $this->handleCompareTeamsStats($arguments);

            case 'get_top_teams_by_stat':
                return $this->handleGetTopTeamsByStat($arguments);

            case 'get_league_averages':
                return $this->handleGetLeagueAverages($arguments);

            case 'calculate_team_scores':
                return $this->teamStatsRepository->calculateTeamScores(
                    $arguments['gameIds'] ?? [],                  // Array of game IDs (optional)
                    $arguments['teamAbv1'] ?? null,              // First team abbreviation (optional)
                    $arguments['teamAbv2'] ?? null,              // Second team abbreviation (optional)
                    $arguments['week'] ?? null,                  // Week number (optional)
                    $arguments['locationFilter'] ?? null         // Location filter (optional)
                );

            // NFL best receivers
            case 'get_best_receivers':
                $playerFilter = $arguments['playerFilter'] ?? null;
                $teamFilter = $arguments['teamFilter'] ?? null;
                $week = $arguments['week'] ?? null;
                $startWeek = $arguments['start_week'] ?? null;
                $endWeek = $arguments['end_week'] ?? null;
                $yardThreshold = $arguments['yardThreshold'] ?? 50; // Default to 50 if null

                Log::info('get_best_receivers called with arguments:', [
                    'playerFilter' => $playerFilter,
                    'teamFilter' => $teamFilter,
                    'week' => $week,
                    'startWeek' => $startWeek,
                    'endWeek' => $endWeek,
                    'yardThreshold' => $yardThreshold,
                ]);

                return $this->teamStatsRepository->getBestReceivers(
                    teamFilter: $teamFilter,
                    week: $week,
                    startWeek: $startWeek,
                    endWeek: $endWeek,
                    playerFilter: $playerFilter,
                    yardThreshold: $yardThreshold,
                    season: $arguments['season'] ?? 2024
                );

            // NFL best rushers
            case 'get_best_rushers':
                $playerFilter = $arguments['playerFilter'] ?? null;
                $teamFilter = $arguments['teamFilter'] ?? null;
                $week = $arguments['week'] ?? null;
                $startWeek = $arguments['start_week'] ?? null;
                $endWeek = $arguments['end_week'] ?? null;
                $yardThreshold = $arguments['yardThreshold'] ?? 50; // Default to 50 if null
                Log::info('get_best_receivers called with arguments:', [
                    'playerFilter' => $playerFilter,
                    'teamFilter' => $teamFilter,
                    'week' => $week,
                    'startWeek' => $startWeek,
                    'endWeek' => $endWeek,
                    'yardThreshold' => $yardThreshold,
                ]);
                return $this->teamStatsRepository->getBestRushers(
                    teamFilter: $teamFilter,
                    week: $week,
                    startWeek: $startWeek,
                    endWeek: $endWeek,
                    playerFilter: $playerFilter,
                    yardThreshold: $yardThreshold,
                    season: $arguments['season'] ?? 2024
                );

            // NFL best tacklers
            case 'get_best_tacklers':
                return $this->teamStatsRepository->getBestTacklers(
                    teamFilter: $arguments['teamFilter'] ?? null,
                    week: $arguments['week'] ?? null,
                    startWeek: $arguments['start_week'] ?? null,
                    endWeek: $arguments['end_week'] ?? null,
                    playerFilter: $arguments['playerFilter'] ?? null,
                    tackleThreshold: $arguments['tackleThreshold'] ?? null,
                    season: $arguments['season'] ?? 2024
                );


            case 'get_big_playmakers':
                return $this->teamStatsRepository->getBigPlaymakers($arguments['teamFilter'] ?? null);

            // NFL situational performance
            case 'get_situational_performance':
                return $this->teamStatsRepository->getSituationalPerformance(
                    $arguments['teamFilter'] ?? null,
                    $arguments['locationFilter'] ?? null,
                    $arguments['againstConference'] ?? null
                );

            // NFL team matchup edge
            case 'get_team_matchup_edge':
                return $this->teamStatsRepository->getTeamMatchupEdge(
                    $arguments['teamFilter'] ?? null,
                    $arguments['teamAbv1'] ?? null,
                    $arguments['teamAbv2'] ?? null,
                    $arguments['week'] ?? null,
                    $arguments['locationFilter'] ?? null
                );

            // NFL first half tendencies
            case 'get_first_half_tendencies':
                return $this->teamStatsRepository->getFirstHalfTendencies(
                    $arguments['teamFilter'] ?? null,
                    $arguments['againstConference'] ?? null,
                    $arguments['locationFilter'] ?? null
                );

            // NFL player vs conference stats
            case 'get_player_vs_conference_stats':
                return $this->teamStatsRepository->getPlayerVsConference(
                    $arguments['teamFilter'] ?? null,
                    $arguments['playerFilter'] ?? null,
                    $arguments['conferenceFilter'] ?? null
                );

            // NFL player by age range
            case 'find_players_by_age_range':
                return $this->playerDataRepository->findByAgeRange(
                    $arguments['minAge'] ?? null,
                    $arguments['maxAge'] ?? null,
                    $arguments['teamFilter'] ?? null
                );

            // NFL player by experience
            case 'find_players_by_experience':
                $years = $arguments['years'] ?? null;
                $teamFilter = $arguments['teamFilter'] ?? null;

                if (is_null($years)) {
                    return response()->json(['error' => 'Years parameter is required.'], 400);
                }

                return $this->playerDataRepository->findByExperience($years, $teamFilter);


// NFL players by injury
            case 'get_team_injuries':
                if (isset($arguments['teamFilter'])) {
                    return $this->playerDataRepository->getTeamInjuries($arguments['teamFilter']);
                }
                return response()->json(['error' => 'Missing required parameter: teamFilter'], 400);

// NFL player by position
            case 'find_players_by_position':
                if (isset($arguments['position'])) {
                    return $this->playerDataRepository->findByPosition($arguments['position']);
                }
                return response()->json(['error' => 'Missing required parameter: position'], 400);

// NFL player by school
            case 'find_players_by_school':
                if (isset($arguments['school'])) {
                    return $this->playerDataRepository->findBySchool($arguments['school']);
                }
                return response()->json(['error' => 'Missing required parameter: school'], 400);

// NFL players by team
            case 'find_players_by_team':
                if (isset($arguments['teamFilter'])) {
                    return $this->playerDataRepository->findPlayersByTeam($arguments['teamFilter']);
                }
                return response()->json(['error' => 'Missing required parameter: teamFilter'], 400);

// NFL player by espnName
            case 'find_player_by_espn_name':
                if (isset($arguments['espnName'])) {
                    $player = $this->playerDataRepository->findByEspnName($arguments['espnName']);
                    return $player ? $player : response()->json(['error' => 'Player not found'], 404);
                }
                return response()->json(['error' => 'Missing required parameter: espnName'], 400);

// NFL free agents
            case 'get_free_agents':
                return $this->playerDataRepository->getFreeAgents();

            case 'get_odds_by_event_ids':
                return $this->bettingOddsRepository->getOddsByEventIds($arguments['eventIds']);
            case 'get_odds_by_team':
                return $this->bettingOddsRepository->getOddsByTeam($arguments['teamFilter']);
            case 'get_odds_by_week':
                return $this->bettingOddsRepository->getOddsByWeek($arguments['week']);
            case 'get_odds_by_date_range':
                return $this->bettingOddsRepository->getOddsByDateRange($arguments['startDate'], $arguments['endDate']);
            case 'get_odds_by_moneyline':
                return $this->bettingOddsRepository->getOddsByMoneyline($arguments['moneyline']);


            // NFL betting odds by team and week
            case 'get_odds_by_team_and_week':
                $odds = $this->bettingOddsRepository->getOddsByTeamAndWeek(
                    $arguments['teamFilter'],
                    $arguments['week']
                );

                return response()->json([
                    'odds' => $odds->toArray()
                ]);

            // Add these cases inside your switch statement in invokeFunction method

            case 'get_receiving_stats':
                return $this->playerStatRepository->getReceivingStats(
                    $arguments['long_name'] ?? null,
                    $arguments['team_abv'] ?? null
                );

            case 'get_rushing_stats':
                return $this->playerStatRepository->getRushingStats(
                    $arguments['long_name'] ?? null,
                    $arguments['team_abv'] ?? null
                );

            case 'get_defense_stats':
                return $this->playerStatRepository->getDefenseStats(
                    $arguments['player_id'] ?? null,
                    $arguments['team_abv'] ?? null
                );

            case 'get_kicking_stats':
                return $this->playerStatRepository->getKickingStats(
                    $arguments['player_id'] ?? null,
                    $arguments['team_abv'] ?? null
                );

            case 'get_punting_stats':
                return $this->playerStatRepository->getPuntingStats(
                    $arguments['player_id'] ?? null,
                    $arguments['team_abv'] ?? null
                );

            case 'get_first_downs_average':
                return $this->nflTeamStatsRepository->getFirstDownsAverage(
                    $arguments['teamFilters'],
                    $arguments['week'] ?? null,
                    $arguments['season'] ?? null
                );

            case 'get_team_stat_average':
                return $this->nflTeamStatsRepository->getTeamStatAverage(
                    $arguments['teamFilters'], // Array of team abbreviations
                    $arguments['statColumn'], // Statistic column name
                    $arguments['week'] ?? null,
                    $arguments['season'] ?? null
                );


            case 'get_half_scoring':
                return $this->teamStatsRepository->getHalfScoring(
                    $arguments['teamFilter'] ?? null,
                    $arguments['locationFilter'] ?? null,
                    $arguments['conferenceFilter'] ?? null,
                    $arguments['divisionFilter'] ?? null
                );

            // NFL schedule by team
            case 'get_schedule_by_team':
                $teamId = $arguments['teamId'] ?? null;
                $teamFilter = $arguments['teamFilter'] ?? null;
                return $this->scheduleRepository->getScheduleByTeam($teamId, $teamFilter);

            case 'check_team_prediction':
                return $this->handleTeamPredictions([
                    'team_abv' => $arguments['team_abv'],
                    'week' => $arguments['week'] ?? OpenAI::getCurrentNFLWeek(),
                    'include_stats' => $arguments['include_stats'] ?? false,
                    'include_factors' => $arguments['include_factors'] ?? false
                ]);

            default:
                throw new Exception("Unknown function: $functionName");
        }
    }

    /**
     * Format the schedule data into a readable string.
     *
     * @param array $schedule
     * @return string
     */
    private function formatSchedule(array $schedule): string
    {
        if (empty($schedule)) {
            return 'No games found for the specified criteria.';
        }

        $formatted = "Schedule for the specified criteria:\n\n";

        foreach ($schedule as $game) {
            $date = Carbon::parse($game['game_date'])->toFormattedDateString();
            $time = $game['time'] ?? 'TBD';
            $status = $game['status'] ?? 'Scheduled';
            $result = $game['result'] ?? '';

            $formatted .= "{$game['home_team']} @ {$game['away_team']}\n";
            $formatted .= "Date: {$date}\n";
            $formatted .= "Time: {$time}\n";
            $formatted .= "Status: {$status}\n";
            if ($result) {
                $formatted .= "Result: {$result}\n";
            }
            $formatted .= "\n";
        }

        return $formatted;
    }

    private function handleGetNflTeamStats(array $arguments)
    {
        $teamAbv = $arguments['team_abv'] ?? null;
        $week = $arguments['week'] ?? null;

        if (!$teamAbv || !$week) {
            return [
                'success' => false,
                'message' => 'Missing required parameters: team_abv and week.'
            ];
        }

        try {
            $result = $this->teamStatsRepository->getNflTeamStats($teamAbv, $week);

            // If the repository method doesn't return an array with 'success', wrap the result
            if (!isset($result['success'])) {
                return [
                    'success' => true,
                    'data' => $result
                ];
            }

            return $result;
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle the compare_teams_stats function call.
     *
     * @param array $arguments
     * @return JsonResponse
     */
    private function handleCompareTeamsStats(array $arguments)
    {
        $teamAbvs = $arguments['team_abvs'] ?? [];
        $statColumn = $arguments['stat_column'] ?? null;
        $week = $arguments['week'] ?? null;

        if (empty($teamAbvs) || !$statColumn || !$week) {
            return response()->json(['message' => 'Missing required parameters: team_abvs, stat_column, and week.'], 400);
        }

        $result = $this->nflTeamStatsRepository->compareTeamsStats($teamAbvs, $statColumn, $week);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 404);
        }

        return response()->json(['comparison' => $result['data']], 200);
    }

    /**
     * Handle the get_league_averages function call.
     *
     * @param array $arguments
     * @return JsonResponse
     */

    /**
     * Handle the get_top_teams_by_stat function call.
     *
     * @param array $arguments
     * @return JsonResponse
     */
    private function handleGetTopTeamsByStat(array $arguments)
    {
        $statColumn = $arguments['stat_column'] ?? null;
        $week = $arguments['week'] ?? null;
        $limit = $arguments['limit'] ?? 5;

        if (!$statColumn) {
            return response()->json(['message' => 'Missing required parameter: stat_column.'], 400);
        }

        $result = $this->nflTeamStatsRepository->getTopTeamsByStat($statColumn, $week, $limit);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 404);
        }

        return response()->json(['top_teams' => $result['data']], 200);
    }

    /**
     * Handle the get_league_averages function call.
     *
     * @param array $arguments
     * @return JsonResponse
     */
    private function handleGetLeagueAverages(array $arguments)
    {
        $week = $arguments['week'] ?? null;

        if (!$week) {
            return response()->json(['message' => 'Missing required parameter: week.'], 400);
        }

        $result = $this->nflTeamStatsRepository->getLeagueAverages($week);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 404);
        }

        // Format the league averages into a readable string
        $averages = $result['data'];
        $formattedContent = "League-Wide Average Statistics for Week {$week}:\n";
        foreach ($averages as $key => $value) {
            // Convert snake_case to Title Case
            $formattedKey = ucwords(str_replace('_', ' ', $key));
            // Format values appropriately
            if (is_float($value)) {
                $formattedValue = number_format($value, 2);
            } elseif (is_string($value)) {
                $formattedValue = $value;
            } else {
                $formattedValue = $value;
            }
            $formattedContent .= "{$formattedKey}: {$formattedValue}\n";
        }

        return response()->json(['content' => $formattedContent], 200);
    }

    private function handleTeamPredictions(array $arguments): array
    {
        return $this->repository->getTeamPrediction(
            teamAbv: $arguments['team_abv'],
            week: $arguments['week'] ?? null,
            includeStats: $arguments['include_stats'] ?? false,
            includeFactors: $arguments['include_factors'] ?? false
        );
    }

    private function resolveTimeFrameWeek(?string $timeFrame, int $currentWeek): ?int
    {
        if (!$timeFrame) {
            return null; // No time frame provided
        }

        switch (strtolower($timeFrame)) {
            case 'last week':
                return max($currentWeek - 1, 1); // Ensure week does not go below 1
            case 'this week':
                return $currentWeek;
            case 'next week':
                return min($currentWeek + 1, 18); // Assuming 18 weeks in the NFL season
            default:
                Log::warning('Unknown timeFrame provided:', ['timeFrame' => $timeFrame]);
                return null;
        }
    }
}
