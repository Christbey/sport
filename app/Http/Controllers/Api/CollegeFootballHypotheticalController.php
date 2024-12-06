<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\{CollegeFootballHypotheticalCollection, CollegeFootballHypotheticalResource};
use App\Models\CollegeFootball\{AdvancedGameStat,
    CollegeFootballGame,
    CollegeFootballHypothetical,
    CollegeFootballNote,
    SpRating};
use App\Services\EnhancedFootballAnalytics;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CollegeFootballHypotheticalController extends Controller
{
    private const CACHE_TTL = 3600;
    protected EnhancedFootballAnalytics $analytics; // 1 hour

    public function __construct(EnhancedFootballAnalytics $analytics)
    {
        $this->analytics = $analytics;
    }

    public function index(Request $request)
    {
        $week = $request->input('week', $this->getCurrentWeek());
        $weeks = $this->getWeeks();
        $hypotheticals = $this->getHypotheticals($week);

        // Get prediction accuracy stats
        $weeklyStats = $this->getWeeklyPredictionStats($week);

        if ($request->wantsJson()) {
            return (new CollegeFootballHypotheticalCollection($hypotheticals))
                ->additional(['meta' => [
                    'weeks' => $weeks,
                    'current_week' => $week,
                    'prediction_stats' => $weeklyStats
                ]]);
        }

        return view('cfb.index', compact('hypotheticals', 'weeks', 'week', 'weeklyStats'));
    }

    private function getCurrentWeek(): int
    {
        $today = Carbon::today();

        foreach (config('college_football.weeks') as $weekNumber => $range) {
            if ($today->between(
                Carbon::parse($range['start']),
                Carbon::parse($range['end'])
            )) {
                return $weekNumber;
            }
        }

        return 1;
    }

    private function getWeeks()
    {
        return Cache::remember('cfb_weeks', self::CACHE_TTL, function () {
            return CollegeFootballHypothetical::select('week')
                ->distinct()
                ->orderBy('week', 'asc')
                ->get();
        });
    }

    private function getHypotheticals($week)
    {
        $hypotheticals = CollegeFootballHypothetical::where('college_football_hypotheticals.week', $week)
            ->join('college_football_games', 'college_football_hypotheticals.game_id', '=', 'college_football_games.id')
            ->select(
                'college_football_hypotheticals.*',
                'college_football_games.start_date',
                'college_football_games.completed',
                'college_football_games.formatted_spread',
                'college_football_games.home_points',
                'college_football_games.away_points'
            )
            ->with(['game', 'homeTeam', 'awayTeam'])
            ->orderBy('college_football_games.start_date', 'asc')
            ->get();

        return $hypotheticals->map(function ($hypothetical) {
            $winningPercentage = $this->calculateHomeWinningPercentage(
                $hypothetical->home_elo,
                $hypothetical->away_elo,
                $hypothetical->home_fpi,
                $hypothetical->away_fpi
            );

            $hypothetical->home_winning_percentage = $winningPercentage;
            $hypothetical->winner_color = $winningPercentage > 0.5
                ? $hypothetical->homeTeam->color ?? '#000000'
                : $hypothetical->awayTeam->color ?? '#000000';

            return $hypothetical;
        });
    }

    private function calculateHomeWinningPercentage($homeElo, $awayElo, $homeFpi, $awayFpi): float
    {
        $eloProbability = 1 / (1 + pow(10, ($awayElo - $homeElo) / 400));
        $fpiProbability = 1 / (1 + pow(10, ($awayFpi - $homeFpi) / 10));

        return round(($eloProbability + $fpiProbability) / 2, 4);
    }

    private function getWeeklyPredictionStats($week): array
    {
        $stats = CollegeFootballHypothetical::where('week', $week)
            ->selectRaw('
                COUNT(*) as total_predictions,
                SUM(CASE WHEN correct = 1 THEN 1 ELSE 0 END) as correct_predictions,
                SUM(CASE WHEN correct = 0 THEN 1 ELSE 0 END) as incorrect_predictions
            ')
            ->first();

        return [
            'total' => $stats->total_predictions ?? 0,
            'correct' => $stats->correct_predictions ?? 0,
            'incorrect' => $stats->incorrect_predictions ?? 0,
            'accuracy_rate' => $stats->total_predictions > 0
                ? round(($stats->correct_predictions / $stats->total_predictions) * 100, 1)
                : 0
        ];
    }

    public function show(Request $request, $gameId)
    {
        $data = $this->getGameData($gameId);

        if ($request->wantsJson()) {
            return new CollegeFootballHypotheticalResource($data);
        }

        // Return all data to the view
        return view('cfb.detail', $data);
    }

    private function getGameData($gameId)
    {
        $cacheKey = "game_data_{$gameId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($gameId) {
            $hypothetical = CollegeFootballHypothetical::with(['game', 'homeTeam', 'awayTeam'])
                ->where('game_id', $gameId)
                ->firstOrFail();

            $homeTeam = $hypothetical->homeTeam;
            $awayTeam = $hypothetical->awayTeam;

            // Fetch stats
            $homeStats = $this->fetchAdvancedStats($homeTeam->id);
            $awayStats = $this->fetchAdvancedStats($awayTeam->id);

            // Calculate efficiency metrics
            $efficiencyMetrics = $this->analytics->calculateEfficiencyMetrics($homeStats, $awayStats);

            // Calculate win probability
            $winningPercentage = $this->calculateHomeWinningPercentage(
                $hypothetical->home_elo,
                $hypothetical->away_elo,
                $hypothetical->home_fpi,
                $hypothetical->away_fpi
            );

            // Return complete data array
            return [
                'hypothetical' => $hypothetical,
                'game' => $hypothetical->game,
                'homeTeam' => $homeTeam,
                'awayTeam' => $awayTeam,
                'homeStats' => $homeStats,
                'awayStats' => $awayStats,
                'homeWinningPercentage' => $winningPercentage,
                'winnerTeam' => $winningPercentage > 0.5 ? $homeTeam : $awayTeam,
                'homeSpRating' => SpRating::firstWhere('team', $homeTeam->school),
                'awaySpRating' => SpRating::firstWhere('team', $awayTeam->school),
                'homeTeamNotes' => CollegeFootballNote::where('team_id', $homeTeam->id)->get(),
                'awayTeamNotes' => CollegeFootballNote::where('team_id', $awayTeam->id)->get(),
                'efficiencyMetrics' => $efficiencyMetrics,  // Added this line
                'matchupAdvantages' => $this->analytics->calculateMatchupAdvantages($homeStats, $awayStats),
                'scoringPrediction' => [
                    'home_predicted_range' => [
                        'low' => $this->calculateScoringRange($homeStats, $awayStats, 'home', 'low'),
                        'high' => $this->calculateScoringRange($homeStats, $awayStats, 'home', 'high')
                    ],
                    'away_predicted_range' => [
                        'low' => $this->calculateScoringRange($homeStats, $awayStats, 'away', 'low'),
                        'high' => $this->calculateScoringRange($homeStats, $awayStats, 'away', 'high')
                    ]
                ],
                'driveMetrics' => $this->analytics->calculateDriveMetrics($homeStats, $awayStats),
                'mismatches' => $this->calculateMismatches($homeStats, $awayStats),
                'trends' => $this->getTeamTrends($hypothetical),
                'gameHistory' => $this->getGameHistory($hypothetical),
                'homeTeamLast3Games' => $this->fetchLastThreeGames($homeTeam->id, $hypothetical->game->start_date),
                'awayTeamLast3Games' => $this->fetchLastThreeGames($awayTeam->id, $hypothetical->game->start_date),
                'previousResults' => $this->fetchRecentMatchups($homeTeam, $awayTeam)
            ];
        });
    }

    private function fetchAdvancedStats($teamId)
    {
        $metrics = $this->getStatMetrics();

        // Create the select statement for averages
        $selectStatements = array_map(function ($metric) {
            return "AVG($metric) as $metric";
        }, $metrics);

        $averages = AdvancedGameStat::where('team_id', $teamId)
            ->selectRaw(implode(', ', $selectStatements))
            ->first();

        // Convert to array and handle nulls
        $stats = array_map(function ($value) {
            return $value ?? 0;
        }, $averages ? $averages->toArray() : array_fill_keys($metrics, 0));

        return $stats;
    }

    private function getStatMetrics(): array
    {
        return [
            'offense_plays', 'offense_drives', 'offense_ppa', 'offense_total_ppa',
            'offense_success_rate', 'offense_explosiveness', 'offense_power_success',
            'offense_stuff_rate', 'offense_line_yards', 'offense_line_yards_total',
            'offense_second_level_yards', 'offense_second_level_yards_total',
            'offense_open_field_yards', 'offense_open_field_yards_total',
            'offense_standard_downs_ppa', 'offense_standard_downs_success_rate',
            'offense_standard_downs_explosiveness', 'offense_passing_downs_ppa',
            'offense_passing_downs_success_rate', 'offense_passing_downs_explosiveness',
            'offense_rushing_ppa', 'offense_rushing_total_ppa', 'offense_rushing_success_rate',
            'offense_rushing_explosiveness', 'offense_passing_ppa', 'offense_passing_total_ppa',
            'offense_passing_success_rate', 'offense_passing_explosiveness',
            'defense_plays', 'defense_drives', 'defense_ppa', 'defense_total_ppa',
            'defense_success_rate', 'defense_explosiveness', 'defense_power_success',
            'defense_stuff_rate', 'defense_line_yards', 'defense_line_yards_total',
            'defense_second_level_yards', 'defense_second_level_yards_total',
            'defense_open_field_yards', 'defense_open_field_yards_total',
            'defense_standard_downs_ppa', 'defense_standard_downs_success_rate',
            'defense_standard_downs_explosiveness', 'defense_passing_downs_ppa',
            'defense_passing_downs_success_rate', 'defense_passing_downs_explosiveness',
            'defense_rushing_ppa', 'defense_rushing_total_ppa', 'defense_rushing_success_rate',
            'defense_rushing_explosiveness', 'defense_passing_ppa', 'defense_passing_total_ppa',
            'defense_passing_success_rate', 'defense_passing_explosiveness'
        ];
    }

    private function calculateScoringRange(array $homeStats, array $awayStats, string $team, string $range): float
    {
        $offensePPA = $team === 'home' ?
            ($homeStats['offense_ppa'] ?? 0) :
            ($awayStats['offense_ppa'] ?? 0);

        $defensePPA = $team === 'home' ?
            ($awayStats['defense_ppa'] ?? 0) :
            ($homeStats['defense_ppa'] ?? 0);

        $successRate = $team === 'home' ?
            ($homeStats['offense_success_rate'] ?? 0) :
            ($awayStats['offense_success_rate'] ?? 0);

        $baseScore = ($offensePPA - $defensePPA) * 100;
        $adjustment = $successRate * 10;

        if ($range === 'low') {
            return max(round($baseScore + $adjustment + 7, 1), 7);
        } else {
            return max(round($baseScore + $adjustment + 17, 1), 21);
        }
    }

    private function calculateMismatches($homeStats, $awayStats): array
    {
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
            )
        ];
    }

    private function calculateMismatch($offenseStat, $defenseStat)
    {
        $offenseValue = is_numeric($offenseStat) ? (float)$offenseStat : null;
        $defenseValue = is_numeric($defenseStat) ? (float)$defenseStat : null;

        if ($offenseValue !== null && $defenseValue !== null) {
            return round($defenseValue - $offenseValue, 5);
        }

        return 'N/A';
    }

    private function getTeamTrends($hypothetical): array
    {
        return [
            'home' => [
                'offense' => $this->calculateTrend($hypothetical->home_team_id, 'offense_ppa'),
                'defense' => $this->calculateTrend($hypothetical->home_team_id, 'defense_ppa')
            ],
            'away' => [
                'offense' => $this->calculateTrend($hypothetical->away_team_id, 'offense_ppa'),
                'defense' => $this->calculateTrend($hypothetical->away_team_id, 'defense_ppa')
            ]
        ];
    }

    private function calculateTrend($teamId, $statKey)
    {
        return Cache::remember("team_trend_{$teamId}_{$statKey}", self::CACHE_TTL, function () use ($teamId, $statKey) {
            return AdvancedGameStat::where('team_id', $teamId)
                ->orderBy('game_id', 'desc')
                ->limit(3)
                ->avg($statKey) ?? 'N/A';
        });
    }

    private function getGameHistory($hypothetical): array
    {
        return [
            'home_last_three' => $this->fetchLastThreeGames($hypothetical->home_team_id, $hypothetical->game->start_date),
            'away_last_three' => $this->fetchLastThreeGames($hypothetical->away_team_id, $hypothetical->game->start_date),
            'head_to_head' => $this->fetchRecentMatchups($hypothetical->homeTeam, $hypothetical->awayTeam)
        ];
    }

    private function fetchLastThreeGames($teamId, $beforeDate)
    {
        return CollegeFootballGame::with(['homeTeam', 'awayTeam'])
            ->where(function ($query) use ($teamId) {
                $query->where('home_id', $teamId)
                    ->orWhere('away_id', $teamId);
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
                $query->where(function ($q) use ($homeTeam, $awayTeam) {
                    $q->where('home_id', $homeTeam->id)
                        ->where('away_id', $awayTeam->id);
                })->orWhere(function ($q) use ($homeTeam, $awayTeam) {
                    $q->where('home_id', $awayTeam->id)
                        ->where('away_id', $homeTeam->id);
                });
            })
            ->orderBy('start_date', 'desc')
            ->get();
    }

    private function getAnalyticsData($homeStats, $awayStats): array
    {
        return [
            'efficiencyMetrics' => $this->analytics->calculateEfficiencyMetrics($homeStats, $awayStats),
            'matchupAdvantages' => $this->analytics->calculateMatchupAdvantages($homeStats, $awayStats),
            'scoringPrediction' => $this->analytics->calculateScoringPrediction($homeStats, $awayStats),
            'driveMetrics' => $this->analytics->calculateDriveMetrics($homeStats, $awayStats)
        ];
    }
}