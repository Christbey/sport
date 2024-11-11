<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BoxScoreFetched
{
    use Dispatchable, SerializesModels;

    public $gameID;
    public $boxScoreData;

    /**
     * Create a new event instance.
     *
     * @param string $gameID
     * @param array $boxScoreData
     */
    public function __construct(string $gameID, array $boxScoreData)
    {
        $this->gameID = $gameID;
        $this->boxScoreData = $boxScoreData;
    }
}
