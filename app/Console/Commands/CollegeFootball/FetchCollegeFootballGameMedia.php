<?php

namespace App\Console\Commands\CollegeFootball;

use App\Jobs\CollegeFootball\StoreCollegeFootballGameMedia;
use Illuminate\Console\Command;

class FetchCollegeFootballGameMedia extends Command
{
    protected $signature = 'fetch:college-football-media {year} {week?} {seasonType?} {team?} {conference?} {mediaType?} {classification?}';
    protected $description = 'Fetch and store college football game media data';

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
            'mediaType' => $this->argument('mediaType'),
            'classification' => $this->argument('classification'),
        ];

        StoreCollegeFootballGameMedia::dispatch($params);

        $this->info('FetchCollegeFootballGameMedia job dispatched.');
    }
}
