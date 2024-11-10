<?php

// app/Events/GameResultsProcessed.php
namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameResultsProcessed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public array  $updatedGames,
        public string $gameWeek
    )
    {
    }
}