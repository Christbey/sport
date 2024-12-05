<?php

namespace App\Providers;


use App\Repositories\Nfl\Interfaces\NflBettingOddsRepositoryInterface;
use App\Repositories\Nfl\Interfaces\NflEloPredictionRepositoryInterface;
use App\Repositories\Nfl\Interfaces\NflPlayerDataRepositoryInterface;
use App\Repositories\Nfl\Interfaces\NflTeamScheduleRepositoryInterface;
use App\Repositories\Nfl\NflBettingOddsRepository;
use App\Repositories\Nfl\NflEloPredictionRepository;
use App\Repositories\Nfl\NflPlayerDataRepository;
use App\Repositories\NflTeamRepository;
use App\Repositories\NflTeamRepositoryInterface;
use App\Repositories\NflTeamScheduleRepository;
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
        $this->app->bind(
            NflEloPredictionRepositoryInterface::class,
            NflEloPredictionRepository::class
        );
        $this->app->bind(
            NflBettingOddsRepositoryInterface::class,
            NflBettingOddsRepository::class
        );

        $this->app->bind(
            NflPlayerDataRepositoryInterface::class,
            NflPlayerDataRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {

    }
}
