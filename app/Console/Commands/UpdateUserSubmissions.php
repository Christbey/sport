<?php

namespace App\Console\Commands;

use App\Jobs\UpdateUserSubmissionsJob;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class UpdateUserSubmissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nfl:update-submissions {event_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch a job to update user submissions based on NFL team schedule when a game is marked as completed';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $eventId = $this->argument('event_id');

        // Dispatch the job, optionally with a specific event ID
        UpdateUserSubmissionsJob::dispatch($eventId);

        $this->info('UpdateUserSubmissionsJob has been dispatched.');

        return CommandAlias::SUCCESS;
    }
}
