<?php

namespace App\Console\Commands\Nfl;

use App\Jobs\Nfl\CalculateTeamElo;
use App\Notifications\DiscordCommandCompletionNotification;
use App\Services\EloRatingService;
use Exception;
use Illuminate\Console\Command;
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

            foreach ($teams as $team) {
                CalculateTeamElo::dispatch($team, $year, $weeks); // Dispatch job for each team without passing current week separately
                $this->info("Dispatched Elo calculation job for team: {$team}");
            }

            $this->info('Elo calculation jobs for all teams have been dispatched.');

            $this->info('All NFL team schedules dispatched successfully.');
            // Send success notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification('', 'success'));

        } catch (Exception $e) {
            // Send failure notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($e->getMessage(), 'error'));

        }
    }
}
