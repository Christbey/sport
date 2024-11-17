<?php

use App\Helpers\CollegeFootballCommandHelpers;
use App\Helpers\NflCommandHelper;
use App\Models\CollegeFootball\{AdvancedGameStat, CollegeFootballElo, CollegeFootballFpi, Sagarin};
use App\Models\NFL\{NflBettingOdds, NflBoxscore};
use App\Models\NflNews;
use Carbon\Carbon;
use Illuminate\Support\Facades\{Log, Schedule};

// College Football Commands
Schedule::command('fetch:college-football-elo')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->when(fn() => CollegeFootballCommandHelpers::isFootballSeason())
    ->before(fn() => Log::info('Starting ELO ratings fetch'))
    ->after(fn() => CollegeFootballCommandHelpers::sendNotification('ELO ratings fetch completed successfully'))
    ->onFailure(fn($e) => CollegeFootballCommandHelpers::sendNotification(
        "ELO ratings fetch failed: {$e->getMessage()}",
        'failure'
    ))
    ->runInBackground();

Schedule::command('fetch:college-football-fpi')
    ->dailyAt('00:15')
    ->withoutOverlapping()
    ->when(fn() => CollegeFootballCommandHelpers::isFootballSeason())
    ->before(fn() => Log::info('Starting FPI ratings fetch'))
    ->after(fn() => CollegeFootballCommandHelpers::sendNotification('FPI ratings fetch completed successfully'))
    ->onFailure(fn($e) => CollegeFootballCommandHelpers::sendNotification(
        "FPI ratings fetch failed: {$e->getMessage()}",
        'failure'
    ))
    ->runInBackground();

Schedule::command('fetch:college-football-rankings')
    ->dailyAt('00:30')
    ->withoutOverlapping()
    ->when(fn() => CollegeFootballCommandHelpers::isFootballSeason())
    ->before(fn() => Log::info('Starting rankings fetch'))
    ->after(fn() => CollegeFootballCommandHelpers::sendNotification('Rankings fetch completed successfully'))
    ->onFailure(fn($e) => CollegeFootballCommandHelpers::sendNotification(
        "Rankings fetch failed: {$e->getMessage()}",
        'failure'
    ))
    ->runInBackground();

Schedule::command('fetch:advanced-game-stats')
    ->dailyAt('00:45')
    ->withoutOverlapping()
    ->when(fn() => CollegeFootballCommandHelpers::isFootballSeason())
    ->before(fn() => Log::info('Starting advanced stats fetch'))
    ->after(fn() => CollegeFootballCommandHelpers::sendNotification('Advanced stats fetch completed successfully'))
    ->onFailure(fn($e) => CollegeFootballCommandHelpers::sendNotification(
        "Advanced stats fetch failed: {$e->getMessage()}",
        'failure'
    ))
    ->runInBackground();

// NFL Boxscore
Schedule::command('nfl:fetch-boxscore')
    ->sundays()
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->before(fn() => Log::info('Starting NFL boxscore fetch'))
    ->after(fn() => NflCommandHelper::sendNotification('NFL boxscore fetch completed successfully'))
    ->onFailure(fn($e) => NflCommandHelper::sendNotification(
        "NFL boxscore fetch failed: {$e->getMessage()}",
        'failure'
    ))
    ->runInBackground();

// NFL News
Schedule::command('nfl:news')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->before(fn() => Log::info('Starting NFL news fetch'))
    ->after(function () {
        if (cache()->get('nfl_news_updated', false)) {
            CollegeFootballCommandHelpers::sendNotification('New NFL news articles fetched');
            cache()->forget('nfl_news_updated');
        }
    })
    ->onFailure(fn($e) => NflCommandHelper::sendNotification(
        "NFL news fetch failed: {$e->getMessage()}",
        'failure'
    ))
    ->runInBackground();

// NFL Betting Odds
Schedule::command('nfl:fetch-betting-odds')
    ->hourly()
    ->withoutOverlapping()
    ->before(fn() => Log::info('Starting NFL betting odds fetch'))
    ->after(function () {
        if (cache()->get('odds_significantly_changed', false)) {
            CollegeFootballCommandHelpers::sendNotification('Significant NFL odds changes detected');
            cache()->forget('odds_significantly_changed');
        }
    })
    ->onFailure(fn($e) => NflCommandHelper::sendNotification(
        "NFL betting odds fetch failed: {$e->getMessage()}",
        'failure'
    ))
    ->runInBackground();

