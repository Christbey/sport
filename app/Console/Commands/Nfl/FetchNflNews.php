<?php

namespace App\Console\Commands\Nfl;

use App\Jobs\Nfl\FetchNflNewsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class FetchNflNews extends Command
{
    protected $signature = 'nfl:news';
    protected $description = 'Fetch NFL news and store the response in the database';

    public function handle()
    {
        $this->cacheLastRunId();
        $this->dispatchFetchJob();
        $this->info('NFL News fetch job dispatched successfully.');
        return 0;
    }

    private function cacheLastRunId(): void
    {
        Cache::put('nfl_news_last_run', 'run_' . now()->timestamp, now()->addHour());
    }

    private function dispatchFetchJob(): void
    {
        FetchNflNewsJob::dispatch();
    }
}