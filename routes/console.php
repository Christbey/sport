```php
<?php

use App\Helpers\CollegeFootballCommandHelpers;
use App\Models\CollegeFootball\AdvancedGameStat;
use App\Models\CollegeFootball\CollegeFootballElo;
use App\Models\CollegeFootball\CollegeFootballFpi;
use App\Models\CollegeFootball\Sagarin;
use App\Models\NFL\NflBettingOdds;
use App\Models\Nfl\NflBoxscore;
use App\Models\NFLNews;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

// College Football Commands
Schedule::command('fetch:college-football-elo')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->when(fn() => CollegeFootballCommandHelpers::isFootballSeason())
    ->before(function () {
        Log::info('Starting ELO ratings fetch');
    })
    ->after(function () {
        CollegeFootballCommandHelpers::sendNotification('ELO ratings fetch completed successfully');
    })
    ->onFailure(function (Throwable $e) {
        CollegeFootballCommandHelpers::sendNotification(
            "ELO ratings fetch failed: {$e->getMessage()}",
            'failure'
        );
    })
    ->runInBackground();

Schedule::command('fetch:college-football-fpi')
    ->dailyAt('00:15')
    ->withoutOverlapping()
    ->when(fn() => CollegeFootballCommandHelpers::isFootballSeason())
    ->before(function () {
        Log::info('Starting FPI ratings fetch');
    })
    ->after(function () {
        CollegeFootballCommandHelpers::sendNotification('FPI ratings fetch completed successfully');
    })
    ->onFailure(function (Throwable $e) {
        CollegeFootballCommandHelpers::sendNotification(
            "FPI ratings fetch failed: {$e->getMessage()}",
            'failure'
        );
    })
    ->runInBackground();

Schedule::command('fetch:college-football-rankings')
    ->dailyAt('00:30')
    ->withoutOverlapping()
    ->when(fn() => CollegeFootballCommandHelpers::isFootballSeason())
    ->before(function () {
        Log::info('Starting rankings fetch');
    })
    ->after(function () {
        CollegeFootballCommandHelpers::sendNotification('Rankings fetch completed successfully');
    })
    ->onFailure(function (Throwable $e) {
        CollegeFootballCommandHelpers::sendNotification(
            "Rankings fetch failed: {$e->getMessage()}",
            'failure'
        );
    })
    ->runInBackground();

Schedule::command('fetch:advanced-game-stats')
    ->dailyAt('00:45')
    ->withoutOverlapping()
    ->when(fn() => CollegeFootballCommandHelpers::isFootballSeason())
    ->before(function () {
        Log::info('Starting advanced stats fetch');
    })
    ->after(function () {
        CollegeFootballCommandHelpers::sendNotification('Advanced stats fetch completed successfully');
    })
    ->onFailure(function (Throwable $e) {
        CollegeFootballCommandHelpers::sendNotification(
            "Advanced stats fetch failed: {$e->getMessage()}",
            'failure'
        );
    })
    ->runInBackground();

// NFL Commands
Schedule::command('nfl:fetch-boxscore')
    ->dailyAt('22:00') // 10:00 PM UTC (7:00 PM CST)
    ->withoutOverlapping()
    ->before(function () {
        Log::info('Starting NFL boxscore fetch');
    })
    ->after(function () {
        CollegeFootballCommandHelpers::sendNotification('NFL boxscore fetch completed successfully');
    })
    ->onFailure(function (Throwable $e) {
        CollegeFootballCommandHelpers::sendNotification(
            "NFL boxscore fetch failed: {$e->getMessage()}",
            'failure'
        );
    })
    ->runInBackground();

Schedule::command('nfl:news')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->before(function () {
        Log::info('Starting NFL news fetch');
    })
    ->after(function () {
        if (cache()->get('nfl_news_updated', false)) {
            CollegeFootballCommandHelpers::sendNotification('New NFL news articles fetched');
            cache()->forget('nfl_news_updated');
        }
    })
    ->onFailure(function (Throwable $e) {
        CollegeFootballCommandHelpers::sendNotification(
            "NFL news fetch failed: {$e->getMessage()}",
            'failure'
        );
    })
    ->runInBackground();

Schedule::command('nfl:fetch-betting-odds')
    ->hourly()
    ->withoutOverlapping()
    ->before(function () {
        Log::info('Starting NFL betting odds fetch');
    })
    ->after(function () {
        if (cache()->get('odds_significantly_changed', false)) {
            CollegeFootballCommandHelpers::sendNotification('Significant NFL odds changes detected');
            cache()->forget('odds_significantly_changed');
        }
    })
    ->onFailure(function (Throwable $e) {
        CollegeFootballCommandHelpers::sendNotification(
            "NFL betting odds fetch failed: {$e->getMessage()}",
            'failure'
        );
    })
    ->runInBackground();

// Health Checks
Schedule::call(function () {
    $lastWeek = Carbon::now()->subWeek();

    $stats = CollegeFootballCommandHelpers::getWeeklyStats([
        'College Football' => [
            'ELO Ratings' => CollegeFootballElo::class,
            'FPI Ratings' => CollegeFootballFpi::class,
            'Rankings' => Sagarin::class,
            'Advanced Stats' => AdvancedGameStat::class,
        ],
        'NFL' => [
            'Boxscores' => NflBoxscore::class,
            'News Articles' => NFLNews::class,
            'Betting Odds Updates' => NflBettingOdds::class,
        ]
    ], $lastWeek);

    CollegeFootballCommandHelpers::sendNotification($stats['message'], $stats['type']);
})
    ->weekly()
    ->mondays()
    ->at('09:00');

// Data Freshness Check
Schedule::call(function () {
    $dataChecks = [
        // College Football checks
        'ELO Ratings' => [CollegeFootballElo::class, 1],
        'FPI Ratings' => [CollegeFootballFpi::class, 1],
        'Rankings' => [Sagarin::class, 1],
        'Advanced Stats' => [AdvancedGameStat::class, 1],

        // NFL checks
        'NFL Boxscores' => [NflBoxscore::class, 1],
        'NFL News' => [NFLNews::class, 1],
        'NFL Betting Odds' => [NflBettingOdds::class, 1 / 24],
    ];

    $warnings = CollegeFootballCommandHelpers::checkDataFreshness($dataChecks);

    if (!empty($warnings)) {
        CollegeFootballCommandHelpers::sendNotification(
            "Data Freshness Warnings:\n" . implode("\n", $warnings),
            'failure'
        );
    }
})
    ->dailyAt('08:00');

// API Rate Limit Monitor
Schedule::call(function () {
    $warnings = CollegeFootballCommandHelpers::checkApiRateLimits([
        'NFL API' => 'nfl_api_remaining_calls',
        'DraftKings API' => 'draftkings_api_remaining_calls'
    ]);

    if (!empty($warnings)) {
        CollegeFootballCommandHelpers::sendNotification(
            "API Rate Limit Warnings:\n" . implode("\n", $warnings),
            'failure'
        );
    }
})
    ->hourly();
