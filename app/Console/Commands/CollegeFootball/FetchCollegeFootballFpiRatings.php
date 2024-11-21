<?php

namespace App\Console\Commands\CollegeFootball;

use App\Helpers\CollegeFootballCommandHelpers;
use App\Jobs\CollegeFootball\StoreCollegeFootballFpiRatings;
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

        // Check recent runs using helper
        if (!CollegeFootballCommandHelpers::handleRecentRun(self::CACHE_KEY, $this, $force)) {
            return 0;
        }

        try {
            $this->info("Fetching FPI data for year: {$year}");

            // Check for existing data
            if (!$force) {
                $existingStats = Cache::get(StoreCollegeFootballFpiRatings::CACHE_PREFIX . 'last_success');
                if ($existingStats && $existingStats['year'] == $year) {
                    $this->info('Latest stats from previous run:');
                    CollegeFootballCommandHelpers::displayConsoleStats($existingStats['stats'], $this);
                }
            }

            // Dispatch the job
            StoreCollegeFootballFpiRatings::dispatch($year);
            $this->info('FPI data fetch job dispatched successfully.');

            // Display API information using helper
            CollegeFootballCommandHelpers::displayApiInfo(StoreCollegeFootballFpiRatings::class, $this);

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
}