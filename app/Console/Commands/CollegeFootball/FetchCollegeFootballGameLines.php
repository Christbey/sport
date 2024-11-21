<?php

namespace App\Console\Commands\CollegeFootball;

use App\Jobs\CollegeFootball\StoreCollegeFootballGameLines;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FetchCollegeFootballGameLines extends Command
{
    protected $signature = 'fetch:college-football-lines 
        {year? : The year to fetch game lines for}
        {--force : Force fetch even if recent data exists}';

    protected $description = 'Fetch and store college football game lines from DraftKings';

    public function handle()
    {
        try {
            $year = $this->argument('year') ?? config('college_football.season');
            $force = $this->option('force');

            // Check if we've run recently (within last 5 minutes)
            if (!$force && Cache::get('cfb_lines_last_run') && !$this->confirmRun()) {
                return 0;
            }

            $this->info("Fetching game lines for Year: {$year}");

            // Show previous stats if available
            $this->displayPreviousStats($year);

            // Dispatch job
            StoreCollegeFootballGameLines::dispatch([
                'year' => (int)$year,
                'force' => $force
            ]);

            Cache::put('cfb_lines_last_run', now(), now()->addMinutes(5));

            $this->info('Game lines fetch job dispatched successfully.');
            $this->displayApiCalls();

            return 0;

        } catch (Exception $e) {
            $this->error("Failed to dispatch game lines fetch job: {$e->getMessage()}");
            Log::error('Game lines command failed', ['error' => $e->getMessage(), 'year' => $year ?? null]);
            return 1;
        }
    }

    private function confirmRun(): bool
    {
        return $this->confirm('This command was run recently. Run again?');
    }

    private function displayPreviousStats(int $year): void
    {
        $stats = Cache::get('cfb_lines_job_last_success');

        if (!$stats || $stats['year'] !== $year || empty($stats['stats'])) {
            return;
        }

        $this->info('Latest stats from previous run:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Updated Teams', $stats['stats']['updated_teams'] ?? 0],
                ['Missing Teams', count($stats['stats']['missing_teams'] ?? [])],
                ['Changed Lines', count($stats['stats']['changed_lines'] ?? [])]
            ]
        );
    }

    private function displayApiCalls(): void
    {
        $calls = Cache::get('cfb_lines_api_calls_' . now()->format('Y-m-d'), 0);
        $this->info("API calls today: {$calls}");
    }
}