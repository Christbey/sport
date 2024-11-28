<?php

namespace App\Repositories\Nfl;

use App\Models\Nfl\NflBettingOdds;
use Illuminate\Support\Collection;

class NflBettingOddsRepository
{
    public function getOddsByEventIds(Collection $eventIds): Collection
    {
        return NflBettingOdds::whereIn('event_id', $eventIds)
            ->select([
                'event_id',
                'game_date',
                'spread_home',
                'spread_away',
                'total_over',
                'total_under',
                'moneyline_home',
                'moneyline_away',
                'implied_total_home',
                'implied_total_away',
            ])
            ->get()
            ->keyBy('event_id');
    }

    public function getOddsByGameId(string $gameId)
    {
        return NflBettingOdds::where('event_id', $gameId)
            ->select(['event_id', 'total_over', 'total_under', 'moneyline_home', 'moneyline_away'])
            ->first();
    }
}
