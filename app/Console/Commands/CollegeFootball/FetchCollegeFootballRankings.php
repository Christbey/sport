<?php

namespace App\Console\Commands\CollegeFootball;

use App\Jobs\CollegeFootball\StoreCollegeFootballRankingsJob;
use Illuminate\Console\Command;

class FetchCollegeFootballRankings extends Command
{
    protected $signature = 'fetch:college-football-rankings';
    protected $description = 'Scrapes college football rankings and saves them in the Sagarin table';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Dispatch the job to process rankings
        StoreCollegeFootballRankingsJob::dispatch();

        $this->info('College football rankings scraping job dispatched.');
    }
}
