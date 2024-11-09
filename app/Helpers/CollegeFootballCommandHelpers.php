<?php

namespace App\Helpers;

use App\Notifications\DiscordCommandCompletionNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class CollegeFootballCommandHelpers
{
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
     * Check if command has been run recently
     */
    public static function hasRecentRun(string $cacheKey): bool
    {
        $lastRun = Cache::get($cacheKey);
        return $lastRun && Carbon::parse($lastRun)->diffInHours() < 6;
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
}
