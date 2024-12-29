<?php

namespace App\OpenAIFunctions;

use App\Helpers\OpenAI;
use App\Models\CollegeBasketballGame;
use App\Models\CollegeBasketballHypothetical;
use App\Models\CollegeBasketballTeam;
use App\Models\Nfl\PlayerTrend;
use App\Repositories\Nfl\NflBettingOddsRepository;
use App\Repositories\Nfl\NflBoxScoreRepository;
use App\Repositories\Nfl\NflEloPredictionRepository;
use App\Repositories\Nfl\NflPlayerDataRepository;
use App\Repositories\Nfl\NflPlayerStatRepository;
use App\Repositories\Nfl\NflTeamStatRepository;
use App\Repositories\Nfl\TeamStatsRepository;
use App\Repositories\NflTeamScheduleRepository;
use Exception;
use Illuminate\Support\Facades\DB;
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

            case 'get_recent_game_details':
                $arguments['team_identifier'] = $arguments['team_abv'] ?? null;
                return $this->getRecentGameDetails($arguments);

            // College Basketball Game Function Handlers
            case 'get_player_trends':
                return $this->getPlayerTrends($arguments);

            case 'get_player_trends_by_market':
                $market = $arguments['market'];
                $week = $arguments['week'] ?? null;

                // Retrieve player trends
                return $this->getPlayerTrendsByMarket($market, $week);

            case 'compare_players':
                $players = $arguments['players'];
                $market = $arguments['market'];

                return $this->comparePlayerTrends($players, $market);
            case 'get_player_trends_by_team':
                $team = $arguments['team'] ?? null;
                $season = $arguments['season'] ?? now()->year; // Default to current year
                $week = $arguments['week'] ?? null;
                $market = $arguments['market'] ?? 'player_receptions'; // Default market

                if (!$team || !$season || !$market) {
                    throw new Exception('Missing required parameters: team, season, or market.');
                }

                return $this->getPlayerTrendsByTeam($team, $season, $week, $market);


            default:
                throw new Exception("Unknown function: $functionName");
        }
    }

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

    private function getCollegeGamePredictions(array $arguments): array
    {
        // Validate season format
        if (!empty($arguments['season']) && !preg_match('/^\d{4}$/', $arguments['season'])) {
            return [
                'success' => false,
                'message' => 'Invalid season format. Please provide a valid year (e.g., 2024).'
            ];
        }

        // Ensure at least one filter is provided
        if (empty($arguments['game_id']) && empty($arguments['home_team_abv']) && empty($arguments['away_team_abv']) && empty($arguments['season'])) {
            return [
                'success' => false,
                'message' => 'No filters provided. Please specify at least one filter.'
            ];
        }

        // Build query with filters
        $query = CollegeBasketballHypothetical::query()
            ->when(!empty($arguments['game_id']), function ($q) use ($arguments) {
                $q->where('game_id', $arguments['game_id']);
            })
            ->when(!empty($arguments['home_team_abv']), function ($q) use ($arguments) {
                $q->where('home_team', $arguments['home_team_abv']);
            })
            ->when(!empty($arguments['away_team_abv']), function ($q) use ($arguments) {
                $q->where('away_team', $arguments['away_team_abv']);
            })
            ->when(!empty($arguments['season']), function ($q) use ($arguments) {
                $q->whereYear('game_date', $arguments['season']);
            });

        // Log the query filters
        Log::info('Fetching college game predictions', ['filters' => $arguments]);

        // Fetch and paginate results
        $predictions = $query->paginate(10);

        if ($predictions->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No game predictions found for the specified criteria.'
            ];
        }

        // Format predictions
        $formattedPredictions = $predictions->map(function ($prediction) {
            return [
                'game_id' => $prediction->game_id,
                'home_team' => $prediction->home_team,
                'away_team' => $prediction->away_team,
                'game_date' => $prediction->game_date,
                'hypothetical_spread' => $prediction->hypothetical_spread,
                'offense_difference' => $prediction->offense_difference,
                'defense_difference' => $prediction->defense_difference,
            ];
        });

        return [
            'success' => true,
            'data' => $formattedPredictions,
            'pagination' => [
                'current_page' => $predictions->currentPage(),
                'last_page' => $predictions->lastPage(),
                'per_page' => $predictions->perPage(),
                'total' => $predictions->total(),
            ]
        ];
    }

    /**
     * Handle analyze_team_performance function.
     */
    
    private function analyzeTeamPerformance(array $arguments)
    {
        Log::info('Invoking analyzeTeamPerformance', ['arguments' => $arguments]);

        $teamAbv = $arguments['team_abv'];
        $season = $arguments['season'] ?? config('college_basketball.season');
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

    private function getRecentGameDetails(array $arguments)
    {
        Log::info('Fetching recent game details', ['arguments' => $arguments]);

        // Validate the presence of the required 'team_identifier' key
        if (empty($arguments['team_identifier'])) {
            Log::error('Missing required parameter: team_identifier');
            return [
                'success' => false,
                'message' => 'The team_identifier parameter is required to fetch recent game details.',
            ];
        }

        $teamIdentifier = $arguments['team_identifier'];
        $limit = isset($arguments['limit']) ? (int)$arguments['limit'] : 1;

        // Try to find the team by various columns using exact matches
        $team = CollegeBasketballTeam::where('abbreviation', '=', $teamIdentifier)
            ->orWhere('name', '=', $teamIdentifier)
            ->orWhere('display_name', '=', $teamIdentifier)
            ->orWhere('short_display_name', '=', $teamIdentifier)
            ->orWhere('slug', '=', $teamIdentifier)
            ->orWhere('nickname', '=', $teamIdentifier)
            ->first();

        if (!$team) {
            Log::error('Team not found', ['team_identifier' => $teamIdentifier]);
            return [
                'success' => false,
                'message' => "No team found matching identifier: $teamIdentifier",
            ];
        }

        // Fetch the most recent games involving the team with a limit
        $games = CollegeBasketballGame::with(['homeTeam', 'awayTeam'])
            ->where('home_team_id', $team->id)
            ->orWhere('away_team_id', $team->id)
            ->orderBy('game_date', 'desc')
            ->take($limit)
            ->get();

        $retrievedCount = $games->count();

        if ($retrievedCount === 0) {
            Log::error('No recent games found for the team', ['team_id' => $team->id]);
            return [
                'success' => false,
                'message' => 'No recent games found for the specified team.',
            ];
        }

        if ($retrievedCount < $limit) {
            Log::warning('Fewer games retrieved than requested', [
                'requested_limit' => $limit,
                'retrieved_count' => $retrievedCount,
            ]);
        }

        // Format game details
        $gameDetails = $games->map(function ($game) {
            return $this->formatGameDetails($game);
        });

        Log::info('Game details retrieved successfully', ['game_details' => $gameDetails]);

        return [
            'success' => true,
            'games_retrieved' => $retrievedCount,
            'games_requested' => $limit,
            'games' => $gameDetails,
        ];
    }

    /**
     * Format game details into an array.
     *
     * @param CollegeBasketballGame $game
     * @return array
     */
    private function formatGameDetails(?CollegeBasketballGame $game): array
    {
        if (!$game) {
            return [
                'message' => 'Game details are not available.',
            ];
        }

        return [
            'game_id' => $game->id,
            'game_date' => $game->game_date ? $game->game_date->toDateString() : null,
            'game_time' => $game->game_time,
            'location' => $game->location,
            'home_team' => $game->homeTeam ? [
                'id' => $game->homeTeam->id,
                'name' => $game->homeTeam->name,
                'abbreviation' => $game->homeTeam->abbreviation,
                'rank' => $game->home_rank,
                'score' => $game->home_team_score,
            ] : null,
            'away_team' => $game->awayTeam ? [
                'id' => $game->awayTeam->id,
                'name' => $game->awayTeam->name,
                'abbreviation' => $game->awayTeam->abbreviation,
                'rank' => $game->away_rank,
                'score' => $game->away_team_score,
            ] : null,
            'hotness_score' => $game->hotness_score,
            'matchup' => $game->matchup,
            'is_completed' => $game->is_completed,
        ];
    }

    protected function getPlayerTrends(array $arguments): array
    {
        $season = $arguments['season'] ?? now()->year;
        $week = $arguments['week'] ?? null;
        $eventId = $arguments['event_id'] ?? null;
        $market = $arguments['market'] ?? 'player_receptions';

        // Fetch player trends from the PlayerTrend model
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

        // Apply additional calculations and adjustments
        $playerTrends = $playerTrends->map(function ($trend) use ($week, $season, $market) {
            $this->calculateOverPercentage($trend);
            $this->applyRecentPerformanceAdjustment($trend, $week, $season, $market);
            $this->determineAction($trend);
            return $trend;
        });

        return $playerTrends->toArray();


// Helper methods from the controller
    }

    protected function calculateOverPercentage(&$trend)
    {
        $totalAttempts = $trend->total_over_count + $trend->total_under_count;
        $trend->over_percentage = $totalAttempts > 0
            ? round(($trend->total_over_count / $totalAttempts) * 100, 2)
            : 0;

        Log::info("Over percentage for {$trend->player}: {$trend->over_percentage}%");
    }

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


    protected function determineAction(&$trend)
    {
        $trend->action = match (true) {
            $trend->over_percentage >= 70 => 'Bet',
            $trend->over_percentage >= 50 => 'Consider',
            default => 'Stay Away',
        };

        Log::info("Action for {$trend->player}: {$trend->action}");
    }

    protected function getPlayerTrendsByMarket(string $market, ?int $week): array
    {
        // Fetch trends based on the market
        $trends = PlayerTrend::where('market', $market)
            ->when($week, fn($q) => $q->where('week', $week))
            ->get();

        return $trends->toArray();
    }

    protected function comparePlayerTrends(array $players, string $market): array
    {
        // Fetch trends for the specified players
        $trends = PlayerTrend::whereIn('player', $players)
            ->where('market', $market)
            ->get();

        // Format for comparison
        $comparison = $trends->map(function ($trend) {
            return [
                'player' => $trend->player,
                'point' => $trend->point,
                'over' => $trend->total_over_count,
                'under' => $trend->total_under_count,
                'hit_percentage' => $trend->over_percentage,
                'action' => $trend->action,
            ];
        });

        return $comparison->toArray();
    }

    protected function getPlayerTrendsByTeam($team, $season, $week = null, $market = 'player_receptions')
    {
        $query = PlayerTrend::select([
            'player',
            'point',
            DB::raw('SUM(over_count) as total_over_count'),
            DB::raw('SUM(under_count) as total_under_count'),
        ])
            ->where('team', $team)
            ->where('season', $season)
            ->when($week, fn($query) => $query->where('week', '<=', $week))
            ->when($market, fn($query) => $query->where('market', $market))
            ->groupBy('player', 'point')
            ->orderBy('player');

        return $query->get();
    }


}
