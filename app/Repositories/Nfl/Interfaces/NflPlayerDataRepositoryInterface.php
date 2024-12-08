<?php


namespace App\Repositories\Nfl\Interfaces;

use Illuminate\Support\Collection;

interface NflPlayerDataRepositoryInterface
{
    public function getTeamInjuries(string $teamFilter): Collection;

    public function findByTeam(string $teamId): Collection;

    public function findByPlayerId(string $playerId): ?object;

    public function findByInjuryStatus(string $designation): Collection;

    public function findByPosition(string $position): Collection;

    public function getFreeAgents(): Collection;

    public function findByExperience(int $years): Collection;

    public function findByAgeRange(int $minAge, int $maxAge): Collection;

    public function findBySchool(string $school): Collection;
}