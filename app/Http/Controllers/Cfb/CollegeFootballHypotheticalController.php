<?php

namespace App\Http\Controllers\Cfb;

use App\Http\Controllers\Controller;
use App\Models\CollegeFootball\AdvancedGameStat;
use App\Models\CollegeFootball\CollegeFootballGame;
use App\Models\CollegeFootball\CollegeFootballHypothetical;
use App\Models\CollegeFootball\CollegeFootballNote;
use App\Models\CollegeFootball\CollegeFootballTeam;
use App\Models\CollegeFootball\SpRating;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CollegeFootballHypotheticalController extends Controller
{
    public function index(Request $request)
    {
        // Determine the current week based on the date, defaulting to the configured week if none is provided
        $week = $request->input('week', $this->getCurrentWeek());

        // Fetch all distinct weeks for the dropdown
        $weeks = CollegeFootballHypothetical::select('week')->distinct()->orderBy('week', 'asc')->get();

        // Join with the college_football_games table and order by start_date
        $hypotheticals = CollegeFootballHypothetical::where('college_football_hypotheticals.week', $week)
            ->join('college_football_games', 'college_football_hypotheticals.game_id', '=', 'college_football_games.id')
            ->orderBy('college_football_games.start_date', 'asc')
            ->select(
                'college_football_hypotheticals.*',
                'college_football_games.start_date',
                'college_football_games.completed',
                'college_football_games.formatted_spread',
                'college_football_games.home_points',   // Added home points
                'college_football_games.away_points'    // Added away points
            )
            ->with('game') // Eager load the game relationship
            ->get();

        // Calculate home winning percentage and determine the projected winner
        foreach ($hypotheticals as $hypothetical) {
            $homeElo = $hypothetical->home_elo;
            $awayElo = $hypothetical->away_elo;
            $homeFpi = $hypothetical->home_fpi;
            $awayFpi = $hypothetical->away_fpi;

            $homeWinningPercentage = $this->calculateHomeWinningPercentage($homeElo, $awayElo, $homeFpi, $awayFpi);
            $hypothetical->home_winning_percentage = $homeWinningPercentage;

            $winnerTeam = $homeWinningPercentage > 0.5
                ? CollegeFootballTeam::find($hypothetical->home_team_id)
                : CollegeFootballTeam::find($hypothetical->away_team_id);

            $hypothetical->winner_color = $winnerTeam->color ?? '#000000'; // Default to black if no color is found
        }

        // Pass data to the view
        return view('cfb.index', compact('hypotheticals', 'weeks', 'week'));
    }

    /**
     * Determine the current week based on today's date.
     *
     * @return int
     */
    private function getCurrentWeek()
    {
        $today = Carbon::today();

        foreach (config('college_football.weeks') as $weekNumber => $range) {
            $start = Carbon::parse($range['start']);
            $end = Carbon::parse($range['end']);

            if ($today->between($start, $end)) {
                return $weekNumber;
            }
        }

        return 1; // Default to week 1 if no matching range is found
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
        // Fetch the necessary game, team, and stats data (as you already have)
        $hypothetical = CollegeFootballHypothetical::where('game_id', $game_id)->firstOrFail();
        $game = CollegeFootballGame::findOrFail($game_id);
        $homeTeam = CollegeFootballTeam::find($hypothetical->home_team_id);
        $awayTeam = CollegeFootballTeam::find($hypothetical->away_team_id);

        // Fetch advanced stats for home and away teams
        $homeStats = $this->fetchAdvancedStats($homeTeam->id);
        $awayStats = $this->fetchAdvancedStats($awayTeam->id);


        // Calculate winning percentage for home team
        $homeElo = $hypothetical->home_elo;
        $awayElo = $hypothetical->away_elo;
        $homeFpi = $hypothetical->home_fpi;
        $awayFpi = $hypothetical->away_fpi;

        // Fetch SP+ ratings for the home and away teams
        $homeSpRating = SpRating::where('team', $homeTeam->school)->first();
        $awaySpRating = SpRating::where('team', $awayTeam->school)->first();

        // Fetch notes for the home and away teams
        $homeTeamNotes = CollegeFootballNote::where('team_id', $homeTeam->id)->get();
        $awayTeamNotes = CollegeFootballNote::where('team_id', $awayTeam->id)->get();


        $homeWinningPercentage = $this->calculateHomeWinningPercentage($homeElo, $awayElo, $homeFpi, $awayFpi);


        $winnerTeam = $homeWinningPercentage > 0.5 ? $homeTeam : $awayTeam;


        // Define all metrics to include in the view
        $metrics = [
            'offense_plays', 'offense_drives', 'offense_ppa', 'offense_total_ppa', 'offense_success_rate',
            'offense_explosiveness', 'offense_power_success', 'offense_stuff_rate', 'offense_line_yards',
            'offense_line_yards_total', 'offense_second_level_yards', 'offense_second_level_yards_total',
            'offense_open_field_yards', 'offense_open_field_yards_total', 'offense_standard_downs_ppa',
            'offense_standard_downs_success_rate', 'offense_standard_downs_explosiveness', 'offense_passing_downs_ppa',
            'offense_passing_downs_success_rate', 'offense_passing_downs_explosiveness', 'offense_rushing_ppa',
            'offense_rushing_total_ppa', 'offense_rushing_success_rate', 'offense_rushing_explosiveness',
            'offense_passing_ppa', 'offense_passing_total_ppa', 'offense_passing_success_rate',
            'offense_passing_explosiveness', 'defense_plays', 'defense_drives', 'defense_ppa', 'defense_total_ppa',
            'defense_success_rate', 'defense_explosiveness', 'defense_power_success', 'defense_stuff_rate',
            'defense_line_yards', 'defense_line_yards_total', 'defense_second_level_yards',
            'defense_second_level_yards_total', 'defense_open_field_yards', 'defense_open_field_yards_total',
            'defense_standard_downs_ppa', 'defense_standard_downs_success_rate', 'defense_standard_downs_explosiveness',
            'defense_passing_downs_ppa', 'defense_passing_downs_success_rate', 'defense_passing_downs_explosiveness',
            'defense_rushing_ppa', 'defense_rushing_total_ppa', 'defense_rushing_success_rate',
            'defense_rushing_explosiveness', 'defense_passing_ppa', 'defense_passing_total_ppa',
            'defense_passing_success_rate', 'defense_passing_explosiveness'
        ];
        $mismatches = [
            'Net PPA Differential' => $this->calculateMismatch($homeStats['offense_ppa'], $awayStats['defense_ppa']),
            'Success Rate Differential' => $this->calculateMismatch($homeStats['offense_success_rate'], $awayStats['defense_success_rate']),
            'Explosiveness Differential' => $this->calculateMismatch($homeStats['offense_explosiveness'], $awayStats['defense_explosiveness']),
            // Add additional mismatches as required
        ];

        // Calculate trends based on recent offensive stats
        $home_offense_trend = $this->calculateTrend($homeTeam->id, 'offense_ppa');
        $away_offense_trend = $this->calculateTrend($awayTeam->id, 'offense_ppa');


        // Prepare the stats array for the view
        $statsData = [];
        foreach ($metrics as $metric) {
            $awayValue = $awayStats[$metric] ?? 0;
            $homeValue = $homeStats[$metric] ?? 0;
            $statsData[$metric] = [
                'home' => $homeValue,
                'away' => $awayValue,
                'total' => $awayValue - $homeValue,
            ];
        }

        // Fetch the last three games for each team
        $beforeDate = $game->start_date; // Assuming `start_date` is the date to compare against
        $homeTeamLast3Games = $this->fetchLastThreeGames($homeTeam->id, $beforeDate);
        $awayTeamLast3Games = $this->fetchLastThreeGames($awayTeam->id, $beforeDate);

        // Fetch previous matchups between the teams
        $previousResults = $this->fetchRecentMatchups($homeTeam, $awayTeam);

        return view('cfb.detail', compact('hypothetical', 'game', 'homeTeamLast3Games', 'previousResults', 'awayTeamLast3Games', 'homeTeam', 'awayTeam', 'statsData', 'winnerTeam', 'homeWinningPercentage', 'homeSpRating', 'awaySpRating', 'homeTeamNotes', 'awayTeamNotes', 'mismatches', 'home_offense_trend', 'away_offense_trend'));
    }

    private function fetchAdvancedStats($teamId)
    {
        // List of all metrics expected in the stats arrays
        $metrics = [
            'offense_plays', 'offense_drives', 'offense_ppa', 'offense_total_ppa', 'offense_success_rate',
            'offense_explosiveness', 'offense_power_success', 'offense_stuff_rate', 'offense_line_yards',
            'offense_line_yards_total', 'offense_second_level_yards', 'offense_second_level_yards_total',
            'offense_open_field_yards', 'offense_open_field_yards_total', 'offense_standard_downs_ppa',
            'offense_standard_downs_success_rate', 'offense_standard_downs_explosiveness', 'offense_passing_downs_ppa',
            'offense_passing_downs_success_rate', 'offense_passing_downs_explosiveness', 'offense_rushing_ppa',
            'offense_rushing_total_ppa', 'offense_rushing_success_rate', 'offense_rushing_explosiveness',
            'offense_passing_ppa', 'offense_passing_total_ppa', 'offense_passing_success_rate',
            'offense_passing_explosiveness', 'defense_plays', 'defense_drives', 'defense_ppa', 'defense_total_ppa',
            'defense_success_rate', 'defense_explosiveness', 'defense_power_success', 'defense_stuff_rate',
            'defense_line_yards', 'defense_line_yards_total', 'defense_second_level_yards',
            'defense_second_level_yards_total', 'defense_open_field_yards', 'defense_open_field_yards_total',
            'defense_standard_downs_ppa', 'defense_standard_downs_success_rate', 'defense_standard_downs_explosiveness',
            'defense_passing_downs_ppa', 'defense_passing_downs_success_rate', 'defense_passing_downs_explosiveness',
            'defense_rushing_ppa', 'defense_rushing_total_ppa', 'defense_rushing_success_rate',
            'defense_rushing_explosiveness', 'defense_passing_ppa', 'defense_passing_total_ppa',
            'defense_passing_success_rate', 'defense_passing_explosiveness'
        ];

        // Initialize all metrics with default values (0 or any other placeholder)
        $stats = array_fill_keys($metrics, 0);

        // Fill in actual averages for each metric
        foreach ($metrics as $metric) {
            $stats[$metric] = AdvancedGameStat::where('team_id', $teamId)->avg($metric) ?? 0;
        }

        return $stats;
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
