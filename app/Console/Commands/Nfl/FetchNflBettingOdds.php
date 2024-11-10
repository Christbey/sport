<?php

namespace App\Console\Commands\Nfl;

use App\Jobs\Nfl\StoreNflBettingOdds;
use App\Notifications\DiscordCommandCompletionNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class FetchNflBettingOdds extends Command
{
    protected const CACHE_KEY = 'nfl_odds_command_last_run';

    protected $signature = 'nfl:fetch-betting-odds 
        {date? : The date to fetch odds for (YYYY-MM-DD)}
        {--force : Force fetch even if already run today}';

    protected $description = 'Fetch NFL betting odds for a specific date';

    public function handle()
    {
        try {
            $date = $this->resolveDate();

            if (!$this->validateDate($date)) {
                return 1;
            }

            $this->info('Processing NFL betting odds for ' . $date->format('Y-m-d'));

            // Process the date
            $formattedDate = $date->format('Ymd');

            dispatch(new StoreNflBettingOdds($formattedDate));

            // Wait a moment for the job to complete
            sleep(2);

            // Check for changes
            $changes = Cache::get(StoreNflBettingOdds::CACHE_KEY . $formattedDate);

            // Send notification
            $this->sendNotification($formattedDate, $changes);

            return 0;

        } catch (Exception $e) {
            $this->handleError($e);
            return 1;
        }
    }

    private function resolveDate(): Carbon
    {
        $dateString = $this->argument('date');
        try {
            return $dateString ? Carbon::parse($dateString) : Carbon::today();
        } catch (Exception $e) {
            throw new Exception('Invalid date format. Please use YYYY-MM-DD');
        }
    }

    private function validateDate(Carbon $date): bool
    {
        if ($date->isFuture()) {
            $this->error('Cannot fetch odds for future dates.');
            return false;
        }

        if ($date->diffInDays(Carbon::today()) > 30) {
            if (!$this->confirm('Date is more than 30 days in the past. Continue?')) {
                return false;
            }
        }

        return true;
    }

    private function sendNotification(string $date, array $changes): void
    {
        $message = "**NFL Betting Odds Update**\n";
        $message .= 'Date: ' . Carbon::parse($date)->format('Y-m-d') . "\n\n";

        if (empty($changes)) {
            $message .= 'No significant line changes detected.';
        } else {
            $message .= "**Notable Line Changes:**\n";
            foreach ($changes as $change) {
                if (isset($change['matchup'])) {
                    $message .= "\n• {$change['matchup']}\n";

                    foreach ($change['changes'] ?? [] as $type => $values) {
                        switch ($type) {
                            case 'spread':
                                $message .= "  Spread: {$values['old']} → {$values['new']} ({$values['change']})\n";
                                break;
                            case 'total':
                                $message .= "  Total: {$values['old']} → {$values['new']} ({$values['change']})\n";
                                break;
                            case 'home_ml':
                                $message .= "  Home ML: {$values['old']} → {$values['new']} ({$values['change']})\n";
                                break;
                            case 'away_ml':
                                $message .= "  Away ML: {$values['old']} → {$values['new']} ({$values['change']})\n";
                                break;
                        }
                    }
                }
            }
        }

        $message .= "\n_Powered by Picksports Alerts • " . now()->format('F j, Y g:i A') . '_';

        Notification::route('discord', config('services.discord.channel_id'))
            ->notify(new DiscordCommandCompletionNotification($message, 'success'));
    }

    private function handleError(Exception $e): void
    {
        $message = "Failed to fetch NFL betting odds: {$e->getMessage()}";

        $this->error($message);
        Log::error($message, [
            'exception' => $e,
            'command' => $this->getName()
        ]);

        Notification::route('discord', config('services.discord.channel_id'))
            ->notify(new DiscordCommandCompletionNotification($message, 'error'));
    }
}