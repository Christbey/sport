<?php
namespace App\Console\Commands;

use App\Jobs\Nfl\StoreNflBettingOdds;
use Illuminate\Console\Command;

class FetchNflBettingOdds extends Command
{
    protected $signature = 'nfl:fetch-betting-odds {date?}';
    protected $description = 'Fetch NFL betting odds for a specific date and store them in the database';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Get the date from the argument or use today's date as default
        $date = $this->argument('date') ?? now()->format('Ymd');

        // Dispatch the job
        dispatch(new StoreNflBettingOdds($date));

        $this->info("Betting odds for date {$date} are being fetched.");
    }
}