Schedule::command('fetch:college-football-games')
    ->hourly()
    ->withoutOverlapping()
    ->when(fn() => CollegeFootballCommandHelpers::isFootballSeason())
    ->before(fn() => Log::info('Starting college football games fetch'))
    ->after(fn() => CollegeFootballCommandHelpers::sendNotification('College football games fetch completed successfully'))
    ->onFailure(fn($e) => CollegeFootballCommandHelpers::sendNotification(
        "College football games fetch failed: {$e->getMessage()}",
        'failure'
    ))
    ->runInBackground();

Schedule::command('fetch:college-football-lines')
    ->dailyAt('17:00')
    ->withoutOverlapping()
    ->when(fn() => CollegeFootballCommandHelpers::isFootballSeason())
    ->before(fn() => Log::info('Starting college football lines fetch'))
    ->after(fn() => CollegeFootballCommandHelpers::sendNotification('College football lines fetch completed successfully'))
    ->onFailure(fn($e) => CollegeFootballCommandHelpers::sendNotification(
        "College football lines fetch failed: {$e->getMessage()}",
        'failure'
    ))
    ->runInBackground();

Schedule::command('fetch:college-football-media')
    ->dailyAt('17:15')
    ->withoutOverlapping()
    ->when(fn() => CollegeFootballCommandHelpers::isFootballSeason())
    ->before(fn() => Log::info('Starting college football media fetch'))
    ->after(fn() => CollegeFootballCommandHelpers::sendNotification('College football media fetch completed successfully'))
    ->onFailure(fn($e) => CollegeFootballCommandHelpers::sendNotification(
        "College football media fetch failed: {$e->getMessage()}",
        'failure'
    ))
    ->runInBackground();

Schedule::command('fetch:college-football-rankings')
    ->dailyAt('17:30')
    ->withoutOverlapping()
    ->when(fn() => CollegeFootballCommandHelpers::isFootballSeason())
    ->before(fn() => Log::info('Starting college football media fetch'))
    ->after(fn() => CollegeFootballCommandHelpers::sendNotification('College football media fetch completed successfully'))
    ->onFailure(fn($e) => CollegeFootballCommandHelpers::sendNotification(
        "College football media fetch failed: {$e->getMessage()}",
        'failure'
    ))
    ->runInBackground();

Schedule::command('nfl:fetch-team-schedule')
    ->dailyAt('18:00')
    ->withoutOverlapping()
    ->before(fn() => Log::info('Starting NFL team schedule fetch'))
    ->after(fn() => NflCommandHelper::sendNotification('NFL team schedule fetch completed successfully'))
    ->onFailure(fn($e) => NflCommandHelper::sendNotification(
        "NFL team schedule fetch failed: {$e->getMessage()}",
        'failure'
    ))
    ->runInBackground();

// NFL Team ELO Calculation
Schedule::command('nfl:calculate-team-elo')
    ->dailyAt('18:15')
    ->withoutOverlapping()
    ->before(fn() => Log::info('Starting NFL team ELO calculation'))
    ->after(fn() => NflCommandHelper::sendNotification('NFL team ELO calculation completed successfully'))
    ->onFailure(fn($e) => NflCommandHelper::sendNotification(
        "NFL team ELO calculation failed: {$e->getMessage()}",
        'failure'
    ))
    ->runInBackground();

Schedule::command('fetch:nfl-teams')
    ->weekly()
    ->tuesdays()
    ->at('09:00')
    ->withoutOverlapping()
    ->before(fn() => Log::info('Starting NFL teams fetch'))
    ->after(fn() => NflCommandHelper::sendNotification('NFL teams fetch completed successfully'))
    ->onFailure(fn($e) => NflCommandHelper::sendNotification(
        "NFL teams fetch failed: {$e->getMessage()}",
        'failure'
    ))
    ->runInBackground();
Schedule::command('college-basketball:scoreboard')
    ->hourly()
    ->withoutOverlapping()
    ->before(fn() => Log::info('Starting college basketball scoreboard fetch'))
    ->after(fn() => CollegeFootballCommandHelpers::sendNotification('College basketball scoreboard fetch completed successfully'))
    ->onFailure(fn($e) => CollegeFootballCommandHelpers::sendNotification(
        "College basketball scoreboard fetch failed: {$e->getMessage()}",
        'failure'
    ))
    ->runInBackground();

Schedule::command('update:game-scores')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->before(fn() => Log::info('Starting college basketball game scores update'))
    ->after(fn() => CollegeFootballCommandHelpers::sendNotification('College basketball game scores update completed successfully'))
    ->onFailure(fn($e) => CollegeFootballCommandHelpers::sendNotification(
        "College basketball game scores update failed: {$e->getMessage()}",
        'failure'
    ))
    ->runInBackground();

Schedule::command('scrape:kenpom')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->before(fn() => Log::info('Starting KenPom scrape'))
    ->after(fn() => CollegeFootballCommandHelpers::sendNotification('KenPom scrape completed successfully'))
    ->onFailure(fn($e) => CollegeFootballCommandHelpers::sendNotification(
        "KenPom scrape failed: {$e->getMessage()}",
        'failure'
    ))
    ->runInBackground();

Schedule::command('nfl:update-submissions')
    ->weekly()
    ->tuesdays()
    ->withoutOverlapping()
    ->before(fn() => Log::info('Starting NFL user submissions update'))
    ->after(fn() => NflCommandHelper::sendNotification('NFL user submissions update completed successfully'))
    ->onFailure(fn($e) => NflCommandHelper::sendNotification(
        "NFL user submissions update failed: {$e->getMessage()}",
        'failure'
    ))
    ->runInBackground();

Schedule::command('calculate:hypothetical-spreads')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->before(fn() => Log::info('Starting hypothetical spreads calculation'))
    ->after(fn() => CollegeFootballCommandHelpers::sendNotification('Hypothetical spreads calculation completed successfully'))
    ->onFailure(fn($e) => CollegeFootballCommandHelpers::sendNotification(
        "Hypothetical spreads calculation failed: {$e->getMessage()}",
        'failure'
    ))
    ->runInBackground();

Schedule::command('college-basketball:hypothetical-spread')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->before(fn() => Log::info('Starting college basketball hypothetical spreads calculation'))
    ->after(fn() => CollegeFootballCommandHelpers::sendNotification('College basketball hypothetical spreads calculation completed successfully'))
    ->onFailure(fn($e) => CollegeFootballCommandHelpers::sendNotification(
        "College basketball hypothetical spreads calculation failed: {$e->getMessage()}",
        'failure'
    ))
    ->runInBackground();

Schedule::command('calculate:game-differences')
    ->hourly()
    ->withoutOverlapping()
    ->before(fn() => Log::info('Starting game differences calculation'))
    ->after(fn() => CollegeFootballCommandHelpers::sendNotification('Game differences calculation completed successfully'))
    ->onFailure(fn($e) => CollegeFootballCommandHelpers::sendNotification(
        "Game differences calculation failed: {$e->getMessage()}",
        'failure'
    ))
    ->runInBackground();

//Schedule::command('scrape:massey')

// Health Checks
Schedule::job(function () {
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
            'News Articles' => NflNews::class,
            'Betting Odds Updates' => NflBettingOdds::class,
        ]
    ], $lastWeek);

    CollegeFootballCommandHelpers::sendNotification($stats['message'], $stats['type']);
})
    ->weekly()
    ->mondays()
    ->at('09:00');

// Data Freshness Check
Schedule::job(function () {
    $warnings = CollegeFootballCommandHelpers::checkDataFreshness([
        'ELO Ratings' => [CollegeFootballElo::class, 1],
        'FPI Ratings' => [CollegeFootballFpi::class, 1],
        'Rankings' => [Sagarin::class, 1],
        'Advanced Stats' => [AdvancedGameStat::class, 1],
        'NFL Boxscores' => [NflBoxscore::class, 1],
        'NFL News' => [NflNews::class, 1],
        'NFL Betting Odds' => [NflBettingOdds::class, 1 / 24],
    ]);

    if (!empty($warnings)) {
        CollegeFootballCommandHelpers::sendNotification(
            "Data Freshness Warnings:\n" . implode("\n", $warnings),
            'failure'
        );
    }
})
    ->dailyAt('08:00');

// API Rate Limit Monitor
Schedule::job(function () {
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