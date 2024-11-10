<?php
// app/Listeners/SendPicksSubmittedNotifications.php
namespace App\Listeners;

use App\Events\PicksSubmitted;
use App\Notifications\PicksSubmittedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPicksSubmittedNotifications implements ShouldQueue
{
    public function handle(PicksSubmitted $event): void
    {
        $event->user->notify(new PicksSubmittedNotification(
            $event->userPicks,
            $event->gameWeek
        ));
    }
}
