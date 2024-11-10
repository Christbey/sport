<?php

namespace App\Helpers;

use App\Notifications\DiscordCommandCompletionNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Log;

class CollegeFootballCommandHelpers
{
    public const CACHE_DURATION = 24; // hours
    public const RECENT_RUN_THRESHOLD = 6; // hours
    public const API_WARNING_THRESHOLD = 100;
    public const MAX_DISPLAY_ITEMS = 3;

    /**
     * Get the current week based on configuration
     */
    public static function getCurrentWeek(): int
    {
        $today = Carbon::today();
        $weeks = config('college_football.weeks');

        foreach ($weeks as $weekNumber => $dates) {
            $start = Carbon::parse($dates['start']);
            $end = Carbon::parse($dates['end']);

            if ($today->between($start, $end)) {
                return $weekNumber;
            }
        }

        return array_key_last($weeks);
    }

    /**
     * Handle command execution with common checks
     */
    public static function handleCommand(Command $command, string $cacheKey, callable $execution, bool $force = false): int
    {
        if (!self::isFootballSeason() && !$force) {
            $command->warn('Not currently in football season. Use --force to run anyway.');
            return 1;
        }

        if (!self::handleRecentRun($cacheKey, $command, $force)) {
            return 0;
        }

        try {
            return $execution();
        } catch (Exception $e) {
            self::handleCommandError($command, $e);
            return 1;
        }
    }

    /**
     * Check if we're in football season
     */
    public static function isFootballSeason(): bool
    {
        $now = Carbon::now();
        $seasonStart = Carbon::parse(config('college_football.season_start'));
        $seasonEnd = Carbon::parse(config('college_football.season_end'));

        return $now->between($seasonStart, $seasonEnd);
    }

    /**
     * Check and handle recent runs of a command
     */
    public static function handleRecentRun(string $cacheKey, Command $command, bool $force = false): bool
    {
        if (!$force && self::hasRecentRun($cacheKey)) {
            $lastRun = Carbon::parse(Cache::get($cacheKey));
            $command->warn("Command was already run {$lastRun->diffForHumans()}.");

            if (!$command->confirm('Do you want to run it again?')) {
                return false;
            }
        }

        Cache::put($cacheKey, now(), now()->addHours(self::CACHE_DURATION));
        return true;
    }

    /**
     * Check if command has been run recently
     */
    public static function hasRecentRun(string $cacheKey): bool
    {
        $lastRun = Cache::get($cacheKey);
        return $lastRun && Carbon::parse($lastRun)->diffInHours() < self::RECENT_RUN_THRESHOLD;
    }

    /**
     * Handle command error with logging and notification
     */
    public static function handleCommandError(Command $command, Exception $e): void
    {
        $errorMessage = "Command failed: {$e->getMessage()}";
        $command->error($errorMessage);
        Log::error($errorMessage, [
            'exception' => $e,
            'command' => get_class($command)
        ]);
        self::sendNotification($errorMessage, 'failure');
    }

    /**
     * Send a command notification through Discord
     */
    public static function sendNotification(string $message, string $type = 'success'): void
    {
        $discordWebhook = config('services.discord.channel_id');

        Notification::route('discord', $discordWebhook)
            ->notify(new DiscordCommandCompletionNotification($message, $type));
    }

    /**
     * Display command execution summary
     */
    public static function displaySummary(Command $command, array $stats, ?string $jobClass = null): void
    {
        self::displayConsoleStats($stats, $command);

        if ($jobClass) {
            self::displayApiInfo($jobClass, $command);
        }

        $warnings = self::getSystemWarnings($stats);
        if (!empty($warnings)) {
            $command->line('');
            $command->warn('Warnings:');
            foreach ($warnings as $warning) {
                $command->warn("- $warning");
            }
        }
    }

    /**
     * Display statistics in a formatted way for console commands
     */
    public static function displayConsoleStats(array $stats, $command): void
    {
        $command->line('');
        $command->line('Previous Run Statistics:');
        $command->line('---------------------');
        $command->info("Teams Updated: {$stats['updated_teams']}");

        if (!empty($stats['missing_teams'])) {
            $command->warn('Missing Teams: ' . implode(', ', array_slice($stats['missing_teams'], 0, 3)) .
                (count($stats['missing_teams']) > 3 ? ' and ' . (count($stats['missing_teams']) - 3) . ' more' : ''));
        }

        if (!empty($stats['significant_changes'])) {
            $command->line('');
            $command->info('Significant Changes:');
            foreach (array_slice($stats['significant_changes'], 0, 3) as $change) {
                $direction = $change['change'] > 0 ? '↑' : '↓';
                $command->line(sprintf(
                    '%s %s: %.2f → %.2f (%+.2f)',
                    $direction,
                    $change['team'],
                    $change['previous'],
                    $change['new'],
                    $change['change']
                ));
            }
            if (count($stats['significant_changes']) > 3) {
                $command->line('... and ' . (count($stats['significant_changes']) - 3) . ' more changes');
            }
        }
        $command->line('');
    }

