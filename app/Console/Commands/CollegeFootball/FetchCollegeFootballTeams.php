<?php

namespace App\Console\Commands\CollegeFootball;

use App\Jobs\CollegeFootball\StoreCollegeFootballTeams;
use Illuminate\Console\Command;

class FetchCollegeFootballTeams extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:college-football-teams';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and store college football teams from the API';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {


        StoreCollegeFootballTeams::dispatch();

        $this->info('Dispatched job to fetch and store college football teams.');

        return 0;
    }
}
