<?php

// app/Listeners/LogGameCalculationsStart.php
namespace App\Listeners;

use App\Events\GameCalculationsStarted;
use Illuminate\Support\Facades\Log;

class LogGameCalculationsStart
{
    public function handle(GameCalculationsStarted $event): void
    {
        Log::info('Starting game calculations', [
            'start_date' => $event->dateRange['start'],
            'end_date' => $event->dateRange['end']
        ]);
    }
}
