<?php

namespace App\OpenAIFunctions;

use App\Helpers\OpenAI;
use App\Models\CollegeBasketballGame;
use App\Models\CollegeBasketballHypothetical;
use App\Repositories\Nfl\NflBettingOddsRepository;
use App\Repositories\Nfl\NflBoxScoreRepository;
use App\Repositories\Nfl\NflEloPredictionRepository;
use App\Repositories\Nfl\NflPlayerDataRepository;
use App\Repositories\Nfl\NflPlayerStatRepository;
use App\Repositories\Nfl\NflTeamStatRepository;
use App\Repositories\Nfl\TeamStatsRepository;
use App\Repositories\NflTeamScheduleRepository;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class OpenAIFunctionHandler
{
    protected NflEloPredictionRepository $repository;
    protected NflBoxScoreRepository $boxScoreRepository;
    protected NflTeamScheduleRepository $scheduleRepository;
    protected TeamStatsRepository $teamStatsRepository;
    protected NflPlayerDataRepository $playerDataRepository;
    protected NflBettingOddsRepository $bettingOddsRepository;
    protected NflPlayerStatRepository $playerStatRepository;
    protected NflTeamStatRepository $nflTeamStatsRepository;

    /**
     * Inject the required repositories and assign them to class properties.
     * Adjust the parameters as needed for your actual dependencies.
     */
    public function __construct(
        NflEloPredictionRepository $repository,
        NflBoxScoreRepository      $boxScoreRepository,
        NflTeamScheduleRepository  $scheduleRepository,
        TeamStatsRepository        $teamStatsRepository,
        NflPlayerDataRepository    $playerDataRepository,
        NflBettingOddsRepository   $bettingOddsRepository,
        NflPlayerStatRepository    $playerStatRepository,
        NflTeamStatRepository      $nflTeamStatsRepository
    )
    {
        $this->repository = $repository;
        $this->boxScoreRepository = $boxScoreRepository;
        $this->scheduleRepository = $scheduleRepository;
        $this->teamStatsRepository = $teamStatsRepository;
        $this->playerDataRepository = $playerDataRepository;
        $this->bettingOddsRepository = $bettingOddsRepository;
        $this->playerStatRepository = $playerStatRepository;
        $this->nflTeamStatsRepository = $nflTeamStatsRepository;
    }

    /**
     * Invoke a function based on its name and arguments.
     * Handles various NFL-related data retrieval tasks.
     */
    public function invokeFunction(string $functionName, array $arguments)
    {
        switch ($functionName) {

            case 'get_predictions_by_week':
                return $this->repository->getPredictions($arguments['week'] ?? OpenAI::getCurrentNFLWeek());

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
                $season = $arguments['season'] ?? now()->year;
                $conferenceFilter = $arguments['conferenceFilter'] ?? null;
                $divisionFilter = $arguments['divisionFilter'] ?? null;
                $returnType = $arguments['returnType'] ?? 'both';

                if (empty($teams)) {
                    throw new Exception('Teams parameter is required for quarterly points analysis.');
                }

                $result = $this->boxScoreRepository->getQuarterlyPointsAnalysis($teams, $season);
                Log::info('Response from getQuarterlyPointsAnalysis:', ['result' => $result]);

                switch ($returnType) {
                    case 'team_stats':
                        return ['team_quarterly_stats' => $result['team_quarterly_stats']];
                    case 'comparison':
                        return ['team_comparison' => $result['team_comparison']];
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
                    $arguments['gameIds'] ?? [],
                    $arguments['teamAbv1'] ?? null,
                    $arguments['teamAbv2'] ?? null,
                    $arguments['week'] ?? OpenAI::getCurrentNFLWeek(),
                    $arguments['locationFilter'] ?? null
                );

            case 'get_best_receivers':
                // Extract arguments with default values
                $playerFilter = $arguments['playerFilter'] ?? null;
                $teamFilter = $arguments['teamFilter'] ?? null;
                $week = $arguments['week'] ?? OpenAI::getCurrentNFLWeek();
                $startWeek = $arguments['start_week'] ?? null;
                $endWeek = $arguments['end_week'] ?? null;
                $yardThreshold = $arguments['yardThreshold'] ?? 50;
                $season = $arguments['season'] ?? 2024;

                // Log the received arguments for debugging
                Log::info('get_best_receivers called with arguments:', [
                    'playerFilter' => $playerFilter,
                    'teamFilter' => $teamFilter,
                    'week' => $week,
                    'startWeek' => $startWeek,
                    'endWeek' => $endWeek,
                    'yardThreshold' => $yardThreshold,
                    'season' => $season,
                ]);

                // Fetch the best receivers data from the repository
                $rawData = $this->teamStatsRepository->getBestReceivers(
                    teamFilter: $teamFilter,
                    week: $week,
                    startWeek: $startWeek,
                    endWeek: $endWeek,
                    playerFilter: $playerFilter,
                    yardThreshold: $yardThreshold,
                    season: $season
                );

                // Check if any data was returned
                if (empty($rawData['data'])) {
                    return [
                        'success' => false,
                        'message' => 'No receivers meet the criteria for the 2024 season.'
                    ];
                }
                // Format the raw data into an HTML table
                $formattedReceivers = $this->stylePlayerStatsResponse($rawData, 'receivers');
                Log::info($formattedReceivers);

                // Return the structured response
                return [
                    'success' => true,
                    'receivers' => $formattedReceivers
                ];

            case 'get_best_rushers':
                $playerFilter = $arguments['playerFilter'] ?? null;
                $teamFilter = $arguments['teamFilter'] ?? null;
                $week = $arguments['week'] ?? null;
                $startWeek = $arguments['start_week'] ?? null;
                $endWeek = $arguments['end_week'] ?? null;
                $yardThreshold = $arguments['yardThreshold'] ?? 50;

                Log::info('get_best_rushers called with arguments:', [
                    'playerFilter' => $playerFilter,
                    'teamFilter' => $teamFilter,
                    'week' => $week,
                    'startWeek' => $startWeek,
                    'endWeek' => $endWeek,
                    'yardThreshold' => $yardThreshold,
                ]);

                $rawData = $this->teamStatsRepository->getBestRushers(
                    teamFilter: $teamFilter,
                    week: $week,
                    startWeek: $startWeek,
                    endWeek: $endWeek,
                    playerFilter: $playerFilter,
                    yardThreshold: $yardThreshold,
                    season: $arguments['season'] ?? 2024
                );

                return $rawData;

            case 'get_best_tacklers':
                $rawData = $this->teamStatsRepository->getBestTacklers(
                    teamFilter: $arguments['teamFilter'] ?? null,
                    week: $arguments['week'] ?? openAi::getCurrentNFLWeek(),
                    startWeek: $arguments['start_week'] ?? OpenAI::getCurrentNFLWeek(),
                    endWeek: $arguments['end_week'] ?? null,
                    playerFilter: $arguments['playerFilter'] ?? null,
                    tackleThreshold: $arguments['tackleThreshold'] ?? null,
                    season: $arguments['season'] ?? 2024
                );

                return $rawData;

            case 'get_big_playmakers':
                return $this->teamStatsRepository->getBigPlaymakers($arguments['teamFilter'] ?? null);

            case 'get_situational_performance':
                return $this->teamStatsRepository->getSituationalPerformance(
                    $arguments['teamFilter'] ?? null,
                    $arguments['locationFilter'] ?? null,
                    $arguments['againstConference'] ?? null
                );

            case 'get_team_matchup_edge':
                return $this->teamStatsRepository->getTeamMatchupEdge(
                    $arguments['teamFilter'] ?? null,
                    $arguments['teamAbv1'] ?? null,
                    $arguments['teamAbv2'] ?? null,
                    $arguments['week'] ?? OpenAI::getCurrentNFLWeek(),
                    $arguments['locationFilter'] ?? null
                );

            case 'get_first_half_tendencies':
                return $this->teamStatsRepository->getFirstHalfTendencies(
                    $arguments['teamFilter'] ?? null,
                    $arguments['againstConference'] ?? null,
                    $arguments['locationFilter'] ?? null
                );

            case 'get_player_vs_conference_stats':
                return $this->teamStatsRepository->getPlayerVsConference(
                    $arguments['teamFilter'] ?? null,
                    $arguments['playerFilter'] ?? null,
                    $arguments['conferenceFilter'] ?? null
                );

            case 'find_players_by_age_range':
                return $this->playerDataRepository->findByAgeRange(
                    $arguments['minAge'] ?? null,
                    $arguments['maxAge'] ?? null,
                    $arguments['teamFilter'] ?? null
                );

            case 'find_players_by_experience':
                $years = $arguments['years'] ?? null;
                $teamFilter = $arguments['teamFilter'] ?? null;

                if (is_null($years)) {
                    return response()->json(['error' => 'Years parameter is required.'], 400);
                }

                return $this->playerDataRepository->findByExperience($years, $teamFilter);

            case 'get_team_injuries':
                if (isset($arguments['teamFilter'])) {
                    return $this->playerDataRepository->getTeamInjuries($arguments['teamFilter']);
                }
                return response()->json(['error' => 'Missing required parameter: teamFilter'], 400);

            case 'find_players_by_position':
                if (isset($arguments['position'])) {
                    return $this->playerDataRepository->findByPosition($arguments['position']);
                }
                return response()->json(['error' => 'Missing required parameter: position'], 400);

            case 'find_players_by_school':
                if (isset($arguments['school'])) {
                    return $this->playerDataRepository->findBySchool($arguments['school']);
                }
                return response()->json(['error' => 'Missing required parameter: school'], 400);

            case 'find_players_by_team':
                if (isset($arguments['teamFilter'])) {
                    return $this->playerDataRepository->findPlayersByTeam($arguments['teamFilter']);
                }
                return response()->json(['error' => 'Missing required parameter: teamFilter'], 400);

            case 'find_player_by_espn_name':
                if (isset($arguments['espnName'])) {
                    $player = $this->playerDataRepository->findByEspnName($arguments['espnName']);
                    return $player ? $player : response()->json(['error' => 'Player not found'], 404);
                }
                return response()->json(['error' => 'Missing required parameter: espnName'], 400);

            case 'get_free_agents':
                return $this->playerDataRepository->getFreeAgents();

            case 'get_odds_by_event_ids':
                return $this->bettingOddsRepository->getOddsByEventIds($arguments['eventIds']);

            case 'get_odds_by_team':
                return $this->bettingOddsRepository->getOddsByTeam($arguments['teamFilter']);

            case 'get_odds_by_week':
                return $this->bettingOddsRepository->getOddsByWeek($arguments['week'] ?? OpenAI::getCurrentNFLWeek());

            case 'get_odds_by_date_range':
                return $this->bettingOddsRepository->getOddsByDateRange($arguments['startDate'], $arguments['endDate']);

            case 'get_odds_by_moneyline':
                return $this->bettingOddsRepository->getOddsByMoneyline($arguments['moneyline']);

            case 'get_odds_by_team_and_week':
                $odds = $this->bettingOddsRepository->getOddsByTeamAndWeek(
                    $arguments['teamFilter'],
                    $arguments['week'] ?? OpenAI::getCurrentNFLWeek(),
                );

                return response()->json(['odds' => $odds->toArray()]);

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
                    $arguments['long_name'] ?? null,
                    $arguments['team_abv'] ?? null
                );

            case 'get_kicking_stats':
                return $this->playerStatRepository->getKickingStats(
                    $arguments['long_name'] ?? null,
                    $arguments['team_abv'] ?? null
                );

            case 'get_punting_stats':
                return $this->playerStatRepository->getPuntingStats(
                    $arguments['long_name'] ?? null,
                    $arguments['team_abv'] ?? null
                );

            case 'get_first_downs_average':
                return $this->nflTeamStatsRepository->getFirstDownsAverage(
                    $arguments['teamFilters'],
                    $arguments['week'] ?? OpenAI::getCurrentNFLWeek(),
                    $arguments['season'] ?? null
                );

            case 'get_team_stat_average':
                return $this->nflTeamStatsRepository->getTeamStatAverage(
                    $arguments['teamFilters'],
                    $arguments['statColumn'],
                    $arguments['week'] ?? OpenAI::getCurrentNFLWeek(),
                    $arguments['season'] ?? null
                );

            case 'get_half_scoring':
                return $this->teamStatsRepository->getHalfScoring(
                    $arguments['teamFilter'] ?? null,
                    $arguments['locationFilter'] ?? null,
                    $arguments['conferenceFilter'] ?? null,
                    $arguments['divisionFilter'] ?? null
                );

            case 'get_schedule_by_team':
                $teamId = $arguments['teamId'] ?? null;
                $teamFilter = $arguments['teamFilter'] ?? null;
                return $this->scheduleRepository->getScheduleByTeam($teamId, $teamFilter);

            case 'check_team_prediction':
                $teamAbv = $arguments['team_abv'] ?? null;
                $week = $arguments['week'] ?? OpenAI::getCurrentNFLWeek(); // Ensure OpenAI::getCurrentNFLWeek() exists
                $includeStats = $arguments['include_stats'] ?? false;
                $includeFactors = $arguments['include_factors'] ?? false;

                if (!$teamAbv) {
                    return [
                        'success' => false,
                        'message' => 'Team abbreviation is required for predictions.',
                    ];
                }

                $response = $this->repository->getTeamPrediction(
                    teamAbv: $teamAbv,
                    week: $week ?? OpenAI::getCurrentNFLWeek(),
                    includeStats: $includeStats,
                    includeFactors: $includeFactors
                );

                if (!$response['found']) {
                    Log::info('Prediction not found', ['team' => $teamAbv, 'week' => $week]);
                    return [
                        'success' => false,
                        'message' => $response['message'] ?? 'Prediction not available.',
                    ];
                }

                Log::info('Prediction response', ['response' => $response]);
                return [
                    'success' => true,
                    'data' => $response,
                ];

            // College Basketball Function Handlers
            case 'get_college_game_predictions':
                return $this->getCollegeGamePredictions($arguments);

            case 'analyze_team_performance':
                return $this->analyzeTeamPerformance($arguments);

            // College Basketball Game Function Handlers
            case 'get_upcoming_games':
                return $this->getUpcomingGames($arguments);

            case 'get_game_details':
                return $this->getGameDetails($arguments);

            case 'get_completed_games':
                return $this->getCompletedGames($arguments);

            case 'get_college_game_predictions_by_date':
                return $this->getCollegeGamePredictionsByDate($arguments);


            default:
                throw new Exception("Unknown function: $functionName");
        }
    }

    // Ensure these methods exist if you call them:
    // formatSchedule($scheduleResult)
    // handleGetNflTeamStats($arguments)
    // handleCompareTeamsStats($arguments)
    // handleGetTopTeamsByStat($arguments)
    // handleGetLeagueAverages($arguments)
    private function formatSchedule(array $scheduleResult)
    {
        // Implement the logic to format the schedule data
        return $scheduleResult;
    }

    public function stylePlayerStatsResponse(array $rawData, string $type): string
    {
        $allowedTypes = ['receivers', 'rushing', 'passing', 'defense'];

        if (!in_array($type, $allowedTypes, true)) {
            return 'Invalid type provided.';
        }

        if (empty($rawData['data'])) {
            return 'No receivers meet the criteria for the 2024 season.';
        }

        return $rawData['data'];
    }

    private function getCollegeGamePredictions(array $arguments)
    {
        $query = CollegeBasketballHypothetical::query();

        if (!empty($arguments['game_id'])) {
            $query->where('game_id', $arguments['game_id']);
        }

        if (!empty($arguments['home_team_abv'])) {
            $query->where('home_team', $arguments['home_team_abv']);
        }

        if (!empty($arguments['away_team_abv'])) {
            $query->where('away_team', $arguments['away_team_abv']);
        }

        if (!empty($arguments['season'])) {
            $query->whereYear('game_date', $arguments['season']);
        }

        $predictions = $query->get();

        if ($predictions->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No game predictions found for the specified criteria.'
            ];
        }

        return [
            'success' => true,
            'data' => $predictions
        ];
    }

    /**
     * Handle analyze_team_performance function.
     */

    /**
     * Handle analyze_team_performance function.
     */
    private function analyzeTeamPerformance(array $arguments)
    {
        Log::info('Invoking analyzeTeamPerformance', ['arguments' => $arguments]);

        $teamAbv = $arguments['team_abv'];
        $season = $arguments['season'] ?? Config('college_basketball.season');
        $metrics = $arguments['metrics'] ?? [];

        // Validate metrics
        $validMetrics = ['points_scored', 'points_allowed', 'rebounds', 'assists', 'turnovers', 'steals', 'blocks'];
        foreach ($metrics as $metric) {
            if (!in_array($metric, $validMetrics)) {
                Log::warning('Invalid metric provided', ['metric' => $metric]);
                return [
                    'success' => false,
                    'message' => "Invalid metric: {$metric}. Valid options are: " . implode(', ', $validMetrics) . '.'
                ];
            }
        }

        // Fetch games for the team and season
        $games = CollegeBasketballGame::where(function ($q) use ($teamAbv) {
            $q->where('home_team', $teamAbv)
                ->orWhere('away_team', $teamAbv);
        })
            ->whereYear('game_date', $season)
            ->get();

        if ($games->isEmpty()) {
            Log::info('No games found for the specified team and season.');
            return [
                'success' => false,
                'message' => 'No games found for the specified team and season.'
            ];
        }

        // Initialize analysis data
        $analysis = [];

        // Calculate metrics
        foreach ($metrics as $metric) {
            switch ($metric) {
                case 'points_scored':
                    $analysis['points_scored'] = $games->sum(function ($game) use ($teamAbv) {
                        return $game->home_team === $teamAbv ? $game->home_team_score : $game->away_team_score;
                    });
                    break;

                case 'points_allowed':
                    $analysis['points_allowed'] = $games->sum(function ($game) use ($teamAbv) {
                        return $game->home_team === $teamAbv ? $game->away_team_score : $game->home_team_score;
                    });
                    break;

                case 'rebounds':
                    // Assuming you have a 'rebounds' column or related data
                    // Replace with actual data fetching logic
                    $analysis['rebounds'] = $games->sum('rebounds');
                    break;

                case 'assists':
                    // Assuming you have an 'assists' column or related data
                    $analysis['assists'] = $games->sum('assists');
                    break;

                case 'turnovers':
                    // Assuming you have a 'turnovers' column or related data
                    $analysis['turnovers'] = $games->sum('turnovers');
                    break;

                case 'steals':
                    // Assuming you have a 'steals' column or related data
                    $analysis['steals'] = $games->sum('steals');
                    break;

                case 'blocks':
                    // Assuming you have a 'blocks' column or related data
                    $analysis['blocks'] = $games->sum('blocks');
                    break;

                default:
                    // This should not occur due to earlier validation
                    break;
            }
        }

        Log::info('Successfully analyzed team performance', ['team_abv' => $teamAbv, 'season' => $season]);

        return [
            'success' => true,
            'analysis' => $analysis
        ];
    }

    private function getUpcomingGames(array $arguments)
    {
        Log::info('Invoking getUpcomingGames', ['arguments' => $arguments]);

        // Determine date range based on 'time_frame'
        $timeFrame = $arguments['time_frame'];
        $startDate = null;
        $endDate = null;

        switch ($timeFrame) {
            case 'today':
                $startDate = Carbon::today()->startOfDay();
                $endDate = Carbon::today()->endOfDay();
                break;

            case 'this_week':
                $startDate = Carbon::now()->startOfWeek(); // Typically Monday
                $endDate = Carbon::now()->endOfWeek();     // Typically Sunday
                break;

            case 'next_week':
                $startDate = Carbon::now()->addWeek()->startOfWeek();
                $endDate = Carbon::now()->addWeek()->endOfWeek();
                break;

            case 'custom':
                if (empty($arguments['start_date']) || empty($arguments['end_date'])) {
                    Log::warning('Missing start_date or end_date for custom time_frame');
                    return [
                        'success' => false,
                        'message' => 'Both start_date and end_date are required for custom time_frame.'
                    ];
                }
                try {
                    $startDate = Carbon::createFromFormat('Y-m-d', $arguments['start_date'])->startOfDay();
                    $endDate = Carbon::createFromFormat('Y-m-d', $arguments['end_date'])->endOfDay();
                } catch (Exception $e) {
                    Log::error('Invalid date format for custom time_frame', ['error' => $e->getMessage()]);
                    return [
                        'success' => false,
                        'message' => 'Invalid date format. Please use YYYY-MM-DD.'
                    ];
                }
                break;

            default:
                Log::warning('Invalid time_frame provided', ['time_frame' => $timeFrame]);
                return [
                    'success' => false,
                    'message' => 'Invalid time_frame provided.'
                ];
        }

        // Build the query
        $query = CollegeBasketballGame::query()
            ->whereBetween('game_date', [$startDate, $endDate])
            ->where('is_completed', false) // Fetch only upcoming games
            ->with(['homeTeam', 'awayTeam']); // Eager load relationships

        // Optional: Filter by team abbreviation if provided
        if (!empty($arguments['team_abv'])) {
            $teamAbv = $arguments['team_abv'];
            $query->where(function ($q) use ($teamAbv) {
                $q->where('home_team', $teamAbv)
                    ->orWhere('away_team', $teamAbv);
            });
        }

        // Optional: Filter by location if provided
        if (!empty($arguments['location'])) {
            $location = ucfirst(strtolower($arguments['location'])); // Normalize input
            $query->where('location', $location);
        }

        // Pagination parameters
        $page = isset($arguments['page']) ? (int)$arguments['page'] : 1;
        $perPage = isset($arguments['per_page']) ? (int)$arguments['per_page'] : 10;

        // Execute the query with pagination
        $upcomingGames = $query->paginate($perPage, ['*'], 'page', $page);

        // Handle no results
        if ($upcomingGames->isEmpty()) {
            Log::info('No upcoming games found for the specified criteria.');
            return [
                'success' => false,
                'message' => 'No upcoming games found for the specified criteria.'
            ];
        }

        // Format the games
        $formattedGames = $upcomingGames->map(function ($game) {
            // Debugging: Check the type of homeTeam and awayTeam
            Log::info('Type of homeTeam', ['type' => gettype($game->homeTeam)]);
            Log::info('homeTeam content', ['homeTeam' => $game->homeTeam]);

            Log::info('Type of awayTeam', ['type' => gettype($game->awayTeam)]);
            Log::info('awayTeam content', ['awayTeam' => $game->awayTeam]);

            return [
                'event_id' => $game->id,
                'game_date' => $game->game_date->toDateString(), // Now works as Carbon
                'game_time' => $game->game_time,
                'location' => $game->location,
                'home_team' => [
                    'id' => $game->homeTeam->id, // Access via relationship
                    'name' => $game->homeTeam->name,
                    'abbreviation' => $game->homeTeam->abbreviation,
                    'rank' => $game->home_rank,
                ],
                'away_team' => [
                    'id' => $game->awayTeam->id, // Access via relationship
                    'name' => $game->awayTeam->name,
                    'abbreviation' => $game->awayTeam->abbreviation,
                    'rank' => $game->away_rank,
                ],
                'hotness_score' => $game->hotness_score,
                'matchup' => $game->matchup,
            ];
        });

        Log::info('Successfully retrieved upcoming games', ['count' => $upcomingGames->count()]);

        return [
            'success' => true,
            'data' => $formattedGames,
            'pagination' => [
                'current_page' => $upcomingGames->currentPage(),
                'last_page' => $upcomingGames->lastPage(),
                'per_page' => $upcomingGames->perPage(),
                'total' => $upcomingGames->total(),
            ]
        ];
    }

    /**
     * Handle get_game_details function.
     */
    private function getGameDetails(array $arguments)
    {
        Log::info('Invoking getGameDetails', ['arguments' => $arguments]);

        $gameId = $arguments['game_id'];

        // Validate game_id
        if (empty($gameId)) {
            return [
                'success' => false,
                'message' => 'game_id is required.'
            ];
        }

        // Fetch the game
        $game = CollegeBasketballGame::with(['homeTeam', 'awayTeam'])
            ->where('id', $gameId)
            ->first();

        if (!$game) {
            Log::warning('Game not found', ['game_id' => $gameId]);
            return [
                'success' => false,
                'message' => 'Game not found.'
            ];
        }

        // Format the game details
        $gameDetails = [
            'game_id' => $game->id,
            'game_date' => $game->game_date->toDateString(),
            'game_time' => $game->game_time,
            'location' => $game->location,
            'home_team' => [
                'id' => $game->home_team->id,
                'name' => $game->home_team->name,
                'abbreviation' => $game->home_team->abbreviation,
                'rank' => $game->home_rank,
                'score' => $game->home_team_score,
            ],
            'away_team' => [
                'id' => $game->away_team->id,
                'name' => $game->away_team->name,
                'abbreviation' => $game->away_team->abbreviation,
                'rank' => $game->away_rank,
                'score' => $game->away_team_score,
            ],
            'hotness_score' => $game->hotness_score,
            'matchup' => $game->matchup,
            'is_completed' => $game->is_completed,
        ];

        Log::info('Successfully retrieved game details', ['game_id' => $gameId]);

        return [
            'success' => true,
            'data' => $gameDetails
        ];
    }

    /**
     * Handle get_completed_games function.
     */

    private function getCollegeGamePredictionsByDate(array $arguments)
    {
        // Validate 'time_frame'
        $timeFrame = $arguments['time_frame'];

        // Initialize date range
        $startDate = null;
        $endDate = null;

        // Determine date range based on 'time_frame'
        switch ($timeFrame) {
            case 'today':
                $startDate = Carbon::today()->startOfDay();
                $endDate = Carbon::today()->endOfDay();
                break;

            case 'this_week':
                $startDate = Carbon::now()->startOfWeek(); // Monday
                $endDate = Carbon::now()->endOfWeek();     // Sunday
                break;

            default:
                throw new Exception("Invalid time_frame: $timeFrame");
        }

        // Build the query
        $query = CollegeBasketballHypothetical::query()
            ->whereBetween('game_date', [$startDate, $endDate]);

        // Optional: Filter by season if provided
        if (!empty($arguments['season'])) {
            $query->whereYear('game_date', $arguments['season']);
        }

        // Optional: Filter by team abbreviation if provided
        if (!empty($arguments['team_abv'])) {
            $teamAbv = $arguments['team_abv'];
            $query->where(function ($q) use ($teamAbv) {
                $q->where('home_team', $teamAbv)
                    ->orWhere('away_team', $teamAbv);
            });
        }

        // Execute the query
        $predictions = $query->get();

        // Handle no results
        if ($predictions->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No game predictions found for the specified criteria.'
            ];
        }

        // Format the predictions (you can customize this as needed)
        $formattedPredictions = $predictions->map(function ($game) {
            return [
                'game_id' => $game->game_id,
                'game_date' => $game->game_date->toDateString(),
                'home_team' => $game->home_team,
                'away_team' => $game->away_team,
                'hypothetical_spread' => $game->hypothetical_spread,
                'offense_difference' => $game->offense_difference,
                'defense_difference' => $game->defense_difference,
                // Add more fields as necessary...
            ];
        });

        return [
            'success' => true,
            'data' => $formattedPredictions
        ];
    }
}
