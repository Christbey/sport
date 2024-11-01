<?php

namespace App\Providers;

use App\Models\Nfl\NflTeamSchedule;
use App\Observers\NflTeamScheduleObserver;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('files', function ($app) {
            return new Filesystem;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        NflTeamSchedule::observe(NflTeamScheduleObserver::class);

    }
}
