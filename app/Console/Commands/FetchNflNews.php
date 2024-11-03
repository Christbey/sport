<?php

namespace App\Console\Commands;

use App\Jobs\FetchNflNewsJob;
use Illuminate\Console\Command;

class FetchNflNews extends Command
{
    protected $signature = 'nfl:news';
    protected $description = 'Fetch NFL news and store the response in the database';

    public function handle()
    {
        FetchNflNewsJob::dispatch();
        $this->info('NFL News fetch job dispatched successfully.');
        return 0;
    }
}
