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
        $runId = 'run_' . now()->timestamp;
        Cache::put('nfl_news_last_run', $runId, now()->addHour());

        FetchNflNewsJob::dispatch($runId);

        $this->info('NFL News fetch job dispatched successfully.');
        return 0;
    }
}