<?php

namespace App\Console\Commands\CollegeBasketball;

use App\Jobs\CollegeBasketball\ScrapeTeamRankingsScheduleJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ScrapeTeamRankingsSchedule extends Command
{
    protected $signature = 'scrape:team-rankings-schedule';
    protected $description = 'Scrapes the Team Rankings schedule table for the next 30 days';

    public function handle()
    {
        $startDate = Carbon::today();

        for ($i = 0; $i < 30; $i++) {
            $currentDate = $startDate->copy()->addDays($i)->toDateString();

            // Dispatch job for each date
            ScrapeTeamRankingsScheduleJob::dispatch($currentDate);

            $this->info("Job dispatched for scraping data on date: $currentDate");
        }

        $this->info('Jobs for scraping Team Rankings schedule have been dispatched for the next 30 days.');
    }
}
