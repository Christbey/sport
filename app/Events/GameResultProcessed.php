<?php
// app/Events/GameResultProcessed.php
namespace App\Events;

use App\Models\CollegeFootball\CollegeFootballGame;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameResultProcessed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CollegeFootballGame $game,
        public bool                $wasCorrect,
        public float               $hypotheticalSpread,
        public float               $actualDifference
    )
    {
    }
}