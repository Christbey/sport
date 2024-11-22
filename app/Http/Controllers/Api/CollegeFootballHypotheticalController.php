<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CollegeFootballCollection;
use App\Models\CollegeFootball\AdvancedGameStat;
use App\Models\CollegeFootball\CollegeFootballGame;
use App\Models\CollegeFootball\CollegeFootballHypothetical;
use App\Models\CollegeFootball\CollegeFootballNote;
use App\Models\CollegeFootball\CollegeFootballTeam;
use App\Models\CollegeFootball\SpRating;
use App\Services\EnhancedFootballAnalytics;
use Carbon\Carbon;
use Illuminate\Http\Request;


class CollegeFootballHypotheticalController extends Controller
{
    protected $analytics;

    public function __construct(EnhancedFootballAnalytics $analytics)
    {
        $this->analytics = $analytics;
    }

    public function index(Request $request)
    {
        $week = $request->input('week', $this->getCurrentWeek());

        // Fetch weeks for dropdown
        $weeks = CollegeFootballHypothetical::select('week')
            ->distinct()
            ->orderBy('week', 'asc')
            ->get();

        // Main query for hypotheticals
        $hypotheticals = CollegeFootballHypothetical::where('college_football_hypotheticals.week', $week)
            ->join('college_football_games', 'college_football_hypotheticals.game_id', '=', 'college_football_games.id')
            ->orderBy('college_football_games.start_date', 'asc')
            ->select(
                'college_football_hypotheticals.*',
                'college_football_games.start_date',
                'college_football_games.completed',
                'college_football_games.formatted_spread',
                'college_football_games.home_points',
                'college_football_games.away_points'
            )
            ->with('game')
            ->get();

        // Calculate additional data
        foreach ($hypotheticals as $hypothetical) {
            $homeWinningPercentage = $this->calculateHomeWinningPercentage(
                $hypothetical->home_elo,
                $hypothetical->away_elo,
                $hypothetical->home_fpi,
                $hypothetical->away_fpi
            );

            $hypothetical->home_winning_percentage = $homeWinningPercentage;

            $winnerTeam = $homeWinningPercentage > 0.5
                ? CollegeFootballTeam::find($hypothetical->home_team_id)
                : CollegeFootballTeam::find($hypothetical->away_team_id);

            $hypothetical->winner_color = $winnerTeam->color ?? '#000000';
        }

        if ($request->wantsJson()) {
            return (new CollegeFootballCollection($hypotheticals))
                ->additional([
                    'meta' => [
                        'weeks' => $weeks,
                        'current_week' => $week
                    ]
                ]);
        }

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


    public function show(Request $request, $game_id)
    {
        // First, fetch all required data
        $hypothetical = CollegeFootballHypothetical::where('game_id', $game_id)->firstOrFail();
        $game = CollegeFootballGame::findOrFail($game_id);

        // Explicitly fetch teams with error checking
        $homeTeam = CollegeFootballTeam::findOrFail($hypothetical->home_team_id);
        $awayTeam = CollegeFootballTeam::findOrFail($hypothetical->away_team_id);

        // Fetch stats
        $homeStats = $this->fetchAdvancedStats($homeTeam->id);
        $awayStats = $this->fetchAdvancedStats($awayTeam->id);

        // Calculate winning percentage
        $homeWinningPercentage = $this->calculateHomeWinningPercentage(
            $hypothetical->home_elo,
            $hypothetical->away_elo,
            $hypothetical->home_fpi,
            $hypothetical->away_fpi
        );

        // Determine winner
        $winnerTeam = $homeWinningPercentage > 0.5 ? $homeTeam : $awayTeam;

        // Fetch SP+ ratings
        $homeSpRating = SpRating::where('team', $homeTeam->school)->first();
        $awaySpRating = SpRating::where('team', $awayTeam->school)->first();

        // Fetch team notes
        $homeTeamNotes = CollegeFootballNote::where('team_id', $homeTeam->id)->get();
        $awayTeamNotes = CollegeFootballNote::where('team_id', $awayTeam->id)->get();

        // Calculate analytics
        $efficiencyMetrics = $this->analytics->calculateEfficiencyMetrics($homeStats, $awayStats);
        $matchupAdvantages = $this->analytics->calculateMatchupAdvantages($homeStats, $awayStats);
        $scoringPrediction = $this->analytics->calculateScoringPrediction($homeStats, $awayStats);
        $driveMetrics = $this->analytics->calculateDriveMetrics($homeStats, $awayStats);

        // Calculate mismatches and trends
        $mismatches = $this->calculateMismatches($homeStats, $awayStats);
        $homeOffenseTrend = $this->calculateTrend($homeTeam->id, 'offense_ppa');
        $awayOffenseTrend = $this->calculateTrend($awayTeam->id, 'offense_ppa');

        // Fetch game history
        $homeTeamLast3Games = $this->fetchLastThreeGames($homeTeam->id, $game->start_date);
        $awayTeamLast3Games = $this->fetchLastThreeGames($awayTeam->id, $game->start_date);
        $previousResults = $this->fetchRecentMatchups($homeTeam, $awayTeam);

        if ($request->wantsJson()) {
            return response()->json([
                'data' => [
                    'hypothetical' => $hypothetical,
                    'game' => $game,
                    'teams' => [
                        'home' => $homeTeam,
                        'away' => $awayTeam,
                    ],
                    // ... rest of your JSON structure
                ]
            ]);
        }

        // Return view with all required data
        return view('cfb.detail', compact(
            'hypothetical',
            'game',
            'homeTeam',
            'awayTeam',
            'homeStats',
            'awayStats',
            'winnerTeam',
            'homeWinningPercentage',
            'homeSpRating',
            'awaySpRating',
            'homeTeamNotes',
            'awayTeamNotes',
            'mismatches',
            'homeOffenseTrend',
            'awayOffenseTrend',
            'efficiencyMetrics',
            'matchupAdvantages',
            'scoringPrediction',
            'driveMetrics',
            'homeTeamLast3Games',
            'awayTeamLast3Games',
            'previousResults'
        ));
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

    private function calculateMismatches($homeStats, $awayStats)
    {
        // Ensure we're accessing specific stats correctly
        return [
            'Net PPA Differential' => $this->calculateMismatch(
                $awayStats['offense_ppa'] ?? null,
                $homeStats['defense_ppa'] ?? null
            ),
            'Success Rate Differential' => $this->calculateMismatch(
                $homeStats['offense_success_rate'] ?? null,
                $awayStats['defense_success_rate'] ?? null
            ),
            'Explosiveness Differential' => $this->calculateMismatch(
                $homeStats['offense_explosiveness'] ?? null,
                $awayStats['defense_explosiveness'] ?? null
            ),
            'Power Success Rate Differential' => $this->calculateMismatch(
                $homeStats['offense_power_success'] ?? null,
                $awayStats['defense_power_success'] ?? null
            ),
            'Stuff Rate Differential' => $this->calculateMismatch(
                $awayStats['offense_stuff_rate'] ?? null,
                $homeStats['defense_stuff_rate'] ?? null
            ),
            'Line Yards Differential' => $this->calculateMismatch(
                $homeStats['offense_line_yards'] ?? null,
                $awayStats['defense_line_yards'] ?? null
            ),
        ];
    }

    private function calculateMismatch($offenseStat, $defenseStat)
    {
        // Convert values to floats and check if they're numeric
        $offenseValue = is_numeric($offenseStat) ? (float)$offenseStat : null;
        $defenseValue = is_numeric($defenseStat) ? (float)$defenseStat : null;

        if ($offenseValue !== null && $defenseValue !== null) {
            return round($defenseValue - $offenseValue, 5);
        }

        return 'N/A';
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
