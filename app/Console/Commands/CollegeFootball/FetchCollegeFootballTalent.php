<?php

namespace App\Console\Commands\CollegeFootball;

use App\Jobs\CollegeFootball\StoreCollegeFootballTalent;
use Illuminate\Console\Command;

class FetchCollegeFootballTalent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:college-football-talent {year}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and store college football talent data from the API';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $year = $this->argument('year');

        // Dispatch the job to store talent data for the specified year
        StoreCollegeFootballTalent::dispatch($year);

        $this->info("Dispatched job to fetch and store college football talent data for the year $year.");

        return 0;
    }
}
