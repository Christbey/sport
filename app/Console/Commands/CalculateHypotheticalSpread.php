<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CollegeFootball\CollegeFootballGame;
use App\Models\CollegeFootball\CollegeFootballElo;
use App\Models\CollegeFootball\CollegeFootballFpi;
use Illuminate\Support\Facades\Log;

class CalculateHypotheticalSpread extends Command
{
    protected $signature = 'calculate:hypothetical-spreads';
    protected $description = 'Calculate hypothetical spreads for all games in CollegeFootballGame table where home_division = "fbs"';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $games = CollegeFootballGame::where('home_division', 'fbs')
            ->where('season',2024)
            ->get();

        foreach ($games as $game) {
            // Fetch home and away team information
            $homeTeam = $game->homeTeam;
            $awayTeam = $game->awayTeam;
            $year = $game->year;

            // Check if the home and away teams are properly loaded
            if (!$homeTeam || !$awayTeam) {
                $message = "Missing team data for game ID {$game->id}. Home or away team is null.";
                $this->error($message);
                Log::warning($message);
                continue;
            }

            // Fetch ELO and FPI data for both teams
            $homeElo = CollegeFootballElo::where('team_id', $homeTeam->id)->where('year', $game->season)->value('elo');
            $awayElo = CollegeFootballElo::where('team_id', $awayTeam->id)->where('year', $game->season)->value('elo');
            $homeFpi = CollegeFootballFpi::where('team_id', $homeTeam->id)->where('year', $game->season)->value('fpi');
            $awayFpi = CollegeFootballFpi::where('team_id', $awayTeam->id)->where('year', $game->season)->value('fpi');


            // Check for missing ELO or FPI data
            if ($homeElo === null || $awayElo === null || $homeFpi === null || $awayFpi === null) {
                $message = "ELO or FPI data missing for {$homeTeam->school} vs {$awayTeam->school} in $year.";
                $this->error($message);
                Log::warning($message);
                continue;
            }

            // Calculate the spread
            $spread = $this->calculateHypotheticalSpread($homeFpi, $awayFpi, $homeElo, $awayElo);

            // Log the result to both terminal and log file
            $message = "Hypothetical Spread for {$awayTeam->school} @ {$homeTeam->school}: $spread";
            $this->info($message);
            Log::info($message);
        }
    }

    // Function to calculate spread using only ELO and FPI
    private function calculateHypotheticalSpread($homeFpi, $awayFpi, $homeElo, $awayElo): float
    {
        $fpiSpread = $homeFpi && $awayFpi ? ($homeFpi - $awayFpi) / 2 : 0;
        $eloSpread = $homeElo && $awayElo ? ($homeElo - $awayElo) / 25 : 0;

        return round(($fpiSpread + $eloSpread) / 1.4, 2); // Adjust divisor as necessary
    }
}
