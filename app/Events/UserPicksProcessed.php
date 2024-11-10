<?php
// app/Events/UserPicksProcessed.php
namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserPicksProcessed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User   $user,
        public array  $weeklyResults,
        public string $gameWeek,
        public array  $weeklyStats,
        public array  $overallStats
    )
    {
    }
}