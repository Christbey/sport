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
    protected $signature = 'calculate:game-differences';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatches the job to calculate game differences and update the hypothetical spreads';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        
        // Dispatch the job to process game differences
        CalculateGameDifferences::dispatch();
        $this->info('CalculateGameDifferences job dispatched.');
    }
}
