<?php

namespace App\Providers;


use App\Repositories\Nfl\Interfaces\NflBettingOddsRepositoryInterface;
use App\Repositories\Nfl\Interfaces\NflEloPredictionRepositoryInterface;
use App\Repositories\Nfl\Interfaces\NflPlayerDataRepositoryInterface;
use App\Repositories\Nfl\Interfaces\NflTeamRepositoryInterface;
use App\Repositories\Nfl\Interfaces\NflTeamScheduleRepositoryInterface;
use App\Repositories\Nfl\Interfaces\TeamStatsRepositoryInterface;
use App\Repositories\Nfl\NflBettingOddsRepository;
use App\Repositories\Nfl\NflEloPredictionRepository;
use App\Repositories\Nfl\NflPlayerDataRepository;
use App\Repositories\Nfl\TeamStatsRepository;
use App\Repositories\NflTeamRepository;
use App\Repositories\NflTeamScheduleRepository;
use App\Services\OpenAIChatService;
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

        // Bind repository interfaces to their implementations
        $this->app->bind(NflBettingOddsRepositoryInterface::class, NflBettingOddsRepository::class);
        $this->app->bind(NflEloPredictionRepositoryInterface::class, NflEloPredictionRepository::class);
        $this->app->bind(TeamStatsRepositoryInterface::class, TeamStatsRepository::class);
        $this->app->bind(NflPlayerDataRepositoryInterface::class, NflPlayerDataRepository::class);
        $this->app->bind(NflTeamScheduleRepositoryInterface::class, NflTeamScheduleRepository::class);

        // Bind OpenAIChatService as a singleton
        $this->app->singleton(OpenAIChatService::class, function ($app) {
            return new OpenAIChatService(
                $app->make(NflBettingOddsRepositoryInterface::class),
                $app->make(NflEloPredictionRepositoryInterface::class),
                $app->make(TeamStatsRepositoryInterface::class),
                $app->make(NflPlayerDataRepositoryInterface::class),
                $app->make(NflTeamScheduleRepositoryInterface::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {

    }
}
