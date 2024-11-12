<?php

namespace App\Events\Nfl;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class CalculateTeamEloEvent
{
    use Dispatchable, SerializesModels;

    public string $team;
    public int $year;
    public array $weeks;
    public Carbon $today;

    /**
     * Create a new event instance.
     *
     * @param string $team
     * @param int $year
     * @param array $weeks
     * @param Carbon $today
     */
    public function __construct(string $team, int $year, array $weeks, Carbon $today)
    {
        $this->team = $team;
        $this->year = $year;
        $this->weeks = $weeks;
        $this->today = $today;
    }
}
