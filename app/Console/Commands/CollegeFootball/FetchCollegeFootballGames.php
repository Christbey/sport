<?php

namespace App\Console\Commands\CollegeFootball;

use App\Jobs\CollegeFootball\StoreCollegeFootballGames;
use Illuminate\Console\Command;

class FetchCollegeFootballGames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:college-football-games {year}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and store college football games from the API';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $year = $this->argument('year');

        // Dispatch the job to fetch and store college football games
        StoreCollegeFootballGames::dispatch($year);

        $this->info("Dispatched job to fetch and store college football games for $year.");

        return 0;
    }
}
