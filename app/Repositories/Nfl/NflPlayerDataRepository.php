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

    public function findByAgeRange(int $minAge, int $maxAge): Collection
    {
        return NflPlayerData::whereBetween('age', [$minAge, $maxAge])
            ->get(self::DEFAULT_COLUMNS);
    }

    public function findBySchool(string $school): Collection
    {
        return NflPlayerData::where('school', $school)
            ->get(self::DEFAULT_COLUMNS);
    }

    
}