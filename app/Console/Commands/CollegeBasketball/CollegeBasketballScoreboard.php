<?php

namespace App\Console\Commands\CollegeBasketball;

use App\Jobs\CollegeBasketball\FetchCollegeBasketballScoreboardJob;
use Illuminate\Console\Command;

class CollegeBasketballScoreboard extends Command
{
    protected $signature = 'college-basketball:scoreboard {date?}';
    protected $description = 'Fetch and store specific data for college basketball scoreboard';

    public function handle()
    {
        $dateInput = $this->argument('date') ?? now()->format('Ymd');

        // Dispatch the job to process the scoreboard data
        FetchCollegeBasketballScoreboardJob::dispatch($dateInput);

        $this->info("Job dispatched to fetch college basketball scoreboard data for date: {$dateInput}");
    }
}
