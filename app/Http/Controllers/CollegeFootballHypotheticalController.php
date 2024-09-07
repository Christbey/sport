<?php

namespace App\Http\Controllers;

use App\Models\CollegeFootball\CollegeFootballGame;
use App\Models\CollegeFootballHypothetical;
use App\Models\CollegeFootball\CollegeFootballTeam;
use Illuminate\Http\Request;

class CollegeFootballHypotheticalController extends Controller
{
    public function index(Request $request)
    {
        // Get the selected week from the request, default to the current week
        $week = $request->input('week', 2);  // Default to week 2 if none selected

        // Fetch all distinct weeks for the dropdown
        $weeks = CollegeFootballHypothetical::select('week')->distinct()->orderBy('week', 'asc')->get();

        // Fetch games for the selected week
        $hypotheticals = CollegeFootballHypothetical::where('week', $week)->get();

        // Calculate home winning percentage for each game and determine the projected winner
        foreach ($hypotheticals as $hypothetical) {
            $homeElo = $hypothetical->home_elo;
            $awayElo = $hypothetical->away_elo;
            $homeFpi = $hypothetical->home_fpi;
            $awayFpi = $hypothetical->away_fpi;

            // Calculate the home winning percentage using ELO and FPI values
            $homeWinningPercentage = $this->calculateHomeWinningPercentage($homeElo, $awayElo, $homeFpi, $awayFpi);
            $hypothetical->home_winning_percentage = $homeWinningPercentage;

            // Determine the projected winner and fetch their team color
            if ($homeWinningPercentage > 0.5) {
                // Home team is projected to win
                $winnerTeam = CollegeFootballTeam::find($hypothetical->home_team_id);
            } else {
                // Away team is projected to win
                $winnerTeam = CollegeFootballTeam::find($hypothetical->away_team_id);
            }

            // Attach the winner's color to the hypothetical
            $hypothetical->winner_color = $winnerTeam->color ?? '#000000'; // Default to black if no color is found
        }

        // Pass the games, weeks, and selected week to the view
        return view('cfb.index', compact('hypotheticals', 'weeks', 'week'));
    }

    private function calculateHomeWinningPercentage($homeElo, $awayElo, $homeFpi, $awayFpi)
    {
        // Calculate probability using ELO
        $eloProbability = 1 / (1 + pow(10, ($awayElo - $homeElo) / 400));

        // Calculate probability using FPI
        $fpiProbability = 1 / (1 + pow(10, ($awayFpi - $homeFpi) / 10));

        // Average the two probabilities (or adjust weights as needed)
        return round(($eloProbability + $fpiProbability) / 2, 4); // Rounded to 4 decimal places
    }

}