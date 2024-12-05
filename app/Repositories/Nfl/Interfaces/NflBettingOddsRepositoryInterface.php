<?php

namespace App\Repositories\Nfl\Interfaces;

use App\Models\Nfl\NflBettingOdds;
use Illuminate\Support\Collection;

interface NflBettingOddsRepositoryInterface
{
    public function findByEventIds(Collection $eventIds): Collection;

    public function findByGameId(string $gameId): ?NflBettingOdds;

    public function findByDateRange(string $startDate, string $endDate): Collection;

    public function findByTeam(string $teamId, ?string $startDate = null, ?string $endDate = null): Collection;

    public function findByGameDate(string $gameDate): Collection;

    public function findByTeamAndSeason(string $teamId, int $season): Collection;
}