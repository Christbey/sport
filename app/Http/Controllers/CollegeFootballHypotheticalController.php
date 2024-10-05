<?php

namespace App\Http\Controllers;

use App\Models\CollegeFootball\AdvancedGameStat;
use App\Models\CollegeFootball\CollegeFootballGame;
use App\Models\CollegeFootball\CollegeFootballHypothetical;
use App\Models\CollegeFootball\CollegeFootballTeam;
use App\Models\CollegeFootball\SpRating;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CollegeFootballHypotheticalController extends Controller
{
    public function index(Request $request)
    {
        // Get the selected week from the request, default to the current week
        $week = $request->input('week', 6);  // Default to week 2 if none selected

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

    private function calculateHomeWinningPercentage($homeElo, $awayElo, $homeFpi, $awayFpi)
    {
        // Calculate probability using ELO
        $eloProbability = 1 / (1 + pow(10, ($awayElo - $homeElo) / 400));

        // Calculate probability using FPI
        $fpiProbability = 1 / (1 + pow(10, ($awayFpi - $homeFpi) / 10));

        // Average the two probabilities (or adjust weights as needed)
        return round(($eloProbability + $fpiProbability) / 2, 4); // Rounded to 4 decimal places
    }

    public function show($game_id)
    {
        // Fetch today's date for filtering games
        $today = Carbon::today();

        // Fetch the hypothetical spread details based on the game_id
        $hypothetical = CollegeFootballHypothetical::where('game_id', $game_id)->firstOrFail();
        $game = CollegeFootballGame::find($game_id);

        // Fetch home and away team details
        $homeTeam = CollegeFootballTeam::find($hypothetical->home_team_id);
        $awayTeam = CollegeFootballTeam::find($hypothetical->away_team_id);

        // Ensure that both teams exist
        if (!$homeTeam || !$awayTeam) {
            abort(404, 'Team not found');
        }

        // Fetch SP+ ratings for the home and away teams
        $homeSpRating = SpRating::where('team', $homeTeam->school)->first();
        $awaySpRating = SpRating::where('team', $awayTeam->school)->first();

        // Fetch average advanced stats for the home team by team_id
        $homeAdvStatsAvg = [
            'offense_ppa' => AdvancedGameStat::where('team_id', $homeTeam->id)->avg('offense_ppa') ?? 0,
            'offense_success_rate' => AdvancedGameStat::where('team_id', $homeTeam->id)->avg('offense_success_rate') ?? 0,
            'offense_explosiveness' => AdvancedGameStat::where('team_id', $homeTeam->id)->avg('offense_explosiveness') ?? 0,
            'defense_ppa' => AdvancedGameStat::where('team_id', $homeTeam->id)->avg('defense_ppa') ?? 0,
            'defense_success_rate' => AdvancedGameStat::where('team_id', $homeTeam->id)->avg('defense_success_rate') ?? 0,
            'defense_explosiveness' => AdvancedGameStat::where('team_id', $homeTeam->id)->avg('defense_explosiveness') ?? 0,
        ];

// Fetch average advanced stats for the away team by team_id
        $awayAdvStatsAvg = [
            'offense_ppa' => AdvancedGameStat::where('team_id', $awayTeam->id)->avg('offense_ppa') ?? 0,
            'offense_success_rate' => AdvancedGameStat::where('team_id', $awayTeam->id)->avg('offense_success_rate') ?? 0,
            'offense_explosiveness' => AdvancedGameStat::where('team_id', $awayTeam->id)->avg('offense_explosiveness') ?? 0,
            'defense_ppa' => AdvancedGameStat::where('team_id', $awayTeam->id)->avg('defense_ppa') ?? 0,
            'defense_success_rate' => AdvancedGameStat::where('team_id', $awayTeam->id)->avg('defense_success_rate') ?? 0,
            'defense_explosiveness' => AdvancedGameStat::where('team_id', $awayTeam->id)->avg('defense_explosiveness') ?? 0,
        ];

        // Calculate mismatches based on average advanced stats
        $ppaMismatch = $homeAdvStatsAvg['offense_ppa'] && $awayAdvStatsAvg['defense_ppa']
            ? round($awayAdvStatsAvg['defense_ppa'] - $homeAdvStatsAvg['offense_ppa'], 5)
            : 'N/A';

        $successRateMismatch = $homeAdvStatsAvg['offense_success_rate'] && $awayAdvStatsAvg['defense_success_rate']
            ? round($awayAdvStatsAvg['defense_success_rate'] - $homeAdvStatsAvg['offense_success_rate'], 5)
            : 'N/A';

        $explosivenessMismatch = $homeAdvStatsAvg['offense_explosiveness'] && $awayAdvStatsAvg['defense_explosiveness']
            ? round($awayAdvStatsAvg['defense_explosiveness'] - $homeAdvStatsAvg['offense_explosiveness'], 5)
            : 'N/A';

        // Calculate offense trend (last 3 games)
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

        // Fetch the last 3 matchups for each team (before today's date)
        $homeTeamLast3Games = CollegeFootballGame::with(['homeTeam', 'awayTeam'])
            ->where(function ($query) use ($homeTeam) {
                $query->where('home_id', $homeTeam->id)
                    ->orWhere('away_id', $homeTeam->id);
            })
            ->whereDate('start_date', '<', $today) // Only games before today
            ->orderBy('start_date', 'desc')
            ->limit(3)
            ->get();

        $awayTeamLast3Games = CollegeFootballGame::with(['homeTeam', 'awayTeam'])
            ->where(function ($query) use ($awayTeam) {
                $query->where('home_id', $awayTeam->id)
                    ->orWhere('away_id', $awayTeam->id);
            })
            ->whereDate('start_date', '<', $today) // Only games before today
            ->orderBy('start_date', 'desc')
            ->limit(3)
            ->get();

        // Fetch recent matchups between the two teams (if applicable)
        $recentMatchups = CollegeFootballGame::with(['homeTeam', 'awayTeam'])
            ->where(function ($query) use ($homeTeam, $awayTeam) {
                $query->where(function ($q) use ($homeTeam, $awayTeam) {
                    $q->where('home_id', $homeTeam->id)
                        ->where('away_id', $awayTeam->id);
                })
                    ->orWhere(function ($q) use ($homeTeam, $awayTeam) {
                        $q->where('home_id', $awayTeam->id)
                            ->where('away_id', $homeTeam->id);
                    });
            })
            ->orderBy('start_date', 'desc')
            ->get();

        // Calculate the outcomes of recent matchups and include scores
        $previousResults = $recentMatchups->map(function ($game) {
            $homeWin = $game->home_points > $game->away_points;
            return [
                'date' => $game->start_date,
                'winner' => $homeWin ? $game->homeTeam->school : $game->awayTeam->school,
                'score' => "{$game->home_points} - {$game->away_points}", // Pass the score
            ];
        });

        // Compare spreads and determine the smart pick
        $smartPick = $this->compareSpreads($game_id);

        // Pass all the data to the view
        return view('cfb.detail', compact(
            'hypothetical', 'game', 'homeTeam', 'awayTeam',
            'homeSpRating', 'awaySpRating',
            'ppaMismatch', 'successRateMismatch', 'explosivenessMismatch',
            'home_offense_trend', 'away_offense_trend',
            'homeWinningPercentage', 'winnerTeam', 'smartPick',
            'homeTeamLast3Games', 'awayTeamLast3Games', 'recentMatchups', 'previousResults'
        ));
    }

    public function compareSpreads($gameId)
    {
        // Fetch the hypothetical and game data
        $hypothetical = CollegeFootballHypothetical::where('game_id', $gameId)->first();
        $game = CollegeFootballGame::find($gameId);

        if (!$hypothetical || !$game) {
            return 'Game or hypothetical data not found.';
        }

        // Fetch the home and away team details
        $homeTeam = CollegeFootballTeam::find($hypothetical->home_team_id);
        $awayTeam = CollegeFootballTeam::find($hypothetical->away_team_id);

        // Use the hypothetical and DraftKings spreads as they are (positive or negative matters)
        $hypotheticalSpread = $hypothetical->hypothetical_spread;
        $draftKingsSpread = $game->draftkings_spread;
        $formattedSpread = $game->formatted_spread; // Assuming 'formatted_spread' exists in 'college_football_games'

        // Determine the favorite and underdog based on the hypothetical spread
        $underdogTeam = $hypotheticalSpread < 0 ? $homeTeam : $awayTeam;  // If hypothetical spread is negative, home team is the underdog
        $favoriteTeam = $hypotheticalSpread < 0 ? $awayTeam : $homeTeam;  // If hypothetical spread is negative, away team is the favorite

        // Initialize the smart pick variable
        $smartPick = '';

        // Check if the spreads are too close to call (within 2.5 points)
        if (abs($hypotheticalSpread - $draftKingsSpread) <= 2.5) {
            $smartPick = 'Too close to call';
        } // If hypothetical spread predicts a smaller win/loss than DraftKings, underdog is the smart pick
        elseif (abs($hypotheticalSpread) < abs($draftKingsSpread)) {
            $smartPick = "The smart pick is the underdog, {$underdogTeam->school}, according to the hypothetical spread. The Vegas line is {$formattedSpread}.";
        } // If hypothetical spread predicts a larger win/loss than DraftKings, favorite is the smart pick
        else {
            $smartPick = "The smart pick is the favorite, {$favoriteTeam->school}, according to the hypothetical spread. The Vegas line is {$formattedSpread}.";
        }

        // Add result comparison if actual results exist
        if ($game->actual_result_margin) {
            $actualMargin = $game->actual_result_margin;
            if ($actualMargin > abs($draftKingsSpread)) {
                $smartPick .= " (Actual result: away team won by {$actualMargin}, covering the spread)";
            } else {
                $smartPick .= ' (Actual result: home team performed better than expected)';
            }
        }

        return $smartPick;
    }

    // Controller or logic to compare spreads and make a recommendation

    public function updateCorrect(Request $request, $id)
    {
        // Validate the input
        $request->validate([
            'correct' => 'required|boolean',
            // 'team_id' => 'required|exists:college_football_teams,id', // Ensure a valid team is selected
        ]);

        // Find the hypothetical by id
        $hypothetical = CollegeFootballHypothetical::findOrFail($id);

        // Update the 'correct' and 'team_id' fields
        $hypothetical->update([
            'correct' => $request->input('correct'),
            'side' => $request->input('team_id'), // Store selected team's ID
        ]);

        return redirect()->route('cfb.hypothetical.show', $hypothetical->game_id)
            ->with('success', 'Prediction outcome and team updated successfully.');
    }
}