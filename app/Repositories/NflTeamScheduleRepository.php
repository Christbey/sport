<?php

namespace App\Repositories;

use App\Models\Nfl\NflTeamSchedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NflTeamScheduleRepository implements NflTeamScheduleRepositoryInterface
{
    public function getScheduleByTeam(string $teamId): array
    {
        return DB::table('nfl_team_schedules')
            ->where('home_team_id', $teamId)
            ->orWhere('away_team_id', $teamId)->get()
            ->toArray();
    }

    public function getAllSchedules(): array
    {
        return DB::table('nfl_team_schedules')
            ->get()
            ->toArray();
    }

    public function getScheduleByDateRange(string $teamId, array $range): array
    {
        return DB::table('nfl_team_schedules')
            ->where('team_id', $teamId)
            ->whereBetween('game_date', $range)
            ->get()
            ->toArray();
    }

    public function getRecentGames(string $teamId, int $limit = 5): Collection
    {
        return DB::table('nfl_team_schedules')
            ->where(function ($query) use ($teamId) {
                $query->where('home_team_id', $teamId)
                    ->orWhere('away_team_id', $teamId);
            })
            ->whereDate('game_date', '<', now()) // Past games only
            ->orderBy('game_date', 'desc') // Most recent first
            ->limit($limit) // Limit the number of games
            ->get(); // Remove `toArray()` to keep it as a collection
    }

    public function getSchedulesByGameIds(Collection $gameIds): Collection
    {
        return NflTeamSchedule::whereIn('game_id', $gameIds)->get()->keyBy('game_id');
    }

    public function getTeamLast3Games(int $teamId, string $currentGameId): Collection
    {
        return NflTeamSchedule::where(function ($query) use ($teamId) {
            $query->where('home_team_id', $teamId)
                ->orWhere('away_team_id', $teamId);
        })
            ->where('game_id', '<', $currentGameId)
            ->orderBy('game_date', 'desc')
            ->limit(3)
            ->get();
    }


}
