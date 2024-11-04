<?php

namespace App\Console\Commands;

use App\Jobs\FetchNflDepthChartJob;
use Illuminate\Console\Command;

class FetchNflDepthChart extends Command
{
    protected $signature = 'nfl:fetch-depth-chart';
    protected $description = 'Fetch NFL depth charts and store the response in the database';

    public function handle()
    {
        FetchNflDepthChartJob::dispatch();
        $this->info('NFL Depth Chart fetch job dispatched successfully.');
        return 0;
    }
}
