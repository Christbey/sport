<?php

namespace App\Console\Commands\CollegeFootball;

use App\Helpers\CollegeFootballCommandHelpers;
use App\Jobs\CollegeFootball\StoreCollegeFootballRankingsJob;
use App\Models\CollegeFootball\Sagarin;
use Exception;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class FetchCollegeFootballRankings extends Command
{
    protected const CACHE_KEY = 'cfb_rankings_command_last_run';

    protected $signature = 'fetch:college-football-rankings
        {--force : Force fetch even if recent data exists}
        {--queue= : Specify queue to use for the job}';

    protected $description = 'Scrapes college football Sagarin rankings and saves them in the database';

    public function handle()
    {
        return CollegeFootballCommandHelpers::handleCommand(
            $this,
            self::CACHE_KEY,
            function () {
                try {
                    // Show last update info if available
                    $lastUpdate = Sagarin::latest('updated_at')->first();
                    if ($lastUpdate) {
                        $stats = [
                            'updated_teams' => Sagarin::count(),
                            'last_update' => $lastUpdate->updated_at
                        ];

                        CollegeFootballCommandHelpers::displayConsoleStats($stats, $this);
                    }

                    // Dispatch the job
                    $job = new StoreCollegeFootballRankingsJob();

                    if ($queue = $this->option('queue')) {
                        $job->onQueue($queue);
                    }

                    dispatch($job);

                    $this->info('College football rankings scraping job dispatched successfully.');
                    $this->info('Check Discord for results once the job completes.');

                    // Check data freshness
                    $warnings = CollegeFootballCommandHelpers::checkDataFreshness([
                        'Sagarin Rankings' => [Sagarin::class, 7] // Warning if data is older than 7 days
                    ]);

                    if (!empty($warnings)) {
                        foreach ($warnings as $warning) {
                            $this->warn($warning);
                        }
                    }

                    return 0;

                } catch (Exception $e) {
                    CollegeFootballCommandHelpers::handleCommandError($this, $e);
                    return 1;
                }
            },
            $this->option('force')
        );
    }

    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Force fetch even if recent data exists'],
            ['queue', null, InputOption::VALUE_OPTIONAL, 'Specify queue to use for the job'],
        ];
    }
}