<?php

use App\Models\CollegeFootball\AdvancedGameStat;
use App\Models\CollegeFootball\CollegeFootballElo;
use App\Models\CollegeFootball\CollegeFootballFpi;
use App\Models\CollegeFootball\Sagarin;
use App\Models\NFL\NflBettingOdds;
use App\Models\Nfl\NflBoxScore;
use App\Models\NFLNews;
use App\Notifications\DiscordCommandCompletionNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schedule;

// Helper function to send notifications
function sendCommandNotification(string $message, string $type = 'success'): void
{
    // You might want to configure this webhook in your config
    $discordWebhook = config('services.discord.webhook');

    Notification::route('discord', $discordWebhook)
        ->notify(new DiscordCommandCompletionNotification($message, $type));
}

// Helper function to check if we're in football season
function isFootballSeason(): bool
{
    $now = Carbon::now();
    $seasonStart = Carbon::parse(config('college_football.season_start'));
    $seasonEnd = Carbon::parse(config('college_football.season_end'));

    return $now->between($seasonStart, $seasonEnd);
}

// Helper function to verify data freshness
function verifyDataFreshness(): array
{
    $warnings = [];
    $today = Carbon::today();

    $dataChecks = [
        'ELO Ratings' => [CollegeFootballElo::class, 1],
        'FPI Ratings' => [CollegeFootballFpi::class, 1],
        'Rankings' => [Sagarin::class, 1],
        'Advanced Stats' => [AdvancedGameStat::class, 1]
    ];

    foreach ($dataChecks as $name => [$model, $staleDays]) {
        $lastRecord = $model::latest('created_at')->first();
        if ($lastRecord && $lastRecord->created_at->diffInDays($today) > $staleDays) {
            $warnings[] = "$name not updated in {$lastRecord->created_at->diffInDays($today)} days";
        }
    }

    return $warnings;
}

Schedule::command('fetch:college-football-elo')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->when(fn() => isFootballSeason())
    ->before(function () {
        Log::info('Starting ELO ratings fetch');
    })
    ->after(function () {
        sendCommandNotification('ELO ratings fetch completed successfully');
    })
    ->onFailure(function (Throwable $e) {
        sendCommandNotification(
            "ELO ratings fetch failed: {$e->getMessage()}",
            'failure'
        );
    })
    ->runInBackground();

Schedule::command('fetch:college-football-fpi')
    ->dailyAt('00:15')
    ->withoutOverlapping()
    ->when(fn() => isFootballSeason())
    ->before(function () {
        Log::info('Starting FPI ratings fetch');
    })
    ->after(function () {
        sendCommandNotification('FPI ratings fetch completed successfully');
    })
    ->onFailure(function (Throwable $e) {
        sendCommandNotification(
            "FPI ratings fetch failed: {$e->getMessage()}",
            'failure'
        );
    })
    ->runInBackground();

Schedule::command('fetch:college-football-rankings')
    ->dailyAt('00:30')
    ->withoutOverlapping()
    ->when(fn() => isFootballSeason())
    ->before(function () {
        Log::info('Starting rankings fetch');
    })
    ->after(function () {
        sendCommandNotification('Rankings fetch completed successfully');
    })
    ->onFailure(function (Throwable $e) {
        sendCommandNotification(
            "Rankings fetch failed: {$e->getMessage()}",
            'failure'
        );
    })
    ->runInBackground();

Schedule::command('fetch:advanced-game-stats')
    ->dailyAt('00:45')
    ->withoutOverlapping()
    ->when(fn() => isFootballSeason())
    ->before(function () {
        Log::info('Starting advanced stats fetch');
    })
    ->after(function () {
        sendCommandNotification('Advanced stats fetch completed successfully');
    })
    ->onFailure(function (Throwable $e) {
        sendCommandNotification(
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
        sendCommandNotification('NFL boxscore fetch completed successfully');
    })
    ->onFailure(function (Throwable $e) {
        sendCommandNotification(
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
        // Only notify on significant updates to avoid spam
        if (cache()->get('nfl_news_updated', false)) {
            sendCommandNotification('New NFL news articles fetched');
            cache()->forget('nfl_news_updated');
        }
    })
    ->onFailure(function (Throwable $e) {
        sendCommandNotification(
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
        // Only notify on significant odds changes to avoid spam
        if (cache()->get('odds_significantly_changed', false)) {
            sendCommandNotification('Significant NFL odds changes detected');
            cache()->forget('odds_significantly_changed');
        }
    })
    ->onFailure(function (Throwable $e) {
        sendCommandNotification(
            "NFL betting odds fetch failed: {$e->getMessage()}",
            'failure'
        );
    })
    ->runInBackground();


// Modified Weekly health check summary to include NFL stats
Schedule::call(function () {
    $lastWeek = Carbon::now()->subWeek();

    // College Football Stats
    $stats = [
        'College Football' => [
            'ELO Ratings' => CollegeFootballElo::where('created_at', '>', $lastWeek)->count(),
            'FPI Ratings' => CollegeFootballFpi::where('created_at', '>', $lastWeek)->count(),
            'Rankings' => Sagarin::where('created_at', '>', $lastWeek)->count(),
            'Advanced Stats' => AdvancedGameStat::where('created_at', '>', $lastWeek)->count(),
        ],
        'NFL' => [
            'Boxscores' => NflBoxscore::where('created_at', '>', $lastWeek)->count(),
            'News Articles' => NFLNews::where('created_at', '>', $lastWeek)->count(),
            'Betting Odds Updates' => NflBettingOdds::where('created_at', '>', $lastWeek)->count(),
        ]
    ];

    $message = "Weekly Data Health Summary:\n\n";

    foreach ($stats as $category => $categoryStats) {
        $message .= "**{$category}**\n";
        foreach ($categoryStats as $name => $count) {
            $message .= "- $name: $count new entries\n";
        }
        $message .= "\n";
    }

    // Calculate overall success rate
    $totalJobs = count($stats['College Football']) + count($stats['NFL']);
    $successfulJobs = count(array_filter(array_merge(
        $stats['College Football'],
        $stats['NFL']
    ), fn($count) => $count > 0));

    $successRate = ($successfulJobs / $totalJobs) * 100;

    $message .= sprintf('Overall Success Rate: %.1f%%', $successRate);

    // Send with appropriate type based on success rate
    $type = $successRate >= 90 ? 'success' : 'failure';

    sendCommandNotification($message, $type);
})
    ->weekly()
    ->mondays()
    ->at('09:00');

// Daily data freshness check (modified to include NFL)
Schedule::call(function () {
    $warnings = [];
    $today = Carbon::today();

    $dataChecks = [
        // College Football checks
        'ELO Ratings' => [CollegeFootballElo::class, 1],
        'FPI Ratings' => [CollegeFootballFpi::class, 1],
        'Rankings' => [Sagarin::class, 1],
        'Advanced Stats' => [AdvancedGameStat::class, 1],

        // NFL checks
        'NFL Boxscores' => [NflBoxscore::class, 1],
        'NFL News' => [NFLNews::class, 1],
        'NFL Betting Odds' => [NflBettingOdds::class, 1 / 24], // Check if odds are more than 1 hour old
    ];

    foreach ($dataChecks as $name => [$model, $staleDays]) {
        $lastRecord = $model::latest('created_at')->first();
        if ($lastRecord && $lastRecord->created_at->diffInDays($today) > $staleDays) {
            $warnings[] = "$name not updated in {$lastRecord->created_at->diffInDays($today)} days";
        }
    }

    if (!empty($warnings)) {
        sendCommandNotification(
            "Data Freshness Warnings:\n" . implode("\n", $warnings),
            'failure'
        );
    }
})
    ->dailyAt('08:00');

// Monitor API rate limits
Schedule::call(function () {
    $apis = [
        'NFL API' => cache()->get('nfl_api_remaining_calls'),
        'DraftKings API' => cache()->get('draftkings_api_remaining_calls'),
    ];

    $warnings = [];
    foreach ($apis as $name => $remainingCalls) {
        if ($remainingCalls !== null && $remainingCalls < 100) {
            $warnings[] = "$name is running low on remaining calls: $remainingCalls left";
        }
    }

    if (!empty($warnings)) {
        sendCommandNotification(
            "API Rate Limit Warnings:\n" . implode("\n", $warnings),
            'failure'
        );
    }
})
    ->hourly();