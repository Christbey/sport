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

        return view('nfl.elo.index', [
            'eloPredictions' => $sortedPredictions,
            'weeks' => $this->eloRepo->getDistinctWeeks(),
            'week' => $week,
            'gameTime' => $schedules->pluck('game_time'),
            'nflBettingOdds' => $odds
        ]);
    }

    public function show(string $gameId)
    {
        $schedule = $this->scheduleRepo->findByGameId($gameId) ?? $this->getDefaultSchedule();
        $odds = $this->oddsRepo->findByGameId($gameId);
        $predictions = $this->eloRepo->getPredictions(null)->where('game_id', $gameId);

        if ($predictions->isEmpty()) {
            return redirect()->back()->with('error', 'No predictions available for this game.');
        }

        if ($odds) {
            $schedule = $this->gameEnrichmentService->enrichWithBettingData($schedule, $odds);
        }

        $homeTeamLastGames = $this->getTeamRecentGames($schedule->home_team_id, $gameId);
        $awayTeamLastGames = $this->getTeamRecentGames($schedule->away_team_id, $gameId);

        $homeTeamInjuries = $schedule->home_team_id ? $this->playerRepo->getTeamInjuries($schedule->home_team_id) : collect();
        $awayTeamInjuries = $schedule->away_team_id ? $this->playerRepo->getTeamInjuries($schedule->away_team_id) : collect();

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
            'totalUnder' => $schedule->totalUnder ?? 'N/A'
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
}