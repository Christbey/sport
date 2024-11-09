<?php

namespace App\DataTransferObjects;

class GameRatingsDTO
{
    public array $elo;
    public array $fpi;
    public array $sagarin;
    public array $advancedStats;
    public array $strengthOfSchedule;

    public function __construct(array $data)
    {
        $this->elo = $data['elo'];
        $this->fpi = $data['fpi'];
        $this->sagarin = $data['sagarin'];
        $this->advancedStats = $data['advancedStats'];
        $this->strengthOfSchedule = $data['strengthOfSchedule'];
    }
}