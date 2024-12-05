<?php


namespace App\Repositories\Nfl;

use App\Models\Nfl\NflBettingOdds;
use App\Repositories\Nfl\Interfaces\NflBettingOddsRepositoryInterface;
use Illuminate\Support\Collection;

class NflBettingOddsRepository implements NflBettingOddsRepositoryInterface
{
    private const DEFAULT_COLUMNS = [
        'event_id',
        'game_date',
        'spread_home',
        'spread_away',
        'away_team_id',
        'home_team_id',
        'source',
        'total_over',
        'total_under',
        'moneyline_home',
        'moneyline_away',
        'implied_total_home',
        'implied_total_away',
    ];

    public function findByEventIds(Collection $eventIds): Collection
    {
        return NflBettingOdds::whereIn('event_id', $eventIds)
            ->select(self::DEFAULT_COLUMNS)
            ->get()
            ->keyBy('event_id');
    }


    public function findByGameId(string $gameId): ?NflBettingOdds
    {
        return NflBettingOdds::where('event_id', $gameId)
            ->select(self::DEFAULT_COLUMNS)
            ->first();
    }

    public function findByTeam(string $teamId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = NflBettingOdds::where(function ($q) use ($teamId) {
            $q->where('home_team_id', $teamId)
                ->orWhere('away_team_id', $teamId);
        });

        if ($startDate && $endDate) {
            $query->whereBetween('game_date', [$startDate, $endDate]);
        }

        return $query->select(self::DEFAULT_COLUMNS)->get();
    }

    public function findByGameDate(string $gameDate): Collection
    {
        return NflBettingOdds::whereDate('game_date', $gameDate)
            ->select(self::DEFAULT_COLUMNS)
            ->get();
    }

    public function findByTeamAndSeason(string $teamId, int $season): Collection
    {
        return NflBettingOdds::where(function ($q) use ($teamId) {
            $q->where('home_team_id', $teamId)
                ->orWhere('away_team_id', $teamId);
        })
            ->whereYear('game_date', $season)
            ->select(self::DEFAULT_COLUMNS)
            ->get();
    }

    public function findByDateRange(string $startDate, string $endDate): Collection
    {
        return NflBettingOdds::whereBetween('game_date', [$startDate, $endDate])
            ->select(self::DEFAULT_COLUMNS)
            ->get();
    }

    public function getOddsByEventIds(Collection $pluck)
    {
        return NflBettingOdds::whereIn('event_id', $pluck)
            ->select(self::DEFAULT_COLUMNS)
            ->get()
            ->keyBy('event_id');
    }


}