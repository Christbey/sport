<?php

namespace App\Repositories\Nfl;

use App\Models\Nfl\NflEloPrediction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NflEloPredictionRepository
{
    public function getPredictions(?string $week): Collection
    {
        return NflEloPrediction::query()
            ->when($week, fn($query) => $query->where('week', $week))
            ->orderBy('team')
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
            if ($game) {
                $prediction->homePts = $game->home_pts;
                $prediction->awayPts = $game->away_pts;
                $prediction->gameStatus = $game->game_status;
                $prediction->gameStatusDetail = $game->status_type_detail;
                $this->calculatePredictionAccuracy($prediction, $game);

            }
            return $prediction;
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


}
