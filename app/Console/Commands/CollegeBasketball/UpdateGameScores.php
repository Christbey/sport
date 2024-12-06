<?php

namespace App\Console\Commands;

use App\Jobs\CollegeBasketball\UpdateCollegeBasketballGameScoresJob;
use App\Models\CollegeBasketballGame;
use Illuminate\Console\Command;
use Log;

class UpdateGameScores extends Command
{
    protected $signature = 'update:game-scores {event_id?}';
    protected $description = 'Update the game scores for a specific event ID or all games if no ID is provided';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $eventId = $this->argument('event_id');

        if ($eventId) {
            // Dispatch job for the specific event ID
            UpdateCollegeBasketballGameScoresJob::dispatch($eventId);
            $this->info("Dispatched UpdateCollegeBasketballGameScoresJob for event ID: $eventId");
        } else {
            // Get all event IDs from the CollegeBasketballGame table
            $games = CollegeBasketballGame::whereNotNull('event_id')->get();

            if ($games->isEmpty()) {
                $this->info('No games found with an event ID.');
                return;
            }

            // Dispatch a job for each event ID
            foreach ($games as $game) {
                UpdateCollegeBasketballGameScoresJob::dispatch($game->event_id);
                Log::info("Dispatched UpdateCollegeBasketballGameScoresJob for event ID: {$game->event_id}");
            }

            $this->info('Dispatched jobs to update scores for all games with an event ID.');
        }
    }
}
