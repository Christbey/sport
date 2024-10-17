<?php

namespace App\Console\Commands\Nfl;

use App\Services\EloRatingService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class UpdateNflEloRatings extends Command
{
    protected $signature = 'nfl:calculate-team-elo {year}';
    protected $description = 'Calculate Elo rating, expected wins, and spreads for all NFL teams for a given season';

    protected EloRatingService $eloService;

    public function __construct(EloRatingService $eloService)
    {
        parent::__construct();
        $this->eloService = $eloService;
    }

    public function handle()
    {
        $year = $this->argument('year');
        $today = Carbon::now();
        $weeks = config('nfl.weeks'); // Load the weeks configuration

        // Fetch all unique team abbreviations using the service
        $teams = $this->eloService->fetchTeams();

        foreach ($teams as $team) {
            $this->info("Calculating Elo, expected wins, and spreads for team: $team");

            // Calculate the Elo rating and predictions for the team
            $finalElo = $this->eloService->processTeamPredictions($team, $year, $weeks, $today);

            $this->info("Team: $team | Final Elo for $year season: {$finalElo}");
        }

        $this->info('Elo calculation, expected wins, and spreads for all teams are completed.');
    }
}
