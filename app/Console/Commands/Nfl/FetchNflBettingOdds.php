<?php

namespace App\Console\Commands\Nfl;

use App\Jobs\Nfl\StoreNflBettingOdds;
use App\Notifications\DiscordCommandCompletionNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class FetchNflBettingOdds extends Command
{
    protected $signature = 'nfl:fetch-betting-odds {date?}';
    protected $description = 'Fetch NFL betting odds for a specific date or for the current week based on today\'s date';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try {
            // Use today's date or the date provided
            $date = $this->argument('date') ? Carbon::parse($this->argument('date')) : Carbon::today();

            // Get the week configuration
            $weeks = config('nfl.weeks');

            // Determine the current week based on today’s date
            $currentWeek = $this->getCurrentWeek($weeks, $date);

            if ($currentWeek) {
                $start = Carbon::parse($currentWeek['start']);
                $end = Carbon::parse($currentWeek['end']);

                // Loop through each day from today until the end of the week
                while ($date->lessThanOrEqualTo($end)) {
                    // Dispatch the job for each date in the current week
                    dispatch(new StoreNflBettingOdds($date->format('Ymd')));
                    $this->info("Betting odds for date {$date->format('Ymd')} are being fetched.");

                    // Move to the next day
                    $date->addDay();
                }
            } else {
                $this->error('No current week found in the configuration for today\'s date.');
            }

            // Send success notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification('', 'success'));

        } catch (Exception $e) {
            // Send failure notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($e->getMessage(), 'error'));

        }
    }

    /**
     * Get the current week based on today’s date.
     *
     * @param array $weeks
     * @param Carbon $date
     * @return array|null
     */
    private function getCurrentWeek(array $weeks, Carbon $date): ?array
    {
        foreach ($weeks as $week) {
            $start = Carbon::parse($week['start']);
            $end = Carbon::parse($week['end']);

            // Check if today's date is within the week’s start and end dates
            if ($date->between($start, $end)) {
                return $week;
            }
        }
        return null; // No matching week found
    }
}
