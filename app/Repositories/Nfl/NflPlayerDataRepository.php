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
        'team',

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

    public function getTeamInjuries(string $teamFilter): Collection
    {
        return NflPlayerData::where('team', $teamFilter)
            ->where(function ($query) {
                $query->whereNull('injury_return_date')
                    ->orWhere('injury_return_date', '>', Carbon::today());
            })
            ->get(self::DEFAULT_COLUMNS);
    }

    public function findPlayersByTeam(?string $teamFilter = null): Collection
    {
        return NflPlayerData::when($teamFilter, function ($query, $teamFilter) {
            $query->where('team', $teamFilter);
        })
            ->limit(50)
            ->get(self::DEFAULT_COLUMNS);
    }

    public function findByEspnName(string $espnName): ?object
    {
        return NflPlayerData::whereRaw('LOWER(espnName) = ?', [strtolower($espnName)])
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


    public function findByExperience(int $years, ?string $teamFilter = null): Collection
    {
        $query = NflPlayerData::where('exp', $years);

        // Apply the optional team filter if provided
        if (!is_null($teamFilter)) {
            $query->where('team', $teamFilter);
        }

        return $query->get(self::DEFAULT_COLUMNS);
    }


    public function findByAgeRange(?int $minAge = null, ?int $maxAge = null, ?string $teamFilter = null): Collection
    {
        return NflPlayerData::when($minAge, function ($query, $minAge) {
            $query->where('age', '>=', $minAge);
        })
            ->when($maxAge, function ($query, $maxAge) {
                $query->where('age', '<=', $maxAge);
            })
            ->when($teamFilter, function ($query, $teamFilter) {
                $query->where('team', $teamFilter);
            })
            ->get(self::DEFAULT_COLUMNS);
    }

    public function findBySchool(string $school): Collection
    {
        return NflPlayerData::where('school', $school)
            ->get(self::DEFAULT_COLUMNS);
    }

    public function getPlayerData(
        ?string $teamFilter = null,
        ?string $espnName = null,
        ?string $designation = null,
        ?string $position = null,
        ?int    $years = null,
        ?string $school = null,
        ?int    $minAge = null,
        ?int    $maxAge = null
    ): Collection
    {
        return NflPlayerData::when($teamFilter, function ($query, $teamFilter) {
            $query->where('team', $teamFilter);
        })
            ->when($espnName, function ($query, $espnName) {
                $query->whereRaw('LOWER(espnName) = ?', [strtolower($espnName)]);
            })
            ->when($designation, function ($query, $designation) {
                $query->where('injury_designation', $designation);
            })
            ->when($position, function ($query, $position) {
                $query->where('pos', $position);
            })
            ->when($years, function ($query, $years) {
                $query->where('exp', $years);
            })
            ->when($school, function ($query, $school) {
                $query->where('school', $school);
            })
            ->when($minAge, function ($query, $minAge) {
                $query->where('age', '>=', $minAge);
            })
            ->when($maxAge, function ($query, $maxAge) {
                $query->where('age', '<=', $maxAge);
            })
            ->get(self::DEFAULT_COLUMNS);
    }

    public function findByTeam(string $teamId): Collection
    {
        return NflPlayerData::where('teamID', $teamId)
            ->get(self::DEFAULT_COLUMNS);
    }

    public function findByPlayerId(string $playerId): ?object
    {
        // TODO: Implement findByPlayerId() method.
        return null;
    }

    public function getTeamRoster(string $teamFilter): Collection
    {
        return NflPlayerData::where('team', $teamFilter)
            ->get('espnName');
    }

    public function getTeamQBs(string $teamFilter): Collection
    {
        return NflPlayerData::where('team', $teamFilter)
            ->where('pos', 'QB')
            ->get(self::DEFAULT_COLUMNS);
    }
}
