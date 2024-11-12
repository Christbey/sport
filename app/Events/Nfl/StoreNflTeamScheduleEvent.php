<?php

namespace App\Events\Nfl;

class StoreNflTeamScheduleEvent
{
    public $teamAbv;
    public $season;

    public function __construct($teamAbv, $season)
    {
        $this->teamAbv = $teamAbv;
        $this->season = $season;
    }
}
