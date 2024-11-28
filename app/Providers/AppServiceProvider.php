<?php

namespace App\Providers;


use App\Repositories\NflTeamRepository;
use App\Repositories\NflTeamRepositoryInterface;
use App\Repositories\NflTeamScheduleRepository;
use App\Repositories\NflTeamScheduleRepositoryInterface;
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
        $this->app->bind(NflTeamRepositoryInterface::class, NflTeamRepository::class);
        $this->app->bind(NflTeamScheduleRepositoryInterface::class, NflTeamScheduleRepository::class);


    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {

    }
}
