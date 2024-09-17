<?php

namespace App\Console\Commands\CollegeFootball;

use App\Jobs\CollegeFootball\StoreCollegeFootballFpiRatings;
use Illuminate\Console\Command;

class FetchCollegeFootballFpiRatings extends Command
{
    protected $signature = 'fetch:college-football-fpi {year?}';
    protected $description = 'Fetch and store college football FPI data';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $year = $this->argument('year') ?? config('college_football.season');
        StoreCollegeFootballFpiRatings::dispatch($year);

        $this->info('FPI data fetch job dispatched.');
    }
}
