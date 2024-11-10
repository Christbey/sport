<?php

// app/Events/GameCalculationsStarted.php
namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameCalculationsStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public array $dateRange
    )
    {
    }
}
