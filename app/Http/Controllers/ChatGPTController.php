<?php

namespace App\Http\Controllers;

use App\Helpers\OpenAI;
use App\Repositories\Nfl\NflBettingOddsRepository;
use App\Repositories\Nfl\NflEloPredictionRepository;
use App\Repositories\Nfl\NflPlayerDataRepository;
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

    private NflTeamScheduleRepository $scheduleRepository;
    private NflTeamStatRepository $nflTeamStatsRepository;

    public function __construct(OpenAIChatService $chatService, NflEloPredictionRepository $repository)
    {
        $this->chatService = $chatService;
        $this->repository = $repository;
        $this->teamStatsRepository = new TeamStatsRepository();
        $this->playerDataRepository = new NflPlayerDataRepository();
        $this->bettingOddsRepository = new NflBettingOddsRepository();
        $this->scheduleRepository = new NflTeamScheduleRepository();
        $this->nflTeamStatsRepository = new NflTeamStatRepository();
    }

    /**
     * Show the chat view.
     */
    public function showChat()
    {
        return view('openai.index');
    }

    /**
     * Handle chat messages and OpenAI function calling.
     */
    public function ask(Request $request)
    {
        $userMessage = $request->input('question', 'What are the predictions for week 14?');
        $currentWeek = OpenAI::getCurrentNFLWeek();

        try {
            $messages = OpenAI::buildConversationMessages($currentWeek, now()->toFormattedDateString(), $userMessage);
            $arguments = OpenAI::determineFunctionAndArguments($userMessage, $currentWeek);

            $response = $this->chatService->getChatCompletion($messages, [
                'function_call' => 'auto', // Let OpenAI decide the function
            ]);

            if (!empty($response['choices'][0]['message']['function_call'])) {
                $functionCall = $response['choices'][0]['message']['function_call'];
                $functionName = $functionCall['name'];
                $arguments = array_merge($arguments, json_decode($functionCall['arguments'], true));

                $functionResult = $this->invokeFunction($functionName, $arguments);

                $messages[] = [
                    'role' => 'function',
                    'name' => $functionName,
                    'content' => json_encode($functionResult),
                ];

                $response = $this->chatService->getChatCompletion($messages, [
                    'function_call' => 'auto',
                ]);
            }

            $content = OpenAI::convertMarkdownToHtml(OpenAI::validateResponseContent($response));

            return view('openai.index', ['response' => $content]);
        } catch (Exception $e) {
            return OpenAI::handleException($e);
        }
    }

    private function invokeFunction(string $functionName, array $arguments)
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

            case 'get_schedule':
                // Set default season to 2024 if not provided
                $season = $args['season'] ?? config('nfl.seasonYear', 2024);

                // Get current week using the helper function
                $currentWeek = OpenAI::getCurrentNFLWeek();

                // Initialize the week variable
                $week = null;

                // Determine the week based on 'timeFrame' if provided
                if (isset($args['timeFrame'])) {
                    switch (strtolower($args['timeFrame'])) {
                        case 'last week':
                            $week = $currentWeek - 1;
                            break;
                        case 'this week':
                            $week = $currentWeek;
                            break;
                        case 'next week':
                            $week = $currentWeek + 1;
                            break;
                        // Add more cases as needed for different time frames
                        default:
                            Log::warning('Unknown timeFrame provided:', ['timeFrame' => $args['timeFrame']]);
                            break;
                    }

                    // Validate the derived week number
                    if ($week < 1 || $week > count(config('nfl.weeks'))) {
                        Log::error('Derived week number is out of valid range.', ['week' => $week]);
                        $week = null; // Optionally, set to a default value or handle accordingly
                    }
                }

                // Log the incoming arguments for debugging purposes
                Log::info('get_schedule called with arguments:', [
                    'teamId' => $args['teamId'] ?? null,
                    'teamFilter' => $args['teamFilter'] ?? null,
                    'season' => $season,
                    'week' => $week,
                    'startDate' => $args['startDate'] ?? null,
                    'endDate' => $args['endDate'] ?? null,
                    'conferenceFilter' => $args['conferenceFilter'] ?? null,
                ]);

                // Call the getSchedule method with the provided and derived arguments
                $schedule = $this->scheduleRepository->getSchedule(
                    $args['teamId'] ?? null,
                    $args['teamFilter'] ?? null,
                    $args['startDate'] ?? null,
                    $args['endDate'] ?? null,
                    $args['conferenceFilter'] ?? null,
                    $season,
                    $week
                );

                // Log the result for debugging purposes
                Log::info('get_schedule returned:', ['schedule' => $schedule]);

                // Handle empty schedule responses
                if (empty($schedule)) {
                    Log::warning('No schedule data found for the provided criteria.', [
                        'teamFilter' => $args['teamFilter'] ?? null,
                        'season' => $season,
                        'week' => $week,
                        'startDate' => $args['startDate'] ?? null,
                        'endDate' => $args['endDate'] ?? null,
                        'conferenceFilter' => $args['conferenceFilter'] ?? null,
                    ]);

                    return response()->json(['message' => 'No schedule data found for the specified criteria.'], 404);
                }

                // Format the schedule data into a user-friendly structure
                $formattedSchedule = $this->formatSchedule($schedule);

                // Log the formatted schedule
                Log::info('Formatted schedule:', ['formattedSchedule' => $formattedSchedule]);

                return response()->json(['schedule' => $formattedSchedule], 200);

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
                return $this->bettingOddsRepository->getOddsByTeamAndWeek(
                    $arguments['teamFilter'],
                    $arguments['week']
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
            return response()->json(['message' => 'Missing required parameters: team_abv and week.'], 400);
        }

        $result = $this->teamStatsRepository->getNflTeamStats($teamAbv, $week);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 404);
        }

        return response()->json(['team_stats' => $result['data']], 200);
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


}
