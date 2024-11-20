<?php

namespace App\Console\Commands\Nfl;

use App\Jobs\Nfl\StoreNflBettingOdds;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchNflBettingOdds extends Command
{
    protected $signature = 'nfl:fetch-betting-odds 
        {date? : The date to fetch odds for (YYYY-MM-DD)}
        {--week : Fetch for entire week}
        {--next-days= : Number of future days to fetch odds for}
        {--force : Force fetch even if already run today}';

    protected $description = 'Fetch NFL betting odds for the next 7 dates by default, or for a specific date or range of dates';

    /**
     * @throws Exception
     */
    public function handle(): int
    {
        try {
            $startDate = $this->getStartDate();
            $dates = $this->getDates($startDate);

            return $this->processOdds($dates);
        } catch (Exception $e) {
            $this->logError($e);
            return self::FAILURE;
        }
    }

    /**
     * Get the start date for fetching odds.
     */
    private function getStartDate(): Carbon
    {
        if ($dateString = $this->argument('date')) {
            try {
                return Carbon::parse($dateString);
            } catch (Exception) {
                throw new Exception('Invalid date format. Please use YYYY-MM-DD');
            }
        }

        return Carbon::today();
    }

    /**
     * Get the dates to process based on command options.
     */
    private function getDates(Carbon $startDate): array
    {
        if ($this->option('week')) {
            return $this->getWeekDates($startDate);
        }

        $days = (int)$this->option('next-days', 7);
        return $this->getFutureDates($startDate, $days);
    }

    /**
     * Get dates for the entire week.
     */
    private function getWeekDates(Carbon $date): array
    {
        $weekStart = $date->copy()->startOfWeek();
        $weekEnd = $date->copy()->endOfWeek();

        return collect(CarbonPeriod::create($weekStart, $weekEnd))
            ->map(fn($date) => $date->copy())
            ->toArray();
    }

    /**
     * Get future dates from start date.
     */
    private function getFutureDates(Carbon $startDate, int $days): array
    {
        return collect(CarbonPeriod::create($startDate, $startDate->copy()->addDays($days - 1)))
            ->map(fn($date) => $date->copy())
            ->toArray();
    }

    /**
     * Process betting odds for given dates.
     */
    private function processOdds(array $dates): int
    {
        $this->info('Processing NFL betting odds from ' . $dates[0]->format('Y-m-d'));

        $progressBar = $this->output->createProgressBar(count($dates));
        $progressBar->start();

        $success = true;

        foreach ($dates as $date) {
            try {
                $this->dispatchOddsJob($date);
                $progressBar->advance();
            } catch (Exception $e) {
                $this->logError($e, $date);
                $success = false;
            }
        }

        $progressBar->finish();
        $this->newLine();

        if (!$success) {
            $this->warn('Some dates failed to process. Check the logs for details.');
        }

        return $success ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Dispatch the job to store betting odds.
     */
    private function dispatchOddsJob(Carbon $date): void
    {
        $formattedDate = $date->format('Ymd');

        Log::info('Processing NFL betting odds', [
            'date' => $date->format('Y-m-d'),
            'command' => $this->getName()
        ]);

        dispatch(new StoreNflBettingOdds($formattedDate));
    }

    /**
     * Log error messages.
     */
    private function logError(Exception $e, ?Carbon $date = null): void
    {
        $context = [
            'exception' => $e,
            'command' => $this->getName(),
        ];

        if ($date) {
            $context['date'] = $date->format('Y-m-d');
        }

        $message = "Failed to fetch NFL betting odds: {$e->getMessage()}";

        $this->error($message);
        Log::error($message, $context);
    }
}