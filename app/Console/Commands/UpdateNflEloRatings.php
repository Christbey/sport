<?php

namespace App\Console\Commands;

use App\Models\Nfl\NflTeamSchedule;
use Illuminate\Console\Command;
use App\Services\EloRatingService;

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
        // Get the season year from the command argument
        $year = $this->argument('year');

        // Fetch all unique team abbreviations from the nfl_team_schedules table
        $teams = NflTeamSchedule::select('home_team')
            ->union(NflTeamSchedule::select('away_team'))
            ->distinct()
            ->pluck('home_team');

        // Iterate through each team and calculate their Elo for the season
        foreach ($teams as $team) {
            $this->info("Calculating Elo, expected wins, and spreads for team: $team");

            // Print expected wins and predicted spreads for the team
            $this->eloService->printExpectedWinsAndSpreadForTeam($team, $year);

            // Calculate the Elo rating for the team
            $finalElo = $this->eloService->calculateTeamEloForSeason($team, $year);

            // Print the result for the team
            $this->info("Team: $team | Final Elo for $year season: $finalElo");
        }

        $this->info('Elo calculation, expected wins, and spreads for all teams is completed.');
    }
}
