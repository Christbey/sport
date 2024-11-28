<?php

namespace App\Http\Controllers\Nfl;

use App\Http\Controllers\Controller;
use App\Repositories\{Nfl\NflBettingOddsRepository,
    Nfl\NflEloPredictionRepository,
    Nfl\NflPlayerDataRepository,
    NflTeamScheduleRepository};
use Illuminate\Http\Request;
use stdClass;

class NflEloRatingController extends Controller
{
    protected NflPlayerDataRepository $playerRepo;
    protected NflTeamScheduleRepository $scheduleRepo;
    protected NflBettingOddsRepository $oddsRepo;
    protected NflEloPredictionRepository $eloRepo;

    public function __construct(
        NflEloPredictionRepository $eloRepo,
        NflBettingOddsRepository   $oddsRepo,
        NflTeamScheduleRepository  $scheduleRepo,
        NflPlayerDataRepository    $playerRepo
    )
    {
        $this->eloRepo = $eloRepo;
        $this->oddsRepo = $oddsRepo;
        $this->scheduleRepo = $scheduleRepo;
        $this->playerRepo = $playerRepo;
    }

    public function prediction(Request $request)
    {
        $week = $request->input('week');

        // Fetch predictions and weeks
        $eloPredictions = $this->eloRepo->getPredictions($week);
        $weeks = $this->eloRepo->getDistinctWeeks();
        $gameIds = $eloPredictions->pluck('game_id');

        // Fetch betting odds and schedules
        $nflBettingOdds = $this->oddsRepo->getOddsByEventIds($gameIds);
        $teamSchedules = $this->scheduleRepo->getSchedulesByGameIds($gameIds);

        // Enrich predictions with game data
        $eloPredictions = $this->eloRepo->enrichPredictionsWithGameData($eloPredictions, $teamSchedules);

        return view('nfl.elo.index', compact('eloPredictions', 'weeks', 'week', 'nflBettingOdds'));
    }

    public function show(string $gameId)
    {
        // Fetch schedule, betting odds, and predictions
        $teamSchedule = $this->getTeamSchedule($gameId);
        $bettingOdds = $this->oddsRepo->getOddsByGameId($gameId);
        $predictions = $this->eloRepo->getPredictions(null)->where('game_id', $gameId);

        if ($predictions->isEmpty()) {
            return redirect()->back()->with('error', 'No predictions available for this game.');
        }

        // Enrich game data if betting odds are available
        if ($bettingOdds) {
            $teamSchedule = $this->enrichGameWithBettingData($teamSchedule, $bettingOdds);
        }

        $homeTeamId = $teamSchedule->home_team_id ?? null;
        $awayTeamId = $teamSchedule->away_team_id ?? null;

        // Fetch recent games and injuries for both teams
        $homeTeamLastGames = $this->getRecentGamesWithBettingData($homeTeamId, $teamSchedule->game_id);
        $awayTeamLastGames = $this->getRecentGamesWithBettingData($awayTeamId, $teamSchedule->game_id);

        $homeTeamInjuries = $homeTeamId ? $this->playerRepo->getTeamInjuries($homeTeamId) : collect();
        $awayTeamInjuries = $awayTeamId ? $this->playerRepo->getTeamInjuries($awayTeamId) : collect();

        // Pass all required variables to the view
        return view('nfl.elo.show', [
            'predictions' => $predictions,
            'teamSchedule' => $teamSchedule,
            'homeTeamLastGames' => $homeTeamLastGames,
            'awayTeamLastGames' => $awayTeamLastGames,
            'homeTeamInjuries' => $homeTeamInjuries,
            'awayTeamInjuries' => $awayTeamInjuries,
            'bettingOdds' => $bettingOdds,
            'totalOver' => $teamSchedule->totalOver ?? 'N/A',
            'totalUnder' => $teamSchedule->totalUnder ?? 'N/A',
            'totalPoints' => $teamSchedule->totalPoints ?? 'N/A',
            'overUnderResult' => $teamSchedule->overUnderResult ?? 'N/A',
            'homeTeamId' => $homeTeamId,
            'awayTeamId' => $awayTeamId,
        ]);
    }

    /**
     * Fetch the team schedule or return an empty object with default values.
     */
    private function getTeamSchedule(string $gameId)
    {
        $teamSchedule = $this->scheduleRepo->getSchedulesByGameIds(collect([$gameId]))->first();

        if (!$teamSchedule) {
            $teamSchedule = new stdClass();
            $teamSchedule->home_pts = null;
            $teamSchedule->away_pts = null;
            $teamSchedule->home_team_id = null;
            $teamSchedule->away_team_id = null;
            $teamSchedule->totalPoints = 'N/A';
            $teamSchedule->overUnderResult = 'N/A';
            $teamSchedule->totalOver = null;
            $teamSchedule->totalUnder = null;
        }

        return $teamSchedule;
    }

    /**
     * Enrich a game with betting data, like over/under results.
     */
    private function enrichGameWithBettingData($teamSchedule, $bettingOdds)
    {
        if (!isset($teamSchedule->home_pts, $teamSchedule->away_pts)) {
            $teamSchedule->totalPoints = null;
            $teamSchedule->overUnderResult = 'N/A';
            $teamSchedule->totalOver = $bettingOdds->total_over ?? null;
            $teamSchedule->totalUnder = $bettingOdds->total_under ?? null;
        } else {
            $totalPoints = $teamSchedule->home_pts + $teamSchedule->away_pts;
            $teamSchedule->totalPoints = $totalPoints;
            $teamSchedule->overUnderResult = match (true) {
                $totalPoints > $bettingOdds->total_over => 'Over',
                $totalPoints < $bettingOdds->total_over => 'Under',
                default => 'Push',
            };
            $teamSchedule->totalOver = $bettingOdds->total_over;
            $teamSchedule->totalUnder = $bettingOdds->total_under;
        }

        return $teamSchedule;
    }

    /**
     * Fetch recent games for a team and enrich them with betting data.
     */
    private function getRecentGamesWithBettingData(?int $teamId, string $currentGameId)
    {
        if (!$teamId) {
            return collect();
        }

        $recentGames = $this->scheduleRepo->getTeamLast3Games($teamId, $currentGameId);

        return $recentGames->map(function ($game) use ($teamId) {
            $bettingOdds = $this->oddsRepo->getOddsByGameId($game->game_id);
            if ($bettingOdds) {
                $game = $this->enrichGameWithBettingData($game, $bettingOdds);
            }
            $game->marginOfVictory = $this->calculateMarginOfVictory($game, $game->home_team_id === $teamId);

            return $game;
        });
    }

    /**
     * Calculate the margin of victory for a game.
     */
    private function calculateMarginOfVictory($game, bool $isHomeTeam)
    {
        if (!isset($game->home_pts, $game->away_pts)) {
            return null;
        }

        return $isHomeTeam
            ? $game->home_pts - $game->away_pts
            : $game->away_pts - $game->home_pts;
    }
}
