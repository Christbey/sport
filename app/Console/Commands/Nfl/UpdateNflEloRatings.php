<?php

namespace App\Console\Commands\Nfl;

use App\Models\Nfl\NflTeamSchedule;
use App\Services\EloRatingService;
use Illuminate\Console\Command;
use App\Models\NflEloPrediction;

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
            $predictions = $this->eloService->calculateTeamEloForSeason($team, $year);

            // Save each prediction to the database
            foreach ($predictions['predictions'] as $prediction) {
                NflEloPrediction::create([
                    'team' => $prediction['team'],
                    'opponent' => $prediction['opponent'],
                    'year' => $year,
                    'week' => $prediction['week'],
                    'team_elo' => $prediction['team_elo'],
                    'opponent_elo' => $prediction['opponent_elo'],
                    'expected_outcome' => $prediction['expected_outcome'],
                ]);
            }

            // Print the result for the team
            $this->info("Team: $team | Final Elo for $year season: {$predictions['final_elo']}");
        }

        $this->info('Elo calculation, expected wins, and spreads for all teams is completed.');
    }
}
