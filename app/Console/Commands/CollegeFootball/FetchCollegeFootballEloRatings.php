<?php

namespace App\Console\Commands\CollegeFootball;

use App\Jobs\CollegeFootball\StoreCollegeFootballEloRatings;
use Illuminate\Console\Command;

class FetchCollegeFootballEloRatings extends Command
{
    // Define the command signature with optional arguments
    protected $signature = 'fetch:college-football-elo {year?} {week?} {seasonType?} {team?} {conference?}';
    protected $description = 'Fetch and store college football ELO ratings';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // If the 'year' argument is not provided, use the value from the config
        $year = $this->argument('year') ?? config('college_football.season');

        // Prepare parameters for the job
        $params = [
            'year' => $year,
            'week' => $this->argument('week'),
            'seasonType' => $this->argument('seasonType'),
            'team' => $this->argument('team'),
            'conference' => $this->argument('conference'),
        ];

        // Dispatch the job to store ELO ratings
        StoreCollegeFootballEloRatings::dispatch($params);

        // Output a success message
        $this->info('ELO ratings job dispatched successfully.');
    }
}
