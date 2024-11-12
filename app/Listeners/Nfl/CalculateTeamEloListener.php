<?php

namespace App\Listeners\Nfl;

use App\Events\Nfl\CalculateTeamEloEvent;
use App\Services\EloRatingService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CalculateTeamEloListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param CalculateTeamEloEvent $event
     * @return void
     */
    public function handle(CalculateTeamEloEvent $event)
    {
        try {
            // Initialize the Elo Rating Service
            $eloService = app(EloRatingService::class);

            // Process team predictions using the Elo service
            $finalElo = $eloService->processTeamPredictions(
                $event->team,
                $event->year,
                $event->weeks,
                $event->today
            );

            Log::info("Team: {$event->team} | Final Elo for {$event->year} season: {$finalElo}");
        } catch (Exception $e) {
            Log::error("Error calculating Elo for team {$event->team}: " . $e->getMessage());
            // Optionally, you can dispatch a failure event or handle the error as needed
        }
    }
}
