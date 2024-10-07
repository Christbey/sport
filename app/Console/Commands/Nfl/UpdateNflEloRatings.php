<?php

namespace App\Console\Commands\Nfl;

use App\Services\EloRatingService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class UpdateNflEloRatings extends Command
{
    protected $signature = 'nfl:calculate-team-elo {year}';
    protected $description = 'Calculate Elo rating, expected wins, and spreads for all NFL teams for a given season';

    protected $eloService;

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
            $predictions = $this->eloService->calculateTeamEloForSeason($team, $year);

            foreach ($predictions['predictions'] as $prediction) {
                $week = $prediction['week'];

                // Check if week exists in the config
                if (!isset($weeks[$week])) {
                    $this->eloService->logMissingWeek($week, $year);
                    continue;
                }

                $weekEnd = Carbon::parse($weeks[$week]['end']);

                // Find the game and handle prediction storage via the service
                $this->eloService->storePredictionIfNeeded($team, $prediction, $year, $weekEnd, $today);
            }

            $this->info("Team: $team | Final Elo for $year season: {$predictions['final_elo']}");
        }

        $this->info('Elo calculation, expected wins, and spreads for all teams is completed.');
    }
}
