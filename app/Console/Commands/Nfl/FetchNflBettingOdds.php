<?php

namespace App\Console\Commands\Nfl;

use App\Jobs\Nfl\StoreNflBettingOdds;
use Carbon\Carbon;
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

    public function handle()
    {
        try {
            $date = $this->resolveDate();

            if ($date && !$this->validateDate($date)) {
                return 1;
            }

            $currentWeek = $this->getCurrentWeek($date ?? Carbon::today());

            if (!$currentWeek) {
                $this->error('No configured week found for date: ' . ($date ? $date->format('Y-m-d') : 'today'));
                return 1;
            }

            $this->info('Processing NFL betting odds starting from ' . ($date ? $date->format('Y-m-d') : Carbon::today()->format('Y-m-d')));

            $datesProcessed = $this->processDates($date, $currentWeek);

            Log::info('Dates processed:', $datesProcessed);

            return 0;

        } catch (Exception $e) {
            $this->handleError($e);
            return 1;
        }
    }

    private function resolveDate(): ?Carbon
    {
        $dateString = $this->argument('date');

        if ($dateString) {
            try {
                return Carbon::parse($dateString);
            } catch (Exception $e) {
                throw new Exception('Invalid date format. Please use YYYY-MM-DD');
            }
        }

        // Return null if no date is specified
        return null;
    }

    private function validateDate(Carbon $date): bool
    {
        // Allow all dates
        return true;
    }

    private function getCurrentWeek(Carbon $date): ?array
    {
        $weeks = config('nfl.weeks');

        if (empty($weeks)) {
            throw new Exception('NFL weeks configuration is missing or empty');
        }

        // Modify this method as needed based on your week's configuration
        // For simplicity, we'll return a week starting from the given date
        return [
            'start' => $date->copy()->startOfWeek(),
            'end' => $date->copy()->endOfWeek(),
        ];
    }

    private function processDates(?Carbon $date, array $currentWeek): array
    {
        $datesProcessed = [];
        $datesToProcess = $this->getDatesToProcess($date, $currentWeek);

        $progressBar = $this->output->createProgressBar(count($datesToProcess));
        $progressBar->start();

        foreach ($datesToProcess as $processDate) {
            $formattedDate = $processDate->format('Ymd');

            try {
                $job = new StoreNflBettingOdds($formattedDate);
                dispatch($job);

                $datesProcessed[] = $formattedDate;
                $this->logProgress($processDate);

            } catch (Exception $e) {
                Log::error('Failed to process date', [
                    'date' => $formattedDate,
                    'error' => $e->getMessage()
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        return $datesProcessed;
    }

    private function getDatesToProcess(?Carbon $date, array $currentWeek): array
    {
        if ($this->option('week')) {
            return $this->getDatesInRange($currentWeek['start'], $currentWeek['end']);
        }

        if ($nextDays = $this->option('next-days')) {
            return $this->getFutureDates($date ?? Carbon::today(), (int)$nextDays);
        }

        // Default behavior: process next 7 dates
        if (!$date) {
            return $this->getFutureDates(Carbon::today(), 7);
        }

        // If a specific date is provided
        return [$date];
    }

    private function getDatesInRange(Carbon $start, Carbon $end): array
    {
        $dates = [];
        $current = $start->copy();

        while ($current->lessThanOrEqualTo($end)) {
            $dates[] = $current->copy();
            $current->addDay();
        }

        return $dates;
    }

    private function getFutureDates(Carbon $startDate, int $days): array
    {
        $dates = [];
        $current = $startDate->copy();

        for ($i = 0; $i < $days; $i++) {
            $dates[] = $current->copy();
            $current->addDay();
        }

        return $dates;
    }

    private function logProgress(Carbon $date): void
    {
        Log::info('Processing NFL betting odds', [
            'date' => $date->format('Y-m-d'),
            'command' => $this->getName()
        ]);
    }

    private function handleError(Exception $e): void
    {
        $message = "Failed to fetch NFL betting odds: {$e->getMessage()}";

        $this->error($message);
        Log::error($message, [
            'exception' => $e,
            'command' => $this->getName()
        ]);

        // Optionally send a notification
    }
}
