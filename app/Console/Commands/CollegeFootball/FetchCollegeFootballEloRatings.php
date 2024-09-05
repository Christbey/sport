<?php

namespace App\Console\Commands\CollegeFootball;

use App\Jobs\CollegeFootball\StoreCollegeFootballEloRatings;
use Illuminate\Console\Command;

class FetchCollegeFootballEloRatings extends Command
{
    protected $signature = 'fetch:college-football-elo {year} {week?} {seasonType?} {team?} {conference?}';
    protected $description = 'Fetch and store college football ELO ratings';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $params = [
            'year' => $this->argument('year'),
            'week' => $this->argument('week'),
            'seasonType' => $this->argument('seasonType'),
            'team' => $this->argument('team'),
            'conference' => $this->argument('conference'),
        ];

        StoreCollegeFootballEloRatings::dispatch($params);

        $this->info('ELO ratings job dispatched successfully.');
    }
}
