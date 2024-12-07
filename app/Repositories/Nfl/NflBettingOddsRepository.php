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

    public function getOddsByWeek(int $week, ?float $spread = null, ?float $total = null): Collection
    {
        // Retrieve the week configuration from the NFL config
        $weekConfig = config('nfl.weeks.' . $week);


        $startDate = $weekConfig['start'];
        $endDate = $weekConfig['end'];

        // Build the query
        $query = NflBettingOdds::whereBetween('game_date', [$startDate, $endDate]);

        if ($spread) {
            $query->where(function ($q) use ($spread) {
                $q->where('spread_home', $spread)
                    ->orWhere('spread_away', $spread);
            });
        }

        if ($total) {
            $query->where(function ($q) use ($total) {
                $q->where('total_over', $total)
                    ->orWhere('total_under', $total);
            });
        }

        return $query->select([
            'event_id',
            'game_date',
            'home_team',
            'away_team',
            'moneyline_home',
            'moneyline_away',
            'spread_home',
            'spread_away',
            'total_over',
            'total_under',
            'implied_total_home',
            'implied_total_away',
        ])->get();
    }

    public function getOddsByTeam(string $teamFilter): Collection
    {
        return NflBettingOdds::where(function ($query) use ($teamFilter) {
            $query->where('home_team', $teamFilter)
                ->orWhere('away_team', $teamFilter);
        })
            ->select([
                'event_id',
                'game_date',
                'home_team',
                'away_team',
                'moneyline_home',
                'moneyline_away',
                'spread_home',
                'spread_away',
                'total_over',
                'total_under',
                'implied_total_home',
                'implied_total_away',
            ])
            ->get();
    }

    public function getOddsByDateRange(string $startDate, string $endDate): Collection
    {
        return NflBettingOdds::whereBetween('game_date', [$startDate, $endDate])
            ->select([
                'event_id',
                'game_date',
                'home_team',
                'away_team',
                'moneyline_home',
                'moneyline_away',
                'spread_home',
                'spread_away',
                'total_over',
                'total_under',
                'implied_total_home',
                'implied_total_away',
            ])
            ->get();
    }

    public function getOddsByMoneyline(float $moneyline): Collection
    {
        return NflBettingOdds::where(function ($query) use ($moneyline) {
            $query->where('moneyline_home', $moneyline)
                ->orWhere('moneyline_away', $moneyline);
        })
            ->select([
                'event_id',
                'game_date',
                'home_team',
                'away_team',
                'moneyline_home',
                'moneyline_away',
                'spread_home',
                'spread_away',
                'total_over',
                'total_under',
                'implied_total_home',
                'implied_total_away',
            ])
            ->get();
    }


}