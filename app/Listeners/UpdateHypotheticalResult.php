<?php

// app/Listeners/UpdateHypotheticalResult.php
namespace App\Listeners;

use App\Events\GameResultProcessed;

class UpdateHypotheticalResult
{
    public function handle(GameResultProcessed $event): void
    {
        $event->game->hypothetical->update([
            'correct' => $event->wasCorrect ? 1 : 0
        ]);
    }
}