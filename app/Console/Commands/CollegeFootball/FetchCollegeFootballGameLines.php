<?php

namespace App\Console\Commands\CollegeFootball;

use App\Jobs\CollegeFootball\StoreCollegeFootballGameLines;
use Illuminate\Console\Command;

class FetchCollegeFootballGameLines extends Command
{
    protected $signature = 'fetch:college-football-lines {year?}';
    protected $description = 'Fetch and store college football game lines from DraftKings';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $year = $this->argument('year') ?? config('college_football.season');

        // Dispatch the job
        StoreCollegeFootballGameLines::dispatch($year);

        $this->info('Job to fetch and store college football game lines has been dispatched.');


    }
}
