<?php
// app/Listeners/SendUserPicksNotifications.php
namespace App\Listeners;

use App\Events\UserPicksProcessed;
use App\Notifications\PicksResultsNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendUserPicksNotifications implements ShouldQueue
{
    public function handle(UserPicksProcessed $event): void
    {
        $event->user->notify(new PicksResultsNotification(
            $event->weeklyResults,
            $event->gameWeek,
            $event->weeklyStats,
            $event->overallStats
        ));
    }
}