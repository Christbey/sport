<?php

namespace App\Services\Nfl;

use App\Repositories\Nfl\TeamStatsRepository;
use App\Services\Nfl\Stats\{ConsistencyCalculator, DefenseStatsCalculator, OffenseStatsCalculator};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TeamStatsService
{
    const CACHE_DURATION = 60;
    protected TeamStatsRepository $repository;
    protected array $calculators;

    public function __construct(
        TeamStatsRepository    $repository,
        OffenseStatsCalculator $offenseCalc,
        DefenseStatsCalculator $defenseCalc,
        ConsistencyCalculator  $consistencyCalc
    )
    {
        $this->repository = $repository;
        $this->calculators = [
            'offense' => $offenseCalc,
            'defense' => $defenseCalc,
            'consistency' => $consistencyCalc
        ];
    }

    public function getStatsByType(string $type, ?string $teamFilter): array
    {
        return match ($type) {
            'average_points' => $this->repository->getAveragePoints($teamFilter),
            'quarter_scoring' => $this->repository->getQuarterScoring($teamFilter),
            'half_scoring' => $this->repository->getHalfScoring($teamFilter),
            'score_margins' => $this->repository->getScoreMargins($teamFilter),
            'quarter_comebacks' => $this->repository->getQuarterComebacks($teamFilter),
            'scoring_streaks' => $this->repository->getScoringStreaks($teamFilter),
            'bestReceivers' => $this->repository->getBestReceivers($teamFilter),
            'bestRushers' => $this->repository->getBestRushers($teamFilter),
            'bestTacklers' => $this->repository->getBestTacklers($teamFilter),
            'big_playmakers' => $this->repository->getBigPlaymakers($teamFilter),
            'defensive_playmakers' => $this->repository->getDefensivePlaymakers($teamFilter),
            'dual_threat' => $this->repository->getDualThreatPlayers($teamFilter),
            'offensive_consistency' => $this->repository->getOffensiveConsistency($teamFilter),
            'nfl_team_stats' => $this->repository->getNflTeamStats($teamFilter),
            'team_analytics' => $this->getTeamAnalytics($teamFilter),
            'over_under_analysis' => $this->repository->getOverUnderAnalysis($teamFilter),
            'team_matchup_edge' => $this->repository->getTeamMatchupEdge($teamFilter),
            'first_half_trends' => $this->repository->getFirstHalfTendencies($teamFilter),
            'team_vs_conference' => $this->repository->getTeamVsConference($teamFilter),
            'team_vs_division' => $this->repository->getTeamVsDivision($teamFilter),
            'player_vs_conference' => $this->repository->getPlayerVsConference($teamFilter),
            'player_vs_division' => $this->repository->getPlayerVsDivision($teamFilter),
            'conference_stats' => $this->repository->getConferenceStats($teamFilter),
            'division_stats' => $this->repository->getDivisionStats($teamFilter),
            default => ['data' => [], 'headings' => []]
        };
    }

    public function getTeamAnalytics(?string $teamFilter = null): array
    {
        $cacheKey = 'team_analytics_' . ($teamFilter ?? 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($teamFilter) {
            if ($teamFilter) {
                $teamId = DB::table('nfl_teams')
                    ->where('team_abv', $teamFilter)
                    ->value('id');

                $teamStats = $this->calculateTeamAnalytics($teamId);

                return [[
                    'Team' => $teamStats['team_abv'] ?? 'N/A',
                    'Games' => $teamStats['sample_size'],
                    'Pass YPG' => number_format($teamStats['offensive_stats']['yards_per_game']['passing'], 1),
                    'Yards/Play' => number_format($teamStats['offensive_stats']['efficiency']['yards_per_play'], 2),
                    'Rush Y/A' => number_format($teamStats['offensive_stats']['efficiency']['rushing_yards_per_attempt'], 2),
                    'Pass Y/A' => number_format($teamStats['offensive_stats']['efficiency']['passing_yards_per_attempt'], 2),
                    'Rush %' => number_format($teamStats['offensive_stats']['play_distribution']['rushing_percentage'], 1) . '%',
                    'Pass %' => number_format($teamStats['offensive_stats']['play_distribution']['passing_percentage'], 1) . '%',
                    'Total CV' => number_format($teamStats['consistency_metrics']['total_yards']['coefficient_of_variation'], 1) . '%',
                    'Rush CV' => number_format($teamStats['consistency_metrics']['rushing_yards']['coefficient_of_variation'], 1) . '%',
                    'Pass CV' => number_format($teamStats['consistency_metrics']['passing_yards']['coefficient_of_variation'], 1) . '%',
                    'Total Trend' => $teamStats['performance_trends']['total_yards']['regression']['trend_direction'] ?? 'N/A',
                    'Rush Trend' => $teamStats['performance_trends']['rushing_yards']['regression']['trend_direction'] ?? 'N/A',
                    'Pass Trend' => $teamStats['performance_trends']['passing_yards']['regression']['trend_direction'] ?? 'N/A',
                    'Rating' => $teamStats['situation_analysis']['home_performance']['performance_rating'] ?? 'Developing',
                    'SOS' => $teamStats['opponent_adjusted']['strength_of_schedule']['difficulty'] ?? 'N/A'
                ]];
            } else {
                // Get all teams' analytics
                $teams = DB::table('nfl_team_stats')
                    ->select('team_id', 'team_abv')
                    ->distinct('team_id')
                    ->whereNotNull('team_id')
                    ->get();

                return $teams->map(function ($team) {
                    $teamStats = $this->calculateTeamAnalytics($team->team_id);

                    if ($teamStats['sample_size'] > 0) {
                        return [
                            'Team' => $team->team_abv,
                            'Games' => $teamStats['sample_size'],
                            'Pass YPG' => number_format($teamStats['offensive_stats']['yards_per_game']['passing'], 1),
                            'Yards/Play' => number_format($teamStats['offensive_stats']['efficiency']['yards_per_play'], 2),
                            'Rush Y/A' => number_format($teamStats['offensive_stats']['efficiency']['rushing_yards_per_attempt'], 2),
                            'Pass Y/A' => number_format($teamStats['offensive_stats']['efficiency']['passing_yards_per_attempt'], 2),
                            'Rush %' => number_format($teamStats['offensive_stats']['play_distribution']['rushing_percentage'], 1) . '%',
                            'Pass %' => number_format($teamStats['offensive_stats']['play_distribution']['passing_percentage'], 1) . '%',
                            'Total CV' => number_format($teamStats['consistency_metrics']['total_yards']['coefficient_of_variation'], 1) . '%',
                            'Rush CV' => number_format($teamStats['consistency_metrics']['rushing_yards']['coefficient_of_variation'], 1) . '%',
                            'Pass CV' => number_format($teamStats['consistency_metrics']['passing_yards']['coefficient_of_variation'], 1) . '%',
                            'Total Trend' => $teamStats['performance_trends']['total_yards']['regression']['trend_direction'] ?? 'N/A',
                            'Rush Trend' => $teamStats['performance_trends']['rushing_yards']['regression']['trend_direction'] ?? 'N/A',
                            'Pass Trend' => $teamStats['performance_trends']['passing_yards']['regression']['trend_direction'] ?? 'N/A',
                            'Rating' => $teamStats['situation_analysis']['home_performance']['performance_rating'] ?? 'Developing',
                            'SOS' => $teamStats['opponent_adjusted']['strength_of_schedule']['difficulty'] ?? 'N/A'
                        ];
                    }
                    return 0;
                })->filter()->values();
            }
        });

        return [
            'data' => $data,
            'headings' => [
                'Team',
                'Games',
                'Pass YPG',
                'Yards/Play',
                'Rush Y/A',
                'Pass Y/A',
                'Rush %',
                'Pass %',
                'Total CV',
                'Rush CV',
                'Pass CV',
                'Total Trend',
                'Rush Trend',
                'Pass Trend',
                'Rating',
                'SOS'
            ]
        ];
    }


    public function calculateTeamAnalytics(int $teamId, int $gamesBack = 5): array
    {
        $games = $this->repository->getRecentGames($teamId, $gamesBack);

        if ($games->isEmpty()) {
            return $this->getEmptyAnalytics();
        }

        return [
            'team_abv' => $games->first()->team_abv,
            'sample_size' => $games->count(),
            'offensive_stats' => $this->calculators['offense']->calculate($games),
            'defensive_stats' => $this->calculators['defense']->calculate($games),
            'consistency_metrics' => $this->calculators['consistency']->calculate($games),
            'performance_trends' => $this->calculateTrends($games),
            'situation_analysis' => $this->analyzeSituationalPerformance($teamId, $gamesBack),
            'opponent_adjusted' => $this->calculateOpponentAdjustedStats($teamId, $gamesBack)
        ];
    }

    protected function getEmptyAnalytics(): array
    {
        return [
            'sample_size' => 0,
            'offensive_stats' => [
                'yards_per_game' => ['total' => 0, 'rushing' => 0, 'passing' => 0],
                'efficiency' => ['yards_per_play' => 0, 'rushing_yards_per_attempt' => 0, 'passing_yards_per_attempt' => 0],
                'play_distribution' => ['rushing_percentage' => 0, 'passing_percentage' => 0],
            ],
            'consistency_metrics' => [
                'total_yards' => ['mean' => 0, 'median' => 0, 'std_dev' => 0, 'coefficient_of_variation' => 0],
                'rushing_yards' => ['mean' => 0, 'median' => 0, 'std_dev' => 0, 'coefficient_of_variation' => 0],
                'passing_yards' => ['mean' => 0, 'median' => 0, 'std_dev' => 0, 'coefficient_of_variation' => 0],
            ],
            'performance_trends' => [
                'total_yards' => ['trend' => 'insufficient_data'],
                'rushing_yards' => ['trend' => 'insufficient_data'],
                'passing_yards' => ['trend' => 'insufficient_data']
            ],
            'situation_analysis' => [
                'home_performance' => ['rating' => 'insufficient_data'],
                'away_performance' => ['rating' => 'insufficient_data']
            ],
            'opponent_adjusted' => [
                'strength_of_schedule' => ['difficulty' => 'insufficient_data']
            ]
        ];
    }

    protected function calculateTrends(Collection $games): array
    {
        if ($games->count() < 2) {
            return ['trend' => 'insufficient_data'];
        }

        $metrics = ['total_yards', 'rushing_yards', 'passing_yards'];
        $trends = [];

        foreach ($metrics as $metric) {
            $values = $games->pluck($metric)->toArray();
            $trends[$metric] = [
                'moving_averages' => $this->calculateMovingAverages($values),
                'regression' => $this->calculateLinearRegression($values),
                'momentum' => $this->calculateMomentum($values)
            ];
        }

        return $trends;
    }

    protected function calculateMovingAverages(array $values): array
    {
        $count = count($values);
        return [
            'three_game' => $count >= 3 ? round(array_sum(array_slice($values, 0, 3)) / 3, 1) : null,
            'five_game' => $count >= 5 ? round(array_sum($values) / 5, 1) : null
        ];
    }

    protected function calculateLinearRegression(array $values): array
    {
        $n = count($values);
        $x = range(1, $n);
        $sumX = array_sum($x);
        $sumY = array_sum($values);
        $sumXY = array_sum(array_map(function ($xi, $yi) {
            return $xi * $yi;
        }, $x, $values));
        $sumX2 = array_sum(array_map(fn($xi) => $xi * $xi, $x));

        $slope = (($n * $sumXY) - ($sumX * $sumY)) / (($n * $sumX2) - ($sumX * $sumX));

        return [
            'slope' => round($slope, 3),
            'trend_direction' => $this->getTrendDirection($slope)
        ];
    }

    protected function getTrendDirection(float $slope): string
    {
        return match (true) {
            $slope > 5 => 'strong_upward',
            $slope > 2 => 'moderate_upward',
            $slope > 0 => 'slight_upward',
            $slope < -5 => 'strong_downward',
            $slope < -2 => 'moderate_downward',
            $slope < 0 => 'slight_downward',
            default => 'stable'
        };
    }

    protected function calculateMomentum(array $values): string
    {
        if (count($values) < 2) {
            return 'insufficient_data';
        }

        $recentAvg = array_sum(array_slice($values, 0, 2)) / 2;
        $previousAvg = array_sum(array_slice($values, -2)) / 2;
        $percentChange = (($recentAvg - $previousAvg) / $previousAvg) * 100;

        return match (true) {
            $percentChange > 15 => 'strong_positive',
            $percentChange > 5 => 'positive',
            $percentChange < -15 => 'strong_negative',
            $percentChange < -5 => 'negative',
            default => 'neutral'
        };
    }

    protected function analyzeSituationalPerformance(int $teamId, int $gamesBack): array
    {
        return $this->repository->getSituationalPerformance($teamId, $gamesBack);
    }

    protected function calculateOpponentAdjustedStats(int $teamId, int $gamesBack): array
    {
        return $this->repository->getOpponentAdjustedStats($teamId, $gamesBack);
    }


}