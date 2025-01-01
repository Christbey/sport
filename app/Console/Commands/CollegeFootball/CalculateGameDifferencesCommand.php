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
    protected $signature = 'calculate:game-differences 
        {week? : The week number to process (optional)}
        {--type=regular : Season type (regular or postseason)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatches the job to calculate game differences and update hypothetical spreads for a specific week and season type.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $seasonType = $this->option('type');
        if (!in_array($seasonType, ['regular', 'postseason'])) {
            $this->error('Invalid season type. Must be either "regular" or "postseason".');
            return;
        }

        // Get the week parameter or default to null
        $week = $this->argument('week');

        if ($seasonType === 'regular') {
            $weeks = config('college_football.weeks');

            // Validate or determine the week for regular season
            if ($week) {
                if (!isset($weeks[$week])) {
                    $this->error('Invalid week number. Valid weeks are 1 to ' . count($weeks) . '.');
                    return;
                }
                $dateRange = $weeks[$week];
                $this->info("Processing regular season week $week ({$dateRange['start']} to {$dateRange['end']}).");
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
                $this->info("Processing current regular season week ($week) ({$dateRange['start']} to {$dateRange['end']}).");
            }
        } else {
            // For postseason, just use the provided week or default to 1
            $week = $week ?: 1;
            $this->info("Processing postseason week $week.");
        }

        // Dispatch the job with the correct week and season type
        CalculateGameDifferences::dispatch($week, $seasonType);
        $this->info("CalculateGameDifferences job dispatched for {$seasonType} season.");
    }
}