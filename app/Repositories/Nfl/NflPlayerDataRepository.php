<?php


namespace App\Repositories\Nfl;

use App\Models\Nfl\NflPlayerData;
use App\Repositories\Nfl\Interfaces\NflPlayerDataRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class NflPlayerDataRepository implements NflPlayerDataRepositoryInterface
{
    private const DEFAULT_COLUMNS = [
        'playerID',
        'espnName',
        'pos',
        'teamID',
        'injury_return_date',
        'injury_description',
        'injury_designation',
        'exp',
        'height',
        'weight',
        'age',
        'longName'
    ];

    public function getTeamInjuries(string $teamId): Collection
    {
        return NflPlayerData::where('teamID', $teamId)
            ->where(function ($query) {
                $query->whereNull('injury_return_date')
                    ->orWhere('injury_return_date', '>', Carbon::today());
            })
            ->get(self::DEFAULT_COLUMNS);
    }

    public function findPlayersByTeam(?string $teamId = null, ?string $teamFilter = null): Collection
    {
        $query = NflPlayerData::query();

        if ($teamId) {
            $query->where('team_id', $teamId);
        }

        if ($teamFilter) {
            $query->where('team', $teamFilter);
        }

        return $query->limit(5)->get(); // Limit the number of results to 50
    }


    public function findByTeam(string $teamId): Collection
    {
        return NflPlayerData::where('teamID', $teamId)
            ->get(self::DEFAULT_COLUMNS);
    }

    public function findByPlayerId(string $playerId): ?object
    {
        return NflPlayerData::where('playerID', $playerId)
            ->first(self::DEFAULT_COLUMNS);
    }

    public function findByInjuryStatus(string $designation): Collection
    {
        return NflPlayerData::where('injury_designation', $designation)
            ->get(self::DEFAULT_COLUMNS);
    }

    public function findByPosition(string $position): Collection
    {
        return NflPlayerData::where('pos', $position)
            ->get(self::DEFAULT_COLUMNS);
    }

    public function getFreeAgents(): Collection
    {
        return NflPlayerData::where('isFreeAgent', true)
            ->get(self::DEFAULT_COLUMNS);
    }

    public function findByExperience(int $years): Collection
    {
        return NflPlayerData::where('exp', $years)
            ->get(self::DEFAULT_COLUMNS);
    }

    public function findByAgeRange(?int $minAge = null, ?int $maxAge = null, ?string $teamFilter = null): Collection
    {
        $query = NflPlayerData::query();

        if (!is_null($minAge)) {
            $query->where('age', '>=', $minAge);
        }

        if (!is_null($maxAge)) {
            $query->where('age', '<=', $maxAge);
        }

        if (!is_null($teamFilter)) {
            $query->where('team_abv', $teamFilter);
        }

        return $query->get(self::DEFAULT_COLUMNS);
    }


    public function findBySchool(string $school): Collection
    {
        return NflPlayerData::where('school', $school)
            ->get(self::DEFAULT_COLUMNS);
    }

    public function getPlayerData(
        ?string $teamId = null,
        ?string $playerId = null,
        ?string $designation = null,
        ?string $position = null,
        ?int    $years = null,
        ?string $school = null,
        ?int    $minAge = null,
        ?int    $maxAge = null,
        ?string $teamFilter = null
    ): Collection
    {
        $query = NflPlayerData::query();

        if ($teamId) {
            $query->where('team_id', $teamId);
        }

        if ($playerId) {
            $query->where('player_id', $playerId);
        }

        if ($designation) {
            $query->where('injury_designation', $designation);
        }

        if ($position) {
            $query->where('position', $position);
        }

        if ($years) {
            $query->where('years_experience', $years);
        }

        if ($school) {
            $query->where('school', $school);
        }

        if ($minAge) {
            $query->where('age', '>=', $minAge);
        }

        if ($maxAge) {
            $query->where('age', '<=', $maxAge);
        }

        if ($teamFilter) {
            $query->where('team', $teamFilter);
        }

        return $query->get();
    }


}