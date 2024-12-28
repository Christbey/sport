<?php

namespace App\Http\Controllers\Nfl;

use App\Http\Controllers\Controller;
use App\Repositories\Nfl\Interfaces\{NflBettingOddsRepositoryInterface,
    NflEloPredictionRepositoryInterface,
    NflPlayerDataRepositoryInterface,
    NflTeamScheduleRepositoryInterface};
use App\Services\NflGameEnrichmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use stdClass;

class NflEloRatingController extends Controller
{
    public function __construct(
        private readonly NflEloPredictionRepositoryInterface $eloRepo,
        private readonly NflBettingOddsRepositoryInterface   $oddsRepo,
        private readonly NflTeamScheduleRepositoryInterface  $scheduleRepo,
        private readonly NflPlayerDataRepositoryInterface    $playerRepo,
        private readonly NflGameEnrichmentService            $gameEnrichmentService
    )
    {
    }

    public function index(Request $request)
    {
        $week = $request->input('week');
        $eloPredictions = $this->eloRepo->getPredictions($week)->where('year', 2024);
        $gameIds = $eloPredictions->pluck('game_id');

        $odds = $this->oddsRepo->findByEventIds($gameIds);
        $schedules = $this->scheduleRepo->findByGameIds($gameIds) ?? collect();

        $enrichedPredictions = $this->eloRepo->enrichPredictionsWithGameData($eloPredictions, $schedules);
        $sortedPredictions = $this->gameEnrichmentService->sortAndGroupPredictions($enrichedPredictions);

        // Example of adding team predictions
        $teamAbv = $request->input('teamAbv', null);
        $teamPrediction = $teamAbv
            ? $this->eloRepo->getTeamPrediction($teamAbv, $week)
            : null;

        return view('nfl.elo.index', [
            'eloPredictions' => $sortedPredictions,
            'weeks' => $this->eloRepo->getDistinctWeeks(),
            'week' => $week,
            'gameTime' => $schedules->pluck('game_time'),
            'nflBettingOdds' => $odds,
            'teamPrediction' => $teamPrediction,
        ]);
    }

    public function getTeamPrediction(Request $request)
    {
        $teamAbv = $request->input('teamAbv');
        $week = $request->input('week');
        $includeStats = $request->boolean('includeStats', false);
        $includeFactors = $request->boolean('includeFactors', false);

        $response = $this->eloRepo->getTeamPrediction(
            teamAbv: $teamAbv,
            week: $week,
            includeStats: $includeStats,
            includeFactors: $includeFactors
        );

        if (!$response['found']) {
            return redirect()->back()->with('error', $response['message']);
        }

        return view('nfl.elo.team_prediction', [
            'teamPrediction' => $response['prediction'],
            'summary' => $response['summary']
        ]);
    }

    public function show(string $gameId)
    {
        // Fetch the schedule for the specified game
        $schedule = $this->scheduleRepo->findByGameId($gameId) ?? $this->getDefaultSchedule();
        $odds = $this->oddsRepo->findByGameId($gameId);
        $predictions = $this->eloRepo->getPredictions(null)->where('game_id', $gameId);

        if ($predictions->isEmpty()) {
            return redirect()->back()->with('error', 'No predictions available for this game.');
        }

        if ($odds) {
            $schedule = $this->gameEnrichmentService->enrichWithBettingData($schedule, $odds);
        }

        // Fetch recent games for both teams
        $homeTeamLastGames = $this->getTeamRecentGames($schedule->home_team_id, $gameId);
        $awayTeamLastGames = $this->getTeamRecentGames($schedule->away_team_id, $gameId);

        // Fetch injuries for both teams
        $homeTeamInjuries = $schedule->home_team_id ? $this->playerRepo->getTeamInjuries($schedule->home_team_id) : collect();
        $awayTeamInjuries = $schedule->away_team_id ? $this->playerRepo->getTeamInjuries($schedule->away_team_id) : collect();

        // Fetch team-specific prediction for the event
        $teamAbv = $schedule->home_team; // Default to home team abbreviation
        $week = $schedule->week; // Extract week from schedule

        $response = $this->eloRepo->getTeamPrediction(
            teamAbv: $teamAbv,
            week: $week,
            includeStats: true,
            includeFactors: true
        );

        $teamPrediction = $response['found'] ? $response['prediction'] : null;
        $summary = $response['summary'] ?? null;

        return view('nfl.elo.show', [
            'predictions' => $predictions,
            'teamSchedule' => $schedule,
            'homeTeamLastGames' => $homeTeamLastGames,
            'awayTeamLastGames' => $awayTeamLastGames,
            'homeTeamInjuries' => $homeTeamInjuries,
            'awayTeamInjuries' => $awayTeamInjuries,
            'bettingOdds' => $odds,
            'homeTeamId' => $schedule->home_team_id,
            'awayTeamId' => $schedule->away_team_id,
            'totalPoints' => $schedule->totalPoints ?? 'N/A',
            'overUnderResult' => $schedule->overUnderResult ?? 'N/A',
            'totalOver' => $schedule->totalOver ?? 'N/A',
            'totalUnder' => $schedule->totalUnder ?? 'N/A',
            'teamPrediction' => $teamPrediction,
            'summary' => $summary,
        ]);
    }

    private function getDefaultSchedule(): stdClass
    {
        return (object)[
            'home_pts' => null,
            'away_pts' => null,
            'home_team_id' => null,
            'away_team_id' => null,
            'totalPoints' => 'N/A',
            'overUnderResult' => 'N/A',
            'totalOver' => null,
            'totalUnder' => null
        ];
    }

    private function getTeamRecentGames(?int $teamId, string $currentGameId): Collection
    {
        if (!$teamId) return collect();

        $games = $this->scheduleRepo->getTeamLast3Games($teamId, $currentGameId);
        return $games->map(fn($game) => $this->gameEnrichmentService->enrichGame($game, $teamId));
    }

    public function showTable(Request $request)
    {
        $currentWeek = config('nfl.current_week'); // Load the current week from configuration
        $week = $request->input('week', $currentWeek); // Default to the current week if none is provided
        $year = 2024;

        // Fetch predictions and related data
        $eloPredictions = $this->eloRepo->getPredictions($week)->where('year', $year);
        $gameIds = $eloPredictions->pluck('game_id');
        $odds = $this->oddsRepo->findByEventIds($gameIds);
        $schedules = $this->scheduleRepo->findByGameIds($gameIds) ?? collect();

        // Enrich predictions with schedule and game data
        $enrichedPredictions = $eloPredictions->map(function ($prediction) use ($schedules) {
            $schedule = $schedules->firstWhere('game_id', $prediction->game_id);

            $prediction->gameTime = $schedule->game_time ?? null;
            $prediction->homePts = $schedule->home_pts ?? null;
            $prediction->awayPts = $schedule->away_pts ?? null;
            $prediction->gameStatusDetail = $schedule->status_type_detail ?? 'Scheduled';
            $prediction->homeTeam = $schedule->home_team ?? null;
            $prediction->homeResult = $schedule->home_result ?? null;

            return $prediction;
        });

        // Sort and group predictions
        $sortedPredictions = $this->gameEnrichmentService->sortAndGroupPredictions($enrichedPredictions);

        // Pass data to the view
        return view('nfl.elo.table', [
            'eloPredictions' => $sortedPredictions,
            'weeks' => $this->eloRepo->getDistinctWeeks(),
            'week' => $week, // Pass the resolved week to the view
            'nflBettingOdds' => $odds,
        ]);
    }

}