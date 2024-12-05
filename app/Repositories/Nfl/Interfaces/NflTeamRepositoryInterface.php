<?php

namespace App\Repositories\Nfl\Interfaces;

use Illuminate\Support\Collection;

interface NflTeamRepositoryInterface
{
    public function all(): Collection;

    public function findById(int $id): ?array;

    public function findByAbbreviation(string $teamAbv): ?array;

    public function create(array $data): array;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;
}
