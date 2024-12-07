<?php

namespace App\Http\Controllers;

use App\Repositories\Nfl\NflBettingOddsRepository;
use App\Repositories\Nfl\NflEloPredictionRepository;
use App\Repositories\Nfl\NflPlayerDataRepository;
use App\Repositories\Nfl\TeamStatsRepository;
use App\Repositories\NflTeamScheduleRepository;
use App\Services\OpenAIChatService;
use Exception;
use Illuminate\Http\Request;
use League\CommonMark\CommonMarkConverter;

class ChatGPTController extends Controller
{
    protected OpenAIChatService $chatService;
    protected NflEloPredictionRepository $repository;
    protected TeamStatsRepository $teamStatsRepository;
    protected NflBettingOddsRepository $bettingOddsRepository;
    private NflPlayerDataRepository $playerDataRepository;

    private NflTeamScheduleRepository $scheduleRepository;

    public function __construct(OpenAIChatService $chatService, NflEloPredictionRepository $repository)
    {
        $this->chatService = $chatService;
        $this->repository = $repository;
        $this->teamStatsRepository = new TeamStatsRepository();
        $this->playerDataRepository = new NflPlayerDataRepository();
        $this->bettingOddsRepository = new NflBettingOddsRepository();
        $this->scheduleRepository = new NflTeamScheduleRepository();
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

        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant who can dynamically fetch NFL predictions.'],
            ['role' => 'user', 'content' => $userMessage],
        ];

        try {
            $response = $this->chatService->getChatCompletion($messages);

            // Check if OpenAI made a function call
            if (!empty($response['choices'][0]['message']['function_call'])) {
                $functionCall = $response['choices'][0]['message']['function_call'];
                $functionName = $functionCall['name'];
                $arguments = json_decode($functionCall['arguments'], true);

                // Determine the current week. Adjust this logic as needed.
                $currentWeek = 10;

                // Parse natural language week references
                if (str_contains($userMessage, 'last week')) {
                    $arguments['week'] = $currentWeek - 1;
                }

                if (preg_match('/over the last (\d+) weeks/', $userMessage, $matches)) {
                    $weeksBack = (int)$matches[1];
                    $arguments['start_week'] = $currentWeek - $weeksBack;
                    $arguments['end_week'] = $currentWeek - 1;
                }

                // Invoke the function with updated arguments
                $functionResult = $this->invokeFunction($functionName, $arguments);

                $messages[] = [
                    'role' => 'function',
                    'name' => $functionName,
                    'content' => json_encode($functionResult),
                ];

                $finalResponse = $this->chatService->getChatCompletion($messages);

                // Convert the final response content (which may contain Markdown) to HTML
                $converter = new CommonMarkConverter();
                $htmlResponse = $converter->convert($finalResponse['choices'][0]['message']['content'])->getContent();

                return view('openai.index', ['response' => $htmlResponse]);
            }

            // If no function call, convert the normal text response (also possible Markdown) to HTML
            $converter = new CommonMarkConverter();
            $htmlResponse = $converter->convert($response['choices'][0]['message']['content'])->getContent();

            return view('openai.index', ['response' => $htmlResponse]);
        } catch (Exception $e) {
            return view('openai.index', ['response' => 'An error occurred: ' . $e->getMessage()]);
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
                    $arguments['team_id'],
                    $arguments['start_date'] ?? null,
                    $arguments['end_date'] ?? null
                );
            case 'get_recent_games':
                return $this->teamStatsRepository->getRecentGames();
            case 'get_average_points':
                return $this->teamStatsRepository->getAveragePoints(
                    $arguments['teamFilter'] ?? null,
                    $arguments['locationFilter'] ?? null,
                    $arguments['conferenceFilter'] ?? null,
                    $arguments['divisionFilter'] ?? null
                );
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
                return $this->teamStatsRepository->getBestReceivers(
                    $arguments['teamFilter'] ?? null,
                    $arguments['week'] ?? null,
                    $arguments['start_week'] ?? null,
                    $arguments['end_week'] ?? null
                );

            // NFL best rushers
            case 'get_best_rushers':
                return $this->teamStatsRepository->getBestRushers(
                    $arguments['teamFilter'] ?? null,
                    $arguments['week'] ?? null,
                    $arguments['start_week'] ?? null,
                    $arguments['end_week'] ?? null
                );

            // NFL best tacklers
            case 'get_best_tacklers':
                return $this->teamStatsRepository->getBestTacklers(
                    $arguments['teamFilter'] ?? null,
                    $arguments['week'] ?? null,
                    $arguments['start_week'] ?? null,
                    $arguments['end_week'] ?? null
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
                return $this->playerDataRepository->findByExperience($arguments['years']);

            // NFL player by injury
            case 'get_team_injuries':
                return $this->playerDataRepository->getTeamInjuries($arguments['injuryDesignation']);

            // NFL player by position
            case 'find_players_by_position':
                return $this->playerDataRepository->findByPosition($arguments['position']);

            // NFL player by school
            case 'find_players_by_school':
                return $this->playerDataRepository->findBySchool($arguments['school']);

            //NFL players by team
            case 'find_players_by_team':
                return $this->playerDataRepository->findPlayersByTeam(
                    $arguments['teamId'] ?? null,
                    $arguments['teamFilter'] ?? null
                );

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

            // NFL schedule by team
            case 'get_schedule_by_team':
                $teamId = $arguments['teamId'] ?? null;
                $teamFilter = $arguments['teamFilter'] ?? null;


                return $this->scheduleRepository->getScheduleByTeam($teamId, $teamFilter);


            default:
                throw new Exception("Unknown function: $functionName");
        }
    }
}
