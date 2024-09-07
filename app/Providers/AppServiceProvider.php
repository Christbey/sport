<?php

namespace App\Providers;

use App\Models\NflTeamSchedule;
use App\Observers\NflTeamScheduleObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        NflTeamSchedule::observe(NflTeamScheduleObserver::class);
    }
}
