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
    protected $signature = 'nfl:fetch-betting-odds {week? : The week to fetch odds for (default: current week)}';
    protected $description = 'Fetch NFL betting odds for the given week or for all future dates.';

    public function handle(): int
    {
        try {
            $dates = $this->getDatesToFetch();
            $this->processOdds($dates);
            return self::SUCCESS;
        } catch (Exception $e) {
            $this->logError($e);
            return self::FAILURE;
        }
    }

    private function getDatesToFetch(): array
    {
        $week = $this->argument('week') ?? config('nfl.current_week');
        $today = Carbon::today();

        if ($week && isset(config('nfl.weeks')[$week])) {
            $weekConfig = config('nfl.weeks')[$week];
            $weekStart = Carbon::parse($weekConfig['start']);
            $weekEnd = Carbon::parse($weekConfig['end']);
            return collect(CarbonPeriod::create($weekStart, $weekEnd))->toArray();
        }

        return collect(CarbonPeriod::create($today, $today->copy()->addMonths(3)))->toArray(); // Fetch all future odds
    }

    private function processOdds(array $dates): void
    {
        $this->info('Processing NFL betting odds...');
        $progressBar = $this->output->createProgressBar(count($dates));
        $progressBar->start();

        foreach ($dates as $date) {
            try {
                $this->dispatchOddsJob($date);
            } catch (Exception $e) {
                $this->logError($e, $date);
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }

    private function dispatchOddsJob(Carbon $date): void
    {
        $formattedDate = $date->format('Ymd');
        Log::info('Processing NFL betting odds', ['date' => $date->format('Y-m-d')]);
        dispatch(new StoreNflBettingOdds($formattedDate));
    }

    private function logError(Exception $e, ?Carbon $date = null): void
    {
        $context = ['exception' => $e];
        if ($date) {
            $context['date'] = $date->format('Y-m-d');
        }
        $message = "Failed to fetch NFL betting odds: {$e->getMessage()}";
        $this->error($message);
        Log::error($message, $context);
    }
}