    /**
     * Display API related information
     */
    public static function displayApiInfo($jobClass, $command): void
    {
        $apiCalls = $jobClass::getApiCallsToday();
        if ($apiCalls > 0) {
            $command->info("API calls today: {$apiCalls}");
        }

        $lastError = $jobClass::getLastError();
        if ($lastError && Carbon::parse($lastError['time'])->isToday()) {
            $command->warn('Note: There was an error in the last run:');
            $command->error($lastError['message']);
        }
    }

    /**
     * Get system health warnings
     */
    protected static function getSystemWarnings(array $stats): array
    {
        $warnings = [];

        if (!empty($stats['missing_teams']) && count($stats['missing_teams']) > 10) {
            $warnings[] = 'High number of missing teams detected';
        }

        if (!empty($stats['significant_changes']) && count($stats['significant_changes']) > 20) {
            $warnings[] = 'Unusually high number of significant changes detected';
        }

        return $warnings;
    }

    /**
     * Format statistics for display
     */
    public static function formatStats(array $stats): array
    {
        $output = [];

        if (isset($stats['updated_teams'])) {
            $output[] = ["Teams Updated", $stats['updated_teams']];
        }

        if (!empty($stats['missing_teams'])) {
            $missingTeams = array_slice($stats['missing_teams'], 0, 3);
            if (count($stats['missing_teams']) > 3) {
                $missingTeams[] = "+" . (count($stats['missing_teams']) - 3) . " more";
            }
            $output[] = ["Missing Teams", implode(", ", $missingTeams)];
        }

        if (!empty($stats['significant_changes'])) {
            $changes = array_slice($stats['significant_changes'], 0, 3);
            foreach ($changes as $change) {
                $direction = $change['change'] > 0 ? '↑' : '↓';
                $output[] = [
                    "Change",
                    sprintf(
                        "%s %s: %.2f → %.2f (%+.2f)",
                        $direction,
                        $change['team'],
                        $change['previous'],
                        $change['new'],
                        $change['change']
                    )
                ];
            }

            if (count($stats['significant_changes']) > 3) {
                $output[] = ["", "+" . (count($stats['significant_changes']) - 3) . " more changes"];
            }
        }

        return $output;
    }

    public static function getWeeklyStats(array $categories, Carbon $lastWeek): array
    {
        $stats = [];
        $message = "Weekly Data Health Summary:\n\n";

        foreach ($categories as $category => $models) {
            $stats[$category] = [];
            $message .= "**{$category}**\n";

            foreach ($models as $name => $modelClass) {
                $count = $modelClass::where('created_at', '>', $lastWeek)->count();
                $stats[$category][$name] = $count;
                $message .= "- $name: $count new entries\n";
            }
            $message .= "\n";
        }

        // Calculate success rate
        $totalJobs = array_sum(array_map('count', $categories));
        $successfulJobs = count(array_filter(
            array_merge(...array_values($stats)),
            fn($count) => $count > 0
        ));

        $successRate = ($successfulJobs / $totalJobs) * 100;
        $message .= sprintf('Overall Success Rate: %.1f%%', $successRate);

        return [
            'stats' => $stats,
            'message' => $message,
            'type' => $successRate >= 90 ? 'success' : 'failure'
        ];
    }

    /**
     * Check data freshness for multiple models
     */
    public static function checkDataFreshness(array $dataChecks): array
    {
        $warnings = [];
        $today = Carbon::today();

        foreach ($dataChecks as $name => [$model, $staleDays]) {
            $lastRecord = $model::latest('created_at')->first();
            if ($lastRecord && $lastRecord->created_at->diffInDays($today) > $staleDays) {
                $warnings[] = "$name not updated in {$lastRecord->created_at->diffInDays($today)} days";
            }
        }

        return $warnings;
    }

    /**
     * Check API rate limits
     */
    public static function checkApiRateLimits(array $apis): array
    {
        $warnings = [];

        foreach ($apis as $name => $cacheKey) {
            $remainingCalls = cache()->get($cacheKey);
            if ($remainingCalls !== null && $remainingCalls < 100) {
                $warnings[] = "$name is running low on remaining calls: $remainingCalls left";
            }
        }

        return $warnings;
    }

    /**
     * Create cache key for a specific command and context
     */
    public static function createCacheKey(string $prefix, array $context = []): string
    {
        $key = $prefix;
        if (!empty($context)) {
            $key .= '_' . md5(serialize($context));
        }
        return $key;
    }

}
