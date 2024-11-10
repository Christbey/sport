<?php
// app/Listeners/SendWeeklyCalculationsNotification.php
namespace App\Listeners;

use App\Events\WeeklyCalculationsCompleted;
use App\Notifications\GameCalculationsNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendWeeklyCalculationsNotification implements ShouldQueue
{
    public function handle(WeeklyCalculationsCompleted $event): void
    {
        sleep(2); // Rate limiting protection

        Notification::route('discord', config('services.discord.channel_id'))
            ->notify(new GameCalculationsNotification(
                $event->gamesData,
                [
                    'totalGames' => $event->totalGames,
                    'correctPredictions' => $event->correctPredictions,
                    'percentage' => $event->percentage
                ]
            ));
    }
}