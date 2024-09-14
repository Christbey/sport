<?php

namespace App\Http\Controllers;

use App\Models\CollegeFootball\AdvancedGameStat;
use App\Models\CollegeFootball\CollegeFootballGame;
use App\Models\CollegeFootball\CollegeFootballHypothetical;
use App\Models\CollegeFootball\CollegeFootballTeam;
use App\Models\CollegeFootball\SpRating;
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
                $winnerTeam = CollegeFootballTeam::find($hypothetical->home_team_id);
            } else {
                $winnerTeam = CollegeFootballTeam::find($hypothetical->away_team_id);
            }

            // Attach the winner's color to the hypothetical
            $hypothetical->winner_color = $winnerTeam->color ?? '#000000'; // Default to black if no color is found
        }

        // Pass the games, weeks, and selected week to the view
        return view('cfb.index', compact('hypotheticals', 'weeks', 'week'));
    }

    public function show($game_id)
    {
        // Fetch the hypothetical spread details based on the game_id
        $hypothetical = CollegeFootballHypothetical::where('game_id', $game_id)->firstOrFail();
        $game = CollegeFootballGame::find($game_id);

        // Fetch home and away team details
        $homeTeam = CollegeFootballTeam::find($hypothetical->home_team_id);
        $awayTeam = CollegeFootballTeam::find($hypothetical->away_team_id);

        // Fetch SP+ ratings for the home and away teams
        $homeSpRating = SpRating::where('team', $homeTeam->school)->first();
        $awaySpRating = SpRating::where('team', $awayTeam->school)->first();

        // Fetch average advanced stats for the home and away teams by team_id
        $homeAdvStatsAvg = [
            'offense_ppa' => AdvancedGameStat::where('team_id', $homeTeam->id)->avg('offense_ppa'),
            'offense_success_rate' => AdvancedGameStat::where('team_id', $homeTeam->id)->avg('offense_success_rate'),
            'offense_explosiveness' => AdvancedGameStat::where('team_id', $homeTeam->id)->avg('offense_explosiveness'),
            'defense_ppa' => AdvancedGameStat::where('team_id', $homeTeam->id)->avg('defense_ppa'),
            'defense_success_rate' => AdvancedGameStat::where('team_id', $homeTeam->id)->avg('defense_success_rate'),
            'defense_explosiveness' => AdvancedGameStat::where('team_id', $homeTeam->id)->avg('defense_explosiveness'),
        ];

        $awayAdvStatsAvg = [
            'offense_ppa' => AdvancedGameStat::where('team_id', $awayTeam->id)->avg('offense_ppa'),
            'offense_success_rate' => AdvancedGameStat::where('team_id', $awayTeam->id)->avg('offense_success_rate'),
            'offense_explosiveness' => AdvancedGameStat::where('team_id', $awayTeam->id)->avg('offense_explosiveness'),
            'defense_ppa' => AdvancedGameStat::where('team_id', $awayTeam->id)->avg('defense_ppa'),
            'defense_success_rate' => AdvancedGameStat::where('team_id', $awayTeam->id)->avg('defense_success_rate'),
            'defense_explosiveness' => AdvancedGameStat::where('team_id', $awayTeam->id)->avg('defense_explosiveness'),
        ];

        // Calculate mismatches based on average advanced stats
        $ppaMismatch = $homeAdvStatsAvg['offense_ppa'] && $awayAdvStatsAvg['defense_ppa']
            ? round(  $awayAdvStatsAvg['defense_ppa']- $homeAdvStatsAvg['offense_ppa'], 5)
            : 'N/A';

        $successRateMismatch = $homeAdvStatsAvg['offense_success_rate'] && $awayAdvStatsAvg['defense_success_rate']
            ? round( $awayAdvStatsAvg['defense_success_rate']-$homeAdvStatsAvg['offense_success_rate'] , 5)
            : 'N/A';

        $explosivenessMismatch = $homeAdvStatsAvg['offense_explosiveness'] && $awayAdvStatsAvg['defense_explosiveness']
            ? round( $awayAdvStatsAvg['defense_explosiveness']-$homeAdvStatsAvg['offense_explosiveness'], 5)
            : 'N/A';

        // Calculate offense trend (for example, you can average the last 3 games offense_ppa)
        $home_offense_trend = AdvancedGameStat::where('team_id', $homeTeam->id)
            ->orderBy('game_id', 'desc')
            ->limit(3)
            ->avg('offense_ppa') ?? 'N/A';

        $away_offense_trend = AdvancedGameStat::where('team_id', $awayTeam->id)
            ->orderBy('game_id', 'desc')
            ->limit(3)
            ->avg('offense_ppa') ?? 'N/A';

        // Determine the projected winner
        $homeWinningPercentage = $this->calculateHomeWinningPercentage(
            $hypothetical->home_elo,
            $hypothetical->away_elo,
            $hypothetical->home_fpi,
            $hypothetical->away_fpi
        );
        $winnerTeam = $homeWinningPercentage > 0.5 ? $homeTeam : $awayTeam;

        // Pass the data to the view
        return view('cfb.detail', compact(
            'hypothetical', 'game', 'homeTeam', 'awayTeam',
            'homeSpRating', 'awaySpRating',
            'ppaMismatch', 'successRateMismatch', 'explosivenessMismatch',
            'home_offense_trend', 'away_offense_trend',
            'homeWinningPercentage', 'winnerTeam'
        ));
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

    public function updateCorrect(Request $request, $id)
    {
        // Validate the input
        $request->validate([
            'correct' => 'required|boolean',
        ]);

        // Find the hypothetical by id
        $hypothetical = CollegeFootballHypothetical::findOrFail($id);

        // Update the 'correct' field
        $hypothetical->update([
            'correct' => $request->input('correct'),
        ]);

        return redirect()->route('cfb.hypothetical.show', $hypothetical->game_id)
            ->with('success', 'Prediction outcome updated successfully.');
    }

}
