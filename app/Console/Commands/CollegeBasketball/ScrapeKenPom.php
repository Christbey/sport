<?php

namespace App\Console\Commands\CollegeBasketball;

use App\Jobs\CollegeBasketball\ScrapeKenPomJob;
use Illuminate\Console\Command;

class ScrapeKenPom extends Command
{
    protected $signature = 'scrape:kenpom';
    protected $description = 'Scrape the KenPom rankings page';


    public function handle()
    {
        // Dispatch the job to handle the scraping and data processing
        ScrapeKenPomJob::dispatch();

        $this->info('Job dispatched to scrape KenPom rankings.');
    }
}
