<?php

namespace App\Console\Commands\CollegeFootball;

use App\Jobs\CollegeFootball\StoreCollegeFootballWeatherData;
use Illuminate\Console\Command;

class FetchCollegeFootballWeatherData extends Command
{
    protected $signature = 'fetch:college-football-weather {year}';
    protected $description = 'Fetch and store college football weather data';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $year = $this->argument('year');
        StoreCollegeFootballWeatherData::dispatch($year);

        $this->info('Weather data fetching job dispatched successfully.');
    }
}
