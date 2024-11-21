<?php

namespace App\Console\Commands\CollegeFootball;

use App\Jobs\CollegeFootball\StoreCollegeFootballPregameProbabilities;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchCollegeFootballPregameProbabilities extends Command
{
    protected $signature = 'fetch:college-football-pregame
        {year? : The year to fetch pregame probabilities for}
        {--week= : The week number to fetch}
        {--team= : Specific team to fetch}
        {--season-type= : Season type (regular, postseason, etc.)}';

    protected $description = 'Fetch and store college football pregame win probabilities';

    public function handle()
    {
        try {
            $params = $this->getParameters();

            $this->info("Fetching pregame probabilities for Year: {$params['year']}" .
                ($params['week'] ? ", Week: {$params['week']}" : '') .
                ($params['team'] ? ", Team: {$params['team']}" : ''));

            StoreCollegeFootballPregameProbabilities::dispatch(
                $params['year'],
                $params['week'],
                $params['team'],
                $params['seasonType']
            );

            $this->info('Pregame probabilities fetch job dispatched successfully.');
            return 0;

        } catch (Exception $e) {
            $this->error("Failed to dispatch pregame probabilities fetch job: {$e->getMessage()}");
            Log::error('Pregame probabilities command failed', [
                'error' => $e->getMessage()
            ]);
            return 1;
        }
    }

    private function getParameters(): array
    {
        return [
            'year' => $this->argument('year') ?? config('college_football.season'),
            'week' => $this->option('week'),
            'team' => $this->option('team'),
            'seasonType' => $this->option('season-type')
        ];
    }
}