<?php

namespace App\Console\Commands\CollegeFootball;

use App\Jobs\CollegeFootball\StoreCollegeFootballGameMedia;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FetchCollegeFootballGameMedia extends Command
{
    protected $signature = 'fetch:college-football-media {year?} {week?} {seasonType?} {team?} {conference?} {mediaType?} {classification?}';
    protected $description = 'Fetch and store college football game media data';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $params = [
            'year' => $this->argument('year') ?? config('college_football.season'),
            'week' => $this->argument('week') ?? $this->getCurrentWeek(),
            'seasonType' => $this->argument('seasonType'),
            'team' => $this->argument('team'),
            'conference' => $this->argument('conference'),
            'mediaType' => $this->argument('mediaType'),
            'classification' => $this->argument('classification'),
        ];

        StoreCollegeFootballGameMedia::dispatch($params);

        $this->info('FetchCollegeFootballGameMedia job dispatched.');
    }

    /**
     * Determine the current week based on today's date and config settings.
     *
     * @return int|null
     */
    private function getCurrentWeek(): ?int
    {
        $today = Carbon::today();
        $weeks = config('college_football.weeks');

        foreach ($weeks as $weekNumber => $dates) {
            $start = Carbon::parse($dates['start']);
            $end = Carbon::parse($dates['end']);

            if ($today->between($start, $end)) {
                return $weekNumber;
            }
        }

        return null; // Return null if no matching week is found
    }
}
