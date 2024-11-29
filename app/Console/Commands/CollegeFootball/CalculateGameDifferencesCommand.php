<?php

namespace App\Console\Commands\CollegeFootball;

use App\Jobs\CollegeFootball\CalculateGameDifferences;
use Illuminate\Console\Command;

class CalculateGameDifferencesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calculate:game-differences {week? : The week number to process (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatches the job to calculate game differences and update hypothetical spreads for a specific week.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get the week parameter or default to null
        $week = $this->argument('week');
        $weeks = config('college_football.weeks');

        // Validate or determine the week
        if ($week) {
            if (!isset($weeks[$week])) {
                $this->error('Invalid week number. Valid weeks are 1 to ' . count($weeks) . '.');
                return;
            }
            $dateRange = $weeks[$week];
            $this->info("Processing week $week ({$dateRange['start']} to {$dateRange['end']}).");
        } else {
            $today = now();
            $week = collect($weeks)->keys()->first(function ($key) use ($weeks, $today) {
                $range = $weeks[$key];
                return $today->between($range['start'], $range['end']);
            });

            if (!$week) {
                $this->error('Could not determine the current week. Specify a week explicitly.');
                return;
            }

            $dateRange = $weeks[$week];
            $this->info("Processing current week ($week) ({$dateRange['start']} to {$dateRange['end']}).");
        }

        // Dispatch the job with the correct week
        CalculateGameDifferences::dispatch($week);
        $this->info('CalculateGameDifferences job dispatched.');
    }
}
