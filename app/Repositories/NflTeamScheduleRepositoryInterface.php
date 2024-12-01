<?php

namespace App\Repositories;

use Illuminate\Support\Collection;

interface NflTeamScheduleRepositoryInterface
{
    public function getScheduleByTeam(string $teamId): array;

    public function getAllSchedules(): array;

    public function getScheduleByDateRange(string $teamId, array $range): array;

    public function getRecentGames(string $teamId, int $limit = 5): Collection;

    // New methods for API handling
    public function updateOrCreateFromRapidApi(array $gameData, string $season): void;


    public function findByGameId(string $gameId): ?object;


}
