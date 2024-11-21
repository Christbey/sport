<?php

namespace App\Console\Commands\CollegeFootball;

use App\Jobs\CollegeFootball\StoreCollegeFootballWeatherData;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchCollegeFootballWeather extends Command
{
    protected $signature = 'fetch:college-football-weather
        {year? : The year to fetch weather data for}
        {--force : Force fetch even if data exists}';

    protected $description = 'Fetch and store college football game weather data';

    public function handle(): int
    {
        try {
            $year = $this->argument('year') ?? config('college_football.season');
            $force = $this->option('force');

            $this->info("Fetching weather data for Year: {$year}");

            StoreCollegeFootballWeatherData::dispatch($year, $force);

            $this->info('Weather data fetch job dispatched successfully.');
            return 0;

        } catch (Exception $e) {
            $this->error("Failed to dispatch weather fetch job: {$e->getMessage()}");
            Log::error('Weather fetch command failed', [
                'error' => $e->getMessage(),
                'year' => $year ?? null
            ]);
            return 1;
        }
    }
}