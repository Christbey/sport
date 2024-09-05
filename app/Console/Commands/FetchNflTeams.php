<?php

namespace App\Console\Commands;

use App\Jobs\Nfl\StoreNflTeams;
use App\Jobs\Nfl\StoreNflEspnTeams;
use Illuminate\Console\Command;

class FetchNflTeams extends Command
{
    protected $signature = 'fetch:nfl-teams';
    protected $description = 'Fetch and store NFL teams data from multiple sources';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Dispatch the job to fetch and store NFL teams data from the first API
        StoreNflTeams::dispatch();
        $this->info('NFL teams data fetching job (RapidAPI) dispatched successfully.');

        // Dispatch the job to fetch and store NFL teams data from ESPN API
        StoreNflEspnTeams::dispatch();
        $this->info('NFL teams data fetching job (ESPN API) dispatched successfully.');
    }
}
