<?php

namespace App\Repositories;

use App\Models\Nfl\NflTeam;
use App\Repositories\Nfl\Interfaces\NflTeamRepositoryInterface;
use Illuminate\Support\Collection;

class NflTeamRepository implements NflTeamRepositoryInterface
{
    protected $model;

    public function __construct(NflTeam $model)
    {
        $this->model = $model;
    }

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function findById(int $id): ?array
    {
        return $this->model->find($id)?->toArray();
    }

    public function findByAbbreviation(string $teamAbv): ?array
    {
        return $this->model->where('team_abv', $teamAbv)->first()?->toArray();
    }

    public function create(array $data): array
    {
        return $this->model->create($data)->toArray();
    }

    public function update(int $id, array $data): bool
    {
        $team = $this->model->find($id);

        if (!$team) {
            return false;
        }

        return $team->update($data);
    }

    public function delete(int $id): bool
    {
        $team = $this->model->find($id);

        if (!$team) {
            return false;
        }

        return $team->delete();
    }
}
