<?php

namespace App\Http\Controllers;

use App\Repositories\Nfl\NflEloPredictionRepository;
use App\Repositories\Nfl\NflPlayerDataRepository;
use App\Repositories\Nfl\TeamStatsRepository;
use App\Services\OpenAIChatService;
use Exception;
use Illuminate\Http\Request;

class ChatGPTController extends Controller
{
    protected OpenAIChatService $chatService;
    protected NflEloPredictionRepository $repository;
    protected TeamStatsRepository $teamStatsRepository;
    private NflPlayerDataRepository $playerDataRepository;

    public function __construct(OpenAIChatService $chatService, NflEloPredictionRepository $repository)
    {
        $this->chatService = $chatService;
        $this->repository = $repository;
        $this->teamStatsRepository = new TeamStatsRepository();
        $this->playerDataRepository = new NflPlayerDataRepository();
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

                $functionResult = $this->invokeFunction($functionName, $arguments);

                $messages[] = [
                    'role' => 'function',
                    'name' => $functionName,
                    'content' => json_encode($functionResult),
                ];

                $finalResponse = $this->chatService->getChatCompletion($messages);

                return view('openai.index', ['response' => $finalResponse['choices'][0]['message']['content']]);
            }

            return view('openai.index', ['response' => $response['choices'][0]['message']['content']]);
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
            case 'get_best_receivers':
                return $this->teamStatsRepository->getBestReceivers($arguments['teamFilter'] ?? null);
            case 'get_best_rushers':
                return $this->teamStatsRepository->getBestRushers($arguments['teamFilter'] ?? null);
            case 'get_big_playmakers':
                return $this->teamStatsRepository->getBigPlaymakers($arguments['teamFilter'] ?? null);
            case 'get_team_matchup_edge':
                return $this->teamStatsRepository->getTeamMatchupEdge($arguments['teamFilter']);
            case 'get_first_half_tendencies':
                return $this->teamStatsRepository->getFirstHalfTendencies($arguments['teamFilter']);
            case 'get_player_vs_conference_stats':
                return $this->teamStatsRepository->getPlayerVsConference($arguments['playerFilter']);
            case 'find_players_by_age_range':
                return $this->playerDataRepository->findByAgeRange($arguments['minAge'], $arguments['maxAge']);
            case 'find_players_by_experience':
                return $this->playerDataRepository->findByExperience($arguments['years']);
//            case 'get_team_injuries':
//                return $this->playerDataRepository->getTeamInjuries($arguments['designation']);
            case 'find_players_by_position':
                return $this->playerDataRepository->findByPosition($arguments['position']);
            case 'find_players_by_school':
                return $this->playerDataRepository->findBySchool($arguments['school']);
//            case 'find_players_by_team':
//                return $this->playerDataRepository->findByTeam($arguments['teamId']);
            default:
                throw new Exception("Unknown function: $functionName");
        }
    }
}
