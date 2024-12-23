<?php

namespace App\Repositories\Nfl\Interfaces;

use Illuminate\Support\Collection;

interface NflEloPredictionRepositoryInterface
{
    public function getPredictions(?int $week): Collection;

    public function getDistinctWeeks(): Collection;

    public function enrichPredictionsWithGameData(Collection $predictions, Collection $schedules): Collection;

    public function findByGameId(string $gameId): ?object;

    public function findByTeam(
        ?string $teamAbv = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $opponent = null,
        ?int    $week = null
    ): Collection;


    public function findByDateRange(string $startDate, string $endDate): Collection;

    public function findByWeek(int $week): Collection;

    public function hasUpdatedToday(): bool;
}