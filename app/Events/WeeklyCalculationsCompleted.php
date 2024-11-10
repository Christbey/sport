<?php
// app/Events/WeeklyCalculationsCompleted.php
namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WeeklyCalculationsCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public array $gamesData,
        public int   $totalGames,
        public int   $correctPredictions,
        public float $percentage
    )
    {
    }
}