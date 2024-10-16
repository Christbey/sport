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
        $week = $request->input('week', 8);  // Default to week 6 if none selected

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
            $winnerTeam = $homeWinningPercentage > 0.5 ? CollegeFootballTeam::find($hypothetical->home_team_id) : CollegeFootballTeam::find($hypothetical->away_team_id);

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

        // Fetch average advanced stats for the home and away teams by team_id
        $homeAdvStatsAvg = $this->fetchAdvancedStats($homeTeam->id);
        $awayAdvStatsAvg = $this->fetchAdvancedStats($awayTeam->id);

        // Calculate mismatches based on average advanced stats
        $ppaMismatch = $this->calculateMismatch($homeAdvStatsAvg['offense_ppa'], $awayAdvStatsAvg['defense_ppa']);
        $successRateMismatch = $this->calculateMismatch($homeAdvStatsAvg['offense_success_rate'], $awayAdvStatsAvg['defense_success_rate']);
        $explosivenessMismatch = $this->calculateMismatch($homeAdvStatsAvg['offense_explosiveness'], $awayAdvStatsAvg['defense_explosiveness']);

        // Calculate offense trend (last 3 games)
        $home_offense_trend = $this->calculateTrend($homeTeam->id, 'offense_ppa');
        $away_offense_trend = $this->calculateTrend($awayTeam->id, 'offense_ppa');

        // Determine the projected winner
        $homeWinningPercentage = $this->calculateHomeWinningPercentage(
            $hypothetical->home_elo,
            $hypothetical->away_elo,
            $hypothetical->home_fpi,
            $hypothetical->away_fpi
        );
        $winnerTeam = $homeWinningPercentage > 0.5 ? $homeTeam : $awayTeam;

        // Fetch the last 3 matchups for each team (before today's date)
        $homeTeamLast3Games = $this->fetchLastThreeGames($homeTeam->id, $today);
        $awayTeamLast3Games = $this->fetchLastThreeGames($awayTeam->id, $today);

        // Fetch recent matchups between the two teams (if applicable)
        $recentMatchups = $this->fetchRecentMatchups($homeTeam, $awayTeam);

        // Calculate the outcomes of recent matchups and include scores
        $previousResults = $this->calculateOutcomes($recentMatchups);

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

    private function fetchAdvancedStats($teamId)
    {
        return [
            'offense_ppa' => AdvancedGameStat::where('team_id', $teamId)->avg('offense_ppa') ?? 0,
            'offense_success_rate' => AdvancedGameStat::where('team_id', $teamId)->avg('offense_success_rate') ?? 0,
            'offense_explosiveness' => AdvancedGameStat::where('team_id', $teamId)->avg('offense_explosiveness') ?? 0,
            'defense_ppa' => AdvancedGameStat::where('team_id', $teamId)->avg('defense_ppa') ?? 0,
            'defense_success_rate' => AdvancedGameStat::where('team_id', $teamId)->avg('defense_success_rate') ?? 0,
            'defense_explosiveness' => AdvancedGameStat::where('team_id', $teamId)->avg('defense_explosiveness') ?? 0,
        ];
    }

    // Additional helper functions to encapsulate data fetching and calculations

    private function calculateMismatch($offenseStat, $defenseStat)
    {
        return $offenseStat && $defenseStat ? round($defenseStat - $offenseStat, 5) : 'N/A';
    }

    private function calculateTrend($teamId, $statKey)
    {
        return AdvancedGameStat::where('team_id', $teamId)
            ->orderBy('game_id', 'desc')
            ->limit(3)
            ->avg($statKey) ?? 'N/A';
    }

    private function fetchLastThreeGames($teamId, $beforeDate)
    {
        return CollegeFootballGame::with(['homeTeam', 'awayTeam'])
            ->where(function ($query) use ($teamId) {
                $query->where('home_id', $teamId)->orWhere('away_id', $teamId);
            })
            ->whereDate('start_date', '<', $beforeDate)
            ->orderBy('start_date', 'desc')
            ->limit(3)
            ->get();
    }

    private function fetchRecentMatchups($homeTeam, $awayTeam)
    {
        return CollegeFootballGame::with(['homeTeam', 'awayTeam'])
            ->where(function ($query) use ($homeTeam, $awayTeam) {
                $query->where('home_id', $homeTeam->id)->where('away_id', $awayTeam->id)
                    ->orWhere('home_id', $awayTeam->id)->where('away_id', $homeTeam->id);
            })
            ->orderBy('start_date', 'desc')
            ->get();
    }

    private function calculateOutcomes($games)
    {
        return $games->map(function ($game) {
            $homeWin = $game->home_points > $game->away_points;
            return [
                'date' => $game->start_date,
                'winner' => $homeWin ? $game->homeTeam->school : $game->awayTeam->school,
                'score' => "{$game->home_points} - {$game->away_points}",
            ];
        });
    }

    private function compareSpreads($game_id)
    {
        // Fetch the hypothetical spread for this game
        $hypothetical = CollegeFootballHypothetical::where('game_id', $game_id)->first();

        if (!$hypothetical) {
            return 'No spread available';
        }

        // Simple example: Just return the hypothetical spread for now
        return $hypothetical->hypothetical_spread ?? 'No spread available';
    }
}
