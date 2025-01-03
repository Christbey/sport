<?php
// app/Providers/EventServiceProvider.php
namespace App\Providers;

use App\Events\BoxScoreFetched;
use App\Events\GameCalculationsStarted;
use App\Events\GameResultProcessed;
use App\Events\GameResultsProcessed;
use App\Events\MessageSent;
use App\Events\Nfl\CalculateTeamEloEvent;
use App\Events\OpenAIResponseReceived;
use App\Events\PicksSubmitted;
use App\Events\UserPicksProcessed;
use App\Events\WeeklyCalculationsCompleted;
use App\Listeners\HandleOpenAIResponse;
use App\Listeners\LogGameCalculationsStart;
use App\Listeners\Nfl\CalculateTeamEloListener;
use App\Listeners\ProcessUserPickResults;
use App\Listeners\SendPicksSubmittedNotifications;
use App\Listeners\SendUserPicksNotifications;
use App\Listeners\SendWeeklyCalculationsNotification;
use App\Listeners\StoreMessageInDatabase;
use App\Listeners\StorePlayerStats;
use App\Listeners\StoreTeamStats;
use App\Listeners\UpdateHypotheticalResult;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PicksSubmitted::class => [
            SendPicksSubmittedNotifications::class,
        ],
        GameResultsProcessed::class => [
            ProcessUserPickResults::class,
        ],
        UserPicksProcessed::class => [
            SendUserPicksNotifications::class,
        ],
        GameCalculationsStarted::class => [
            LogGameCalculationsStart::class,
        ],
        GameResultProcessed::class => [
            UpdateHypotheticalResult::class,
        ],
        WeeklyCalculationsCompleted::class => [
            SendWeeklyCalculationsNotification::class,
        ],
        BoxScoreFetched::class => [
            StorePlayerStats::class,
            StoreTeamStats::class,
        ],
        // New Events and Listeners
        CalculateTeamEloEvent::class => [
            CalculateTeamEloListener::class,
        ],

        OpenAIResponseReceived::class => [
            HandleOpenAIResponse::class,
        ],

        MessageSent::class => [
            StoreMessageInDatabase::class,
        ],

    ];
}