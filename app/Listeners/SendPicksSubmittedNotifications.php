<?php
// app/Listeners/SendPicksSubmittedNotifications.php
namespace App\Listeners;

use App\Events\PicksSubmitted;
use App\Notifications\PicksSubmittedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendPicksSubmittedNotifications implements ShouldQueue
{
    public function handle(PicksSubmitted $event): void
    {
        Log::info('SendPicksSubmittedNotifications handling event', [
            'gameWeek' => $event->gameWeek,
            'user' => $event->user->id
        ]);

        $event->user->notify(new PicksSubmittedNotification(
            $event->userPicks,
            $event->gameWeek
        ));
    }
}
