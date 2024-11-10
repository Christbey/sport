<?php
// app/Providers/EventServiceProvider.php
namespace App\Providers;

use App\Events\PicksSubmitted;
use App\Listeners\SendPicksSubmittedNotifications;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PicksSubmitted::class => [
            SendPicksSubmittedNotifications::class,
        ],
    ];
}