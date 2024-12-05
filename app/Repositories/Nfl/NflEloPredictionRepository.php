<?php

namespace App\Repositories\Nfl;

use App\Models\Nfl\NflEloPrediction;
use App\Repositories\Nfl\Interfaces\NflEloPredictionRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NflEloPredictionRepository implements NflEloPredictionRepositoryInterface
{
    private const DEFAULT_COLUMNS = [
        'game_id',
        'team',
        'opponent',
        'week',
        'team_elo',
        'opponent_elo',
        'expected_outcome',
        'predicted_spread'
    ];

    public function getPredictions(?int $week): Collection
    {
        return NflEloPrediction::query()
            ->when($week, fn($query) => $query->where('week', $week))
            ->orderBy('game_id')
            ->get();
    }

    public function getDistinctWeeks(): Collection
    {
        return NflEloPrediction::distinct()
            ->orderBy(DB::raw('CAST(week AS SIGNED)'))
            ->pluck('week');
    }

    public function enrichPredictionsWithGameData(Collection $predictions, Collection $schedules): Collection
    {
        return $predictions->map(function ($prediction) use ($schedules) {
            $game = $schedules->get($prediction->game_id);
            if (!$game) return $prediction;

            $prediction->homePts = $game->home_pts;
            $prediction->awayPts = $game->away_pts;
            $prediction->gameStatus = $game->game_status;
            $prediction->gameStatusDetail = $game->status_type_detail;

            $this->calculatePredictionAccuracy($prediction, $game);
            return $prediction;
        });
    }

    private function calculatePredictionAccuracy($prediction, $game): void
    {
        if (!isset($game->home_pts, $game->away_pts)) {
            $prediction->wasCorrect = null;
            return;
        }

        $actualSpread = $game->home_pts - $game->away_pts;
        $predictedSpread = $prediction->predicted_spread;

        $prediction->wasCorrect = ($predictedSpread > 0 && $actualSpread > $predictedSpread) ||
            ($predictedSpread < 0 && $actualSpread < $predictedSpread);
    }

    public function findByGameId(string $gameId): ?object
    {
        return NflEloPrediction::where('game_id', $gameId)
            ->select(self::DEFAULT_COLUMNS)
            ->first();
    }

    public function findByTeam(string $teamId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = NflEloPrediction::where(function ($q) use ($teamId) {
            $q->where('home_team_id', $teamId)
                ->orWhere('away_team_id', $teamId);
        });

        if ($startDate && $endDate) {
            $query->whereBetween('game_date', [$startDate, $endDate]);
        }

        return $query->select(self::DEFAULT_COLUMNS)->get();
    }

    public function findByDateRange(string $startDate, string $endDate): Collection
    {
        return NflEloPrediction::whereBetween('game_date', [$startDate, $endDate])
            ->select(self::DEFAULT_COLUMNS)
            ->get();
    }

    public function findByWeek(int $week): Collection
    {
        return NflEloPrediction::where('week', $week)
            ->select(self::DEFAULT_COLUMNS)
            ->get();
    }

    public function hasUpdatedToday(): bool
    {
        return NflEloPrediction::whereDate('created_at', today())->exists();
    }
}