<?php

namespace App\Http\Controllers\Nfl;

use App\Http\Controllers\Controller;
use App\Models\Nfl\{NflBettingOdds, NflEloPrediction, NflPlayerData, NflTeamSchedule};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class NflEloRatingController extends Controller
{
    public function prediction(Request $request)
    {
        $week = $request->input('week');

        // Get predictions with optional week filter
        $eloPredictions = $this->getEloPredictions($week);

        // Get all unique weeks for dropdown
        $weeks = NflEloPrediction::distinct()
            ->orderBy('week')
            ->pluck('week');

        // Get related data
        $nflBettingOdds = $this->getBettingOdds($eloPredictions->pluck('game_id'));
        $teamSchedules = $this->getTeamSchedules($eloPredictions->pluck('game_id'));

        // Enrich predictions with game data
        $this->enrichPredictionsWithGameData($eloPredictions, $teamSchedules);

        return view('nfl.elo_predictions', compact('eloPredictions', 'weeks', 'week', 'nflBettingOdds'));
    }

    private function getEloPredictions(?string $week): Collection
    {
        return NflEloPrediction::query()
            ->when($week, fn($query) => $query->where('week', $week))
            ->orderBy('team')
            ->get();
    }

    private function getBettingOdds($gameIds): Collection
    {
        return NflBettingOdds::whereIn('event_id', $gameIds)
            ->get()
            ->keyBy('event_id');
    }

    private function getTeamSchedules($gameIds): Collection
    {
        return NflTeamSchedule::whereIn('game_id', $gameIds)
            ->get()
            ->keyBy('game_id');
    }

    private function enrichPredictionsWithGameData(Collection $predictions, Collection $schedules): void
    {
        $predictions->each(function ($prediction) use ($schedules) {
            $game = $schedules[$prediction->game_id] ?? null;
            if (!$game) return;

            $prediction->homePts = $game->home_pts;
            $prediction->awayPts = $game->away_pts;
            $prediction->gameStatus = $game->game_status;
            $prediction->gameStatusDetail = $game->status_type_detail;

            $this->calculatePredictionAccuracy($prediction, $game);
        });
    }

    private function calculatePredictionAccuracy($prediction, $game): void
    {
        if (!isset($game->home_pts) || !isset($game->away_pts)) {
            $prediction->wasCorrect = null;
            return;
        }

        $actualSpread = $game->home_pts - $game->away_pts;
        $predictedSpread = $prediction->predicted_spread;

        $prediction->wasCorrect = ($predictedSpread > 0 && $actualSpread > $predictedSpread) ||
            ($predictedSpread < 0 && $actualSpread < $predictedSpread);
    }

    public function show(string $gameId)
    {
        // Fetch core game data
        $predictions = NflEloPrediction::where('game_id', $gameId)->get();
        $teamSchedule = NflTeamSchedule::where('game_id', $gameId)->first();

        if ($predictions->isEmpty() || !$teamSchedule) {
            return redirect()->back()->with('error', 'No stats available for this game.');
        }

        // Get team IDs
        $homeTeamId = $teamSchedule->home_team_id;
        $awayTeamId = $teamSchedule->away_team_id;

        // Get game data
        $bettingOdds = NflBettingOdds::where('event_id', $gameId)->first();
        $homeTeamInjuries = $this->getTeamInjuries($homeTeamId);
        $awayTeamInjuries = $this->getTeamInjuries($awayTeamId);
        $homeTeamLastGames = $this->getTeamLastGames($homeTeamId, $teamSchedule->game_id);
        $awayTeamLastGames = $this->getTeamLastGames($awayTeamId, $teamSchedule->game_id);

        // Calculate game results
        [$totalPoints, $overUnderResult] = $this->calculateGameResults($teamSchedule, $bettingOdds);

        return view('nfl.elo.show', compact(
            'predictions',
            'teamSchedule',
            'homeTeamLastGames',
            'awayTeamLastGames',
            'homeTeamInjuries',
            'awayTeamInjuries',
            'homeTeamId',
            'awayTeamId',
            'bettingOdds',
            'totalPoints',
            'overUnderResult'
        ));
    }

    private function getTeamInjuries(int $teamId): Collection
    {
        $today = Carbon::today();

        return NflPlayerData::where('teamiD', $teamId)
            ->where(function ($query) use ($today) {
                $query->whereNull('injury_return_date')
                    ->orWhere('injury_return_date', '>', $today);
            })
            ->get(['espnName', 'injury_description', 'injury_designation', 'injury_return_date']);
    }

    private function getTeamLastGames(int $teamId, string $currentGameId): Collection
    {
        return NflTeamSchedule::where(function ($query) use ($teamId) {
            $query->where('home_team_id', $teamId)
                ->orWhere('away_team_id', $teamId);
        })
            ->where('game_id', '<', $currentGameId)
            ->orderBy('game_date', 'desc')
            ->limit(3)
            ->get()
            ->map(fn($game) => $this->enrichGameWithStats($game, $teamId));
    }

    private function enrichGameWithStats($game, int $teamId)
    {
        $isHomeTeam = $game->home_team_id === $teamId;

        // Calculate Margin of Victory
        $game->marginOfVictory = $this->calculateMarginOfVictory($game, $isHomeTeam);

        // Calculate Over/Under
        $game->overUnderResult = $this->calculateOverUnderResult($game);

        return $game;
    }

    private function calculateMarginOfVictory($game, bool $isHomeTeam)
    {
        if (!isset($game->home_pts) || !isset($game->away_pts)) {
            return 'N/A';
        }

        return $isHomeTeam
            ? $game->home_pts - $game->away_pts
            : $game->away_pts - $game->home_pts;
    }

    private function calculateOverUnderResult($game)
    {
        if (!isset($game->home_pts) || !isset($game->away_pts)) {
            return 'N/A';
        }

        $bettingOdds = NflBettingOdds::where('event_id', $game->game_id)->first();
        if (!$bettingOdds?->total_over) {
            return 'N/A';
        }

        $totalPoints = $game->home_pts + $game->away_pts;

        if ($totalPoints > $bettingOdds->total_over) return 'Over';
        if ($totalPoints < $bettingOdds->total_over) return 'Under';
        return 'Push';
    }

    private function calculateGameResults($teamSchedule, $bettingOdds): array
    {
        $totalPoints = null;
        $overUnderResult = null;

        if (isset($teamSchedule->home_pts, $teamSchedule->away_pts)) {
            $totalPoints = $teamSchedule->home_pts + $teamSchedule->away_pts;

            if ($bettingOdds?->total_over) {
                if ($totalPoints > $bettingOdds->total_over) $overUnderResult = 'Over';
                elseif ($totalPoints < $bettingOdds->total_over) $overUnderResult = 'Under';
                else $overUnderResult = 'Push';
            }
        }

        return [$totalPoints, $overUnderResult];
    }
}