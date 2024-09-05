<?php

namespace App\Console\Commands\CollegeFootball;

use App\Jobs\CollegeFootball\StoreCollegeFootballPregameProbabilities;
use Illuminate\Console\Command;

class FetchCollegeFootballPregameProbabilities extends Command
{
    protected $signature = 'fetch:college-football-pregame {year} {week?} {team?} {seasonType?}';
    protected $description = 'Fetch and store college football pregame win probabilities data';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $year = $this->argument('year');
        $week = $this->argument('week');
        $team = $this->argument('team');
        $seasonType = $this->argument('seasonType');

        StoreCollegeFootballPregameProbabilities::dispatch($year, $week, $team, $seasonType);

        $this->info('Pregame win probabilities data fetching has been initiated.');
    }
}
