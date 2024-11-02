<?php

namespace App\Console\Commands\CollegeFootball;

use App\Jobs\CollegeFootball\StoreSpRatingsJob;
use Illuminate\Console\Command;

class FetchSpRatings extends Command
{
    protected $signature = 'fetch:sp-ratings {year?}';
    protected $description = 'Fetch SP+ ratings for a specified year';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Define year from the argument or fallback to the config
        $year = $this->argument('year') ?? config('college_football.season');

        // Dispatch the job with the determined year
        StoreSpRatingsJob::dispatch($year);

        $this->info("SP+ ratings fetch job dispatched for year {$year}.");
    }
}
