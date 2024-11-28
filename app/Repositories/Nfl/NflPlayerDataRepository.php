<?php

namespace App\Repositories\Nfl;

use App\Models\Nfl\NflPlayerData;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class NflPlayerDataRepository
{
    public function getTeamInjuries(int $teamId): Collection
    {
        $today = Carbon::today();

        return NflPlayerData::where('teamId', $teamId)
            ->where(function ($query) use ($today) {
                $query->whereNull('injury_return_date')
                    ->orWhere('injury_return_date', '>', $today);
            })
            ->get(['espnName', 'injury_description', 'injury_designation', 'injury_return_date']);
    }
}
