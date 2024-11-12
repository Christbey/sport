<?php

namespace App\Console\Commands\Nfl;

use App\Events\Nfl\CalculateTeamEloEvent;
use App\Notifications\DiscordCommandCompletionNotification;
use App\Services\EloRatingService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class UpdateNflEloRatings extends Command
{
    protected $signature = 'nfl:calculate-team-elo {year?}';
    protected $description = 'Calculate Elo rating, expected wins, and spreads for all NFL teams for a given season';

    protected EloRatingService $eloService;

    public function __construct(EloRatingService $eloService)
    {
        parent::__construct();
        $this->eloService = $eloService;
    }

    public function handle()
    {
        try {
            $year = $this->argument('year') ?? config('nfl.seasonYear'); // Get the year or fallback to config
            $weeks = config('nfl.weeks');
            $teams = $this->eloService->fetchTeams(); // Fetch all unique teams
            $today = Carbon::now(); // Current date and time

            foreach ($teams as $team) {
                // Ensure $team is a string and not null
                if (is_null($team) || !is_string($team)) {
                    Log::warning("Invalid team name encountered: {$team}");
                    $this->error("Invalid team name encountered: {$team}");
                    continue; // Skip dispatching for this team
                }

                // Dispatch the CalculateTeamEloEvent with necessary data
                event(new CalculateTeamEloEvent($team, $year, $weeks, $today));

                // Log the success message
                $this->info("CalculateTeamEloEvent dispatched for team: {$team}");
                Log::info("CalculateTeamEloEvent dispatched for team: {$team}");
            }

            $this->info('Elo calculation events for all teams have been dispatched.');
            Log::info('Elo calculation events for all teams have been dispatched.');

            // Send success notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification('', 'success'));

        } catch (Exception $e) {
            // Send failure notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($e->getMessage(), 'error'));

            // Log the exception for debugging
            Log::error('Error in UpdateNflEloRatings command: ' . $e->getMessage());
            $this->error('An error occurred: ' . $e->getMessage());
        }
    }
}
