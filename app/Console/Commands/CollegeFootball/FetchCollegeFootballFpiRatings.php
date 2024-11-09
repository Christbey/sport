<?php

namespace App\Console\Commands\CollegeFootball;

use App\Jobs\CollegeFootball\StoreCollegeFootballFpiRatings;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FetchCollegeFootballFpiRatings extends Command
{
    protected const CACHE_KEY = 'cfb_fpi_command_last_run';
    protected $signature = 'fetch:college-football-fpi 
        {year? : The year to fetch FPI data for} 
        {--force : Force fetch even if recent data exists}';
    protected $description = 'Fetch and store college football FPI data';

    public function handle()
    {
        $year = $this->argument('year') ?? config('college_football.season');
        $force = $this->option('force');

        // Check if we've run recently (within last 6 hours) unless forced
        if (!$force && $this->hasRecentRun()) {
            $lastRun = Carbon::parse(Cache::get(self::CACHE_KEY));
            $this->warn("Command was already run {$lastRun->diffForHumans()}.");

            if (!$this->confirm('Do you want to run it again?')) {
                return;
            }
        }

        try {
            $this->info("Fetching FPI data for year: {$year}");

            // Store command execution time
            Cache::put(self::CACHE_KEY, now(), now()->addDay());

            // Check for existing data
            if (!$force) {
                $existingStats = Cache::get(StoreCollegeFootballFpiRatings::CACHE_PREFIX . 'last_success');
                if ($existingStats && $existingStats['year'] == $year) {
                    $this->info("Latest stats from previous run:");
                    $this->displayStats($existingStats['stats']);
                }
            }

            // Dispatch the job
            StoreCollegeFootballFpiRatings::dispatch($year);

            $this->info('FPI data fetch job dispatched successfully.');

            // Display API calls info if available
            $apiCalls = StoreCollegeFootballFpiRatings::getApiCallsToday();
            if ($apiCalls > 0) {
                $this->info("API calls today: {$apiCalls}");
            }

            // Check for recent errors
            $lastError = StoreCollegeFootballFpiRatings::getLastError();
            if ($lastError && Carbon::parse($lastError['time'])->isToday()) {
                $this->warn("Note: There was an error in the last run:");
                $this->error($lastError['message']);
            }

        } catch (Exception $e) {
            $this->error("Failed to dispatch FPI fetch job: {$e->getMessage()}");
            Log::error('FPI command failed', [
                'error' => $e->getMessage(),
                'year' => $year
            ]);
            return 1;
        }

        return 0;
    }

    protected function hasRecentRun(): bool
    {
        $lastRun = Cache::get(self::CACHE_KEY);
        return $lastRun && Carbon::parse($lastRun)->diffInHours() < 6;
    }

    protected function displayStats(array $stats): void
    {
        $this->line('');
        $this->line('Previous Run Statistics:');
        $this->line('---------------------');
        $this->info("Teams Updated: {$stats['updated_teams']}");

        if (!empty($stats['missing_teams'])) {
            $this->warn("Missing Teams: " . implode(', ', array_slice($stats['missing_teams'], 0, 3)) .
                (count($stats['missing_teams']) > 3 ? " and " . (count($stats['missing_teams']) - 3) . " more" : ""));
        }

        if (!empty($stats['significant_changes'])) {
            $this->line('');
            $this->info('Significant FPI Changes:');
            foreach (array_slice($stats['significant_changes'], 0, 3) as $change) {
                $direction = $change['change'] > 0 ? '↑' : '↓';
                $this->line(sprintf(
                    "%s %s: %.2f → %.2f (%+.2f)",
                    $direction,
                    $change['team'],
                    $change['previous'],
                    $change['new'],
                    $change['change']
                ));
            }
            if (count($stats['significant_changes']) > 3) {
                $this->line("... and " . (count($stats['significant_changes']) - 3) . " more changes");
            }
        }
        $this->line('');
    }
}
