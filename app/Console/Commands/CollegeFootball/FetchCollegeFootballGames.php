<?php

namespace App\Console\Commands\CollegeFootball;

use App\Jobs\CollegeFootball\StoreCollegeFootballGames;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FetchCollegeFootballGames extends Command
{
    protected $signature = 'fetch:college-football-games 
                            {year? : The year to fetch games for}
                            {--week= : The week number for regular season games}
                            {--type= : The season type (regular or postseason)}';

    protected $description = 'Fetch and store college football games from the API';

    public function handle()
    {
        $year = $this->argument('year') ?? config('college_football.season');
        $type = $this->option('type') ?? $this->determineSeasonType();
        $week = $this->option('week') ?? $this->determineWeek($type);

        StoreCollegeFootballGames::dispatch($year, $week, $type);

        $this->info("Dispatched job to fetch and store {$type} season college football games for {$year}, week {$week}.");

        return 0;
    }

    private function determineSeasonType()
    {
        $today = Carbon::today();
        $seasonStart = Carbon::parse(config('college_football.season_start'));
        $seasonEnd = Carbon::parse(config('college_football.season_end'));

        if ($today->between($seasonStart, $seasonEnd)) {
            foreach (config('college_football.regular season.postseason.weeks') as $week => $dates) {
                $weekStart = Carbon::parse($dates['start']);
                $weekEnd = Carbon::parse($dates['end']);
                if ($today->between($weekStart, $weekEnd)) {
                    return 'postseason';
                }
            }
            return 'regular';
        }

        return config('college_football.season_type', 'regular');
    }

    private function determineWeek($type)
    {
        $today = Carbon::today();
        $weeks = $type === 'regular'
            ? config('college_football.regular season.weeks')
            : config('college_football.regular season.postseason.weeks');

        foreach ($weeks as $weekNumber => $dates) {
            $weekStart = Carbon::parse($dates['start']);
            $weekEnd = Carbon::parse($dates['end']);
            if ($today->between($weekStart, $weekEnd)) {
                return $weekNumber;
            }
        }

        return config('college_football.week', 1);
    }
}