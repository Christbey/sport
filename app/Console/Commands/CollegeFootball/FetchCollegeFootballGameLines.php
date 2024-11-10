<?php

namespace App\Console\Commands\CollegeFootball;

use App\Helpers\CollegeFootballCommandHelpers;
use App\Jobs\CollegeFootball\StoreCollegeFootballGameLines;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FetchCollegeFootballGameLines extends Command
{
    protected const CACHE_KEY = 'cfb_lines_command_last_run';

    protected $signature = 'fetch:college-football-lines 
        {year? : The year to fetch game lines for}
        {--force : Force fetch even if recent data exists}';

    protected $description = 'Fetch and store college football game lines from DraftKings';

    public function handle()
    {
        $year = $this->argument('year') ?? config('college_football.season');
        $force = $this->option('force');

        // Check recent runs using helper
        if (!CollegeFootballCommandHelpers::handleRecentRun(self::CACHE_KEY, $this, $force)) {
            return 0;
        }

        try {
            $this->info("Fetching game lines for Year: {$year}");

            // Check for existing data
            if (!$force) {
                $existingStats = Cache::get(StoreCollegeFootballGameLines::CACHE_PREFIX . 'last_success');
                if ($existingStats && $existingStats['year'] == $year && !empty($existingStats['stats'])) {
                    $this->info('Latest stats from previous run:');
                    if (isset($existingStats['stats']['updated_teams'])) {
                        CollegeFootballCommandHelpers::displayConsoleStats($existingStats['stats'], $this);
                    }
                }
            }

            // Dispatch the job with parameters as an array
            StoreCollegeFootballGameLines::dispatch([
                'year' => (int)$year,
                'week' => CollegeFootballCommandHelpers::getCurrentWeek(),
                'force' => $force
            ]);

            $this->info('Game lines fetch job dispatched successfully.');

            // Display API information using helper
            CollegeFootballCommandHelpers::displayApiInfo(StoreCollegeFootballGameLines::class, $this);

        } catch (Exception $e) {
            $this->error("Failed to dispatch game lines fetch job: {$e->getMessage()}");
            Log::error('Game lines command failed', [
                'error' => $e->getMessage(),
                'year' => $year
            ]);
            return 1;
        }

        return 0;
    }
}