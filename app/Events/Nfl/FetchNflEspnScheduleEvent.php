<?php

namespace App\Events\Nfl;

class FetchNflEspnScheduleEvent
{
    public $seasonYear;
    public $seasonType;
    public $weekNumber;

    public function __construct(int $seasonYear, int $seasonType, $weekNumber)
    {
        $this->seasonYear = $seasonYear;
        $this->seasonType = $seasonType;
        $this->weekNumber = $weekNumber;
    }
}
