<?php

namespace App\Console\Commands\CollegeFootball;

use App\Jobs\CollegeFootball\StoreCollegeFootballPregameWinProbabilities;
use Illuminate\Console\Command;

class FetchCollegeFootballPregameWinProbabilities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:college-football-pregame-win-probabilities {year}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and store pregame win probabilities from the API';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $year = $this->argument('year');

        // Dispatch the job to fetch and store pregame win probabilities
        StoreCollegeFootballPregameWinProbabilities::dispatch($year);

        $this->info("Dispatched job to fetch and store pregame win probabilities for $year.");

        return 0;
    }
}
