<?php

namespace App\Console\Commands\CollegeFootball;

use App\Jobs\CollegeFootball\StoreCollegeFootballEloRatings;
use Carbon\Carbon;
use Illuminate\Console\Command;
use NotificationChannels\Discord\Discord;

class FetchCollegeFootballEloRatings extends Command
{
    protected $signature = 'fetch:college-football-elo {year?} {week?} {seasonType?} {team?} {conference?}';
    protected $description = 'Fetch and store college football ELO ratings';

    protected Discord $discord;

    public function __construct(Discord $discord)
    {
        parent::__construct();
        $this->discord = $discord;
    }

    public function handle(): void
    {
        $year = $this->argument('year') ?? config('college_football.season');
        $week = $this->argument('week') ?? $this->getCurrentWeek();

        $params = [
            'year' => $year,
            'week' => $week,
            'seasonType' => $this->argument('seasonType') ?? 'regular',
            'team' => $this->argument('team'),
            'conference' => $this->argument('conference'),
        ];

        // Dispatch the job to store ELO ratings
        StoreCollegeFootballEloRatings::dispatch($params);

        $this->info('ELO ratings job dispatched successfully.');
    }

    private function getCurrentWeek(): int
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

        // Default to the last week if no match is found
        return array_key_last($weeks);
    }
}
