<?php

namespace App\Console\Commands;

use App\Jobs\CollegeBasketball\UpdateCollegeBasketballGameScoresJob;
use Illuminate\Console\Command;

class UpdateGameScores extends Command
{
    protected $signature = 'update:game-scores {event_id}';
    protected $description = 'Update the game scores for a specific event ID';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $eventId = $this->argument('event_id');
        UpdateCollegeBasketballGameScoresJob::dispatch($eventId);
        $this->info("Dispatched UpdateCollegeBasketballGameScoresJob for event ID: $eventId");
    }
}
