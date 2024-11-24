<?php

namespace App\Repositories\Nfl;

use App\Models\Nfl\{NflTeamSchedule, NflTeamStat};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TeamStatsRepository
{
    /**
     * Cache duration in minutes
     */
    private const CACHE_DURATION = 60;

    /**
     * Get recent games for a team
     *
     * @param int $teamId
     * @param int $gamesBack
     * @return Collection
     */
    public function getRecentGames(int $teamId, int $gamesBack = 10): Collection
    {
        $cacheKey = "team_recent_games_{$teamId}_{$gamesBack}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($teamId, $gamesBack) {
            return NflTeamStat::where('team_id', $teamId)
                ->orderBy('created_at', 'desc')
                ->take($gamesBack)
                ->get();
        });
    }

    /**
     * Get average points statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public function getAveragePoints(?string $teamFilter = null): array
    {
        $cacheKey = 'avg_points_' . ($teamFilter ?? 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($teamFilter) {
            $baseQuery = "
                SELECT
                    b.game_id,
                    :%team_column AS team_abv,
                    :%location AS location_type,
                    JSON_UNQUOTE(JSON_EXTRACT(:%score_column, '$.Q1')) AS Q1,
                    JSON_UNQUOTE(JSON_EXTRACT(:%score_column, '$.Q2')) AS Q2,
                    JSON_UNQUOTE(JSON_EXTRACT(:%score_column, '$.Q3')) AS Q3,
                    JSON_UNQUOTE(JSON_EXTRACT(:%score_column, '$.Q4')) AS Q4,
                    :%points_column AS totalPts
                FROM nfl_box_scores b
                INNER JOIN nfl_team_schedules s ON b.game_id = s.game_id
                WHERE s.season_type = 'Regular Season'
                AND (:%team_column = ? OR ? IS NULL)
            ";

            $homeQuery = strtr($baseQuery, [
                ':%team_column' => 'b.home_team',
                ':%location' => "'home'",
                ':%score_column' => 'b.home_line_score',
                ':%points_column' => 'b.home_points'
            ]);

            $awayQuery = strtr($baseQuery, [
                ':%team_column' => 'b.away_team',
                ':%location' => "'away'",
                ':%score_column' => 'b.away_line_score',
                ':%points_column' => 'b.away_points'
            ]);

            $sql = "
                WITH team_scores AS (
                    SELECT * FROM (
                        {$homeQuery}
                        UNION ALL
                        {$awayQuery}
                    ) scores
                )
                SELECT
                    team_abv,
                    location_type,
                    AVG(CAST(Q1 AS UNSIGNED)) as avg_q1_points,
                    AVG(CAST(Q2 AS UNSIGNED)) as avg_q2_points,
                    AVG(CAST(Q3 AS UNSIGNED)) as avg_q3_points,
                    AVG(CAST(Q4 AS UNSIGNED)) as avg_q4_points,
                    AVG(CAST(Q1 AS UNSIGNED) + CAST(Q2 AS UNSIGNED)) as avg_first_half_points,
                    AVG(CAST(Q3 AS UNSIGNED) + CAST(Q4 AS UNSIGNED)) as avg_second_half_points,
                    AVG(CAST(totalPts AS UNSIGNED)) as avg_total_points
                FROM team_scores
                GROUP BY team_abv, location_type
            ";

            return DB::select($sql, array_fill(0, 4, $teamFilter));
        });

        return [
            'data' => $data,
            'headings' => [
                'Team',
                'Location Type',
                'Q1',
                'Q2',
                'Q3',
                'Q4',
                'First Half',
                'Second Half',
                'Total Points'
            ]
        ];
    }

    /**
     * Get quarter scoring statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public function getQuarterScoring(?string $teamFilter = null): array
    {
        $cacheKey = 'quarter_scoring_' . ($teamFilter ?? 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($teamFilter) {
            return DB::table('nfl_box_scores as b')
                ->join('nfl_team_schedules as s', 'b.game_id', '=', 's.game_id')
                ->where('s.season_type', 'Regular Season')
                ->when($teamFilter, function ($query) use ($teamFilter) {
                    return $query->where(function ($q) use ($teamFilter) {
                        $q->where('b.home_team', $teamFilter)
                            ->orWhere('b.away_team', $teamFilter);
                    });
                })
                ->select(
                    'b.home_team',
                    'b.away_team',
                    DB::raw('JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, "$.Q1")) as home_q1'),
                    DB::raw('JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, "$.Q2")) as home_q2'),
                    DB::raw('JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, "$.Q3")) as home_q3'),
                    DB::raw('JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, "$.Q4")) as home_q4'),
                    DB::raw('JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, "$.Q1")) as away_q1'),
                    DB::raw('JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, "$.Q2")) as away_q2'),
                    DB::raw('JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, "$.Q3")) as away_q3'),
                    DB::raw('JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, "$.Q4")) as away_q4')
                )
                ->get()
                ->map(function ($game) {
                    // Process data for both home and away teams
                    return [
                        // Home team stats
                        [
                            'team' => $game->home_team,
                            'location' => 'home',
                            'q1' => (int)$game->home_q1,
                            'q2' => (int)$game->home_q2,
                            'q3' => (int)$game->home_q3,
                            'q4' => (int)$game->home_q4
                        ],
                        // Away team stats
                        [
                            'team' => $game->away_team,
                            'location' => 'away',
                            'q1' => (int)$game->away_q1,
                            'q2' => (int)$game->away_q2,
                            'q3' => (int)$game->away_q3,
                            'q4' => (int)$game->away_q4
                        ]
                    ];
                })
                ->flatten(1)
                ->groupBy('team')
                ->map(function ($teamGames) {
                    return $this->calculateQuarterStats($teamGames);
                })
                ->values();
        });

        return [
            'data' => $data,
            'headings' => [
                'Team',
                'Q1 Avg',
                'Q2 Avg',
                'Q3 Avg',
                'Q4 Avg',
                'Games',
                'Q1 Scoring %',
                'Q2 Scoring %',
                'Q3 Scoring %',
                'Q4 Scoring %',
                'Best Qtr',
                'Worst Qtr'
            ]
        ];
    }

    /**
     * Calculate quarter statistics
     *
     * @param Collection $teamGames
     * @return array
     */
    protected function calculateQuarterStats(Collection $teamGames): array
    {
        $gamesCount = $teamGames->count();
        $quarterAverages = [];
        $quarterScoring = [];

        for ($i = 1; $i <= 4; $i++) {
            $quarter = "q$i";
            $quarterPoints = $teamGames->pluck($quarter);
            $quarterAverages[$quarter] = round($quarterPoints->avg(), 1);
            $quarterScoring[$quarter] = round(
                ($quarterPoints->filter()->count() / $gamesCount) * 100,
                1
            );
        }

        $bestQuarter = array_search(max($quarterAverages), $quarterAverages);
        $worstQuarter = array_search(min($quarterAverages), $quarterAverages);

        return [
            'team' => $teamGames->first()['team'],
            'q1_avg' => $quarterAverages['q1'],
            'q2_avg' => $quarterAverages['q2'],
            'q3_avg' => $quarterAverages['q3'],
            'q4_avg' => $quarterAverages['q4'],
            'games_played' => $gamesCount,
            'q1_scoring_pct' => $quarterScoring['q1'],
            'q2_scoring_pct' => $quarterScoring['q2'],
            'q3_scoring_pct' => $quarterScoring['q3'],
            'q4_scoring_pct' => $quarterScoring['q4'],
            'best_quarter' => strtoupper($bestQuarter),
            'worst_quarter' => strtoupper($worstQuarter)
        ];
    }

    /**
     * Get situational performance
     *
     * @param int $teamId
     * @param int $gamesBack
     * @return array
     */
    public function getSituationalPerformance(int $teamId, int $gamesBack): array
    {
        $cacheKey = "situational_performance_{$teamId}_{$gamesBack}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($teamId, $gamesBack) {
            $schedules = NflTeamSchedule::where(function ($query) use ($teamId) {
                $query->where('home_team_id', $teamId)
                    ->orWhere('away_team_id', $teamId);
            })
                ->orderBy('game_date', 'desc')
                ->take($gamesBack)
                ->get();

            $homeStats = [];
            $awayStats = [];

            foreach ($schedules as $schedule) {
                $gameStats = NflTeamStat::where('game_id', $schedule->game_id)
                    ->where('team_id', $teamId)
                    ->first();

                if ($gameStats) {
                    if ($schedule->home_team_id === $teamId) {
                        $homeStats[] = $gameStats;
                    } else {
                        $awayStats[] = $gameStats;
                    }
                }
            }

            return [
                'home_performance' => $this->calculateSituationalMetrics($homeStats),
                'away_performance' => $this->calculateSituationalMetrics($awayStats)
            ];
        });
    }

    /**
     * Calculate situational metrics
     *
     * @param array $stats
     * @return array
     */
    protected function calculateSituationalMetrics(array $stats): array
    {
        if (empty($stats)) {
            return [
                'average_yards' => 0,
                'yards_consistency' => 0,
                'performance_rating' => 'insufficient_data'
            ];
        }

        $yardValues = array_column($stats, 'total_yards');
        $average = array_sum($yardValues) / count($yardValues);
        $consistency = $this->calculateConsistencyScore($yardValues);

        return [
            'average_yards' => round($average, 1),
            'yards_consistency' => round($consistency, 2),
            'performance_rating' => $this->getRatingFromStats($average, $consistency)
        ];
    }

    /**
     * Calculate consistency score
     *
     * @param array $values
     * @return float
     */
    protected function calculateConsistencyScore(array $values): float
    {
        $mean = array_sum($values) / count($values);
        $variance = array_reduce($values, function ($carry, $item) use ($mean) {
                return $carry + pow($item - $mean, 2);
            }, 0) / count($values);

        return sqrt($variance) / $mean * 100;
    }

    /**
     * Get rating from stats
     *
     * @param float $average
     * @param float $consistency
     * @return string
     */
    protected function getRatingFromStats(float $average, float $consistency): string
    {
        if ($average >= 350 && $consistency <= 15) return 'Elite Consistent';
        if ($average >= 350) return 'Elite Variable';
        if ($average >= 300 && $consistency <= 20) return 'Strong Consistent';
        if ($average >= 300) return 'Strong Variable';
        if ($average >= 250) return 'Above Average';
        return 'Average';
    }

    /**
     * Get half scoring statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public function getHalfScoring(?string $teamFilter = null): array
    {
        $cacheKey = 'half_scoring_' . ($teamFilter ?? 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($teamFilter) {
            $baseQuery = "
                SELECT
                    b.game_id,
                    :%team_column AS team_abv,
                    :%location AS location_type,
                    JSON_UNQUOTE(JSON_EXTRACT(:%score_column, '$.Q1')) AS Q1,
                    JSON_UNQUOTE(JSON_EXTRACT(:%score_column, '$.Q2')) AS Q2,
                    JSON_UNQUOTE(JSON_EXTRACT(:%score_column, '$.Q3')) AS Q3,
                    JSON_UNQUOTE(JSON_EXTRACT(:%score_column, '$.Q4')) AS Q4
                FROM nfl_box_scores b
                INNER JOIN nfl_team_schedules s ON b.game_id = s.game_id
                WHERE s.season_type = 'Regular Season'
                AND (:%team_column = ? OR ? IS NULL)
            ";

            $homeQuery = strtr($baseQuery, [
                ':%team_column' => 'b.home_team',
                ':%location' => "'home'",
                ':%score_column' => 'b.home_line_score'
            ]);

            $awayQuery = strtr($baseQuery, [
                ':%team_column' => 'b.away_team',
                ':%location' => "'away'",
                ':%score_column' => 'b.away_line_score'
            ]);

            $sql = "
                WITH team_scores AS (
                    SELECT
                        scores.team_abv,
                        scores.location_type,
                        scores.Q1,
                        scores.Q2,
                        scores.Q3,
                        scores.Q4
                    FROM (
                        {$homeQuery}
                        UNION ALL
                        {$awayQuery}
                    ) scores
                )
                SELECT
                    team_abv,
                    location_type,
                    ROUND(AVG(CAST(Q1 AS UNSIGNED) + CAST(Q2 AS UNSIGNED)), 1) AS avg_first_half_points,
                    ROUND(AVG(CAST(Q3 AS UNSIGNED) + CAST(Q4 AS UNSIGNED)), 1) AS avg_second_half_points,
                    ROUND(AVG(
                        CASE 
                            WHEN CAST(Q1 AS UNSIGNED) + CAST(Q2 AS UNSIGNED) > CAST(Q3 AS UNSIGNED) + CAST(Q4 AS UNSIGNED) THEN 1
                            ELSE 0
                        END
                    ) * 100, 1) AS first_half_stronger_percentage,
                    COUNT(*) as games_played
                FROM team_scores
                GROUP BY team_abv, location_type
                ORDER BY avg_first_half_points DESC
            ";

            return DB::select($sql, array_fill(0, 4, $teamFilter));
        });

        return [
            'data' => $data,
            'headings' => [
                'Team',
                'Location Type',
                'First Half Avg',
                'Second Half Avg',
                'First Half Stronger %',
                'Games Played'
            ]
        ];
    }

    /**
     * Get score margins statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public function getScoreMargins(?string $teamFilter = null): array
    {
        $cacheKey = 'score_margins_' . ($teamFilter ?? 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($teamFilter) {
            $sql = "
                WITH game_margins AS (
                    SELECT 
                        CASE 
                            WHEN b.home_team = ? OR ? IS NULL THEN b.home_team
                            ELSE b.away_team 
                        END as team_abv,
                        CASE 
                            WHEN b.home_team = ? OR ? IS NULL THEN 'home'
                            ELSE 'away' 
                        END as location_type,
                        CASE 
                            WHEN b.home_team = ? OR ? IS NULL THEN b.home_points - b.away_points
                            ELSE b.away_points - b.home_points 
                        END as point_margin,
                        CASE 
                            WHEN b.home_team = ? OR ? IS NULL THEN 
                                CASE WHEN b.home_points > b.away_points THEN 'W' ELSE 'L' END
                            ELSE 
                                CASE WHEN b.away_points > b.home_points THEN 'W' ELSE 'L' END
                        END as result
                    FROM nfl_box_scores b
                    JOIN nfl_team_schedules s ON b.game_id = s.game_id
                    WHERE s.season_type = 'Regular Season'
                    AND (b.home_team = ? OR b.away_team = ? OR ? IS NULL)
                )
                SELECT 
                    team_abv,
                    location_type,
                    COUNT(*) as games_played,
                    ROUND(AVG(point_margin), 1) as avg_margin,
                    MAX(point_margin) as largest_win_margin,
                    MIN(point_margin) as largest_loss_margin,
                    SUM(CASE WHEN point_margin > 0 THEN 1 ELSE 0 END) as wins,
                    SUM(CASE WHEN point_margin < 0 THEN 1 ELSE 0 END) as losses,
                    ROUND(AVG(CASE WHEN result = 'W' THEN point_margin END), 1) as avg_margin_in_wins,
                    ROUND(AVG(CASE WHEN result = 'L' THEN point_margin END), 1) as avg_margin_in_losses,
                    SUM(CASE WHEN ABS(point_margin) <= 7 THEN 1 ELSE 0 END) as one_score_games,
                    ROUND(SUM(CASE WHEN point_margin > 0 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as win_percentage
                FROM game_margins
                GROUP BY team_abv, location_type
                ORDER BY avg_margin DESC
            ";

            return DB::select($sql, array_fill(0, 11, $teamFilter));
        });

        return [
            'data' => $data,
            'headings' => [
                'Team',
                'Location',
                'Games',
                'Avg Margin',
                'Largest Win',
                'Largest Loss',
                'Wins',
                'Losses',
                'Avg Win Margin',
                'Avg Loss Margin',
                'One Score Games',
                'Win %'
            ]
        ];
    }

    /**
     * Get quarter comebacks statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public function getQuarterComebacks(?string $teamFilter = null): array
    {
        $cacheKey = 'quarter_comebacks_' . ($teamFilter ?? 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($teamFilter) {
            $sql = "
                WITH quarter_scores AS (
                    SELECT 
                        b.game_id,
                        b.home_team,
                        b.away_team,
                        CAST(JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, '$.Q1')) AS UNSIGNED) +
                        CAST(JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, '$.Q2')) AS UNSIGNED) +
                        CAST(JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, '$.Q3')) AS UNSIGNED) as home_thru_3,
                        CAST(JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, '$.Q1')) AS UNSIGNED) +
                        CAST(JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, '$.Q2')) AS UNSIGNED) +
                        CAST(JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, '$.Q3')) AS UNSIGNED) as away_thru_3,
                        b.home_points as home_final,
                        b.away_points as away_final,
                        CAST(JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, '$.Q4')) AS UNSIGNED) as home_q4,
                        CAST(JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, '$.Q4')) AS UNSIGNED) as away_q4
                    FROM nfl_box_scores b
                    JOIN nfl_team_schedules s ON b.game_id = s.game_id
                    WHERE s.season_type = 'Regular Season'
                    AND (b.home_team = ? OR b.away_team = ? OR ? IS NULL)
                ),
                team_stats AS (
                    SELECT 
                        home_team as team,
                        'home' as location,
                        home_thru_3 as team_thru_3,
                        away_thru_3 as opp_thru_3,
                        home_final as team_final,
                        away_final as opp_final,
                        home_q4 as team_q4
                    FROM quarter_scores
                    WHERE home_team = ? OR ? IS NULL
                    
                    UNION ALL
                    
                    SELECT 
                        away_team as team,
                        'away' as location,
                        away_thru_3 as team_thru_3,
                        home_thru_3 as opp_thru_3,
                        away_final as team_final,
                        home_final as opp_final,
                        away_q4 as team_q4
                    FROM quarter_scores
                    WHERE away_team = ? OR ? IS NULL
                )
                SELECT 
                    team as team_abv,
                    COUNT(*) as total_games,
                    SUM(CASE 
                        WHEN team_thru_3 < opp_thru_3 AND team_final > opp_final THEN 1
                        ELSE 0 
                    END) as comeback_wins,
                    SUM(CASE 
                        WHEN team_thru_3 > opp_thru_3 AND team_final < opp_final THEN 1
                        ELSE 0 
                    END) as blown_leads,
                    ROUND(AVG(team_q4), 1) as avg_fourth_quarter_points,
                    ROUND(AVG(CASE 
                        WHEN team_thru_3 < opp_thru_3 AND team_final > opp_final THEN team_q4
                        ELSE NULL 
                    END), 1) as avg_comeback_q4_points
                FROM team_stats
                GROUP BY team
                ORDER BY comeback_wins DESC
            ";

            return DB::select($sql, array_fill(0, 7, $teamFilter));
        });

        return [
            'data' => $data,
            'headings' => [
                'Team',
                'Total Games',
                'Comeback Wins',
                'Blown Leads',
                'Avg 4th Qtr Points',
                'Avg Comeback 4th Qtr Points'
            ]
        ];
    }

    /**
     * Get scoring streaks statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public function getScoringStreaks(?string $teamFilter = null): array
    {
        $cacheKey = 'scoring_streaks_' . ($teamFilter ?? 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($teamFilter) {
            $sql = "
                WITH consecutive_quarters AS (
                    SELECT 
                        b.game_id,
                        CASE 
                            WHEN b.home_team = ? OR ? IS NULL THEN b.home_team
                            ELSE b.away_team 
                        END as team_abv,
                        CASE 
                            WHEN b.home_team = ? OR ? IS NULL THEN b.home_line_score
                            ELSE b.away_line_score 
                        END as line_score
                    FROM nfl_box_scores b
                    JOIN nfl_team_schedules s ON b.game_id = s.game_id
                    WHERE s.season_type = 'Regular Season'
                    AND (b.home_team = ? OR b.away_team = ? OR ? IS NULL)
                )
                SELECT 
                    team_abv,
                    COUNT(*) as games_analyzed,
                    SUM(CASE 
                        WHEN CAST(JSON_UNQUOTE(JSON_EXTRACT(line_score, '$.Q1')) AS UNSIGNED) > 0 AND
                            CAST(JSON_UNQUOTE(JSON_EXTRACT(line_score, '$.Q2')) AS UNSIGNED) > 0 AND
                            CAST(JSON_UNQUOTE(JSON_EXTRACT(line_score, '$.Q3')) AS UNSIGNED) > 0 AND
                            CAST(JSON_UNQUOTE(JSON_EXTRACT(line_score, '$.Q4')) AS UNSIGNED) > 0
                        THEN 1 ELSE 0 END) as games_scored_every_quarter,
                    SUM(CASE 
                        WHEN CAST(JSON_UNQUOTE(JSON_EXTRACT(line_score, '$.Q1')) AS UNSIGNED) = 0 AND
                            CAST(JSON_UNQUOTE(JSON_EXTRACT(line_score, '$.Q2')) AS UNSIGNED) = 0 AND
                            CAST(JSON_UNQUOTE(JSON_EXTRACT(line_score, '$.Q3')) AS UNSIGNED) = 0 AND
                            CAST(JSON_UNQUOTE(JSON_EXTRACT(line_score, '$.Q4')) AS UNSIGNED) > 0
                        THEN 1 ELSE 0 END) as fourth_quarter_only_scores,
                    MAX(
                        GREATEST(
                            CAST(JSON_UNQUOTE(JSON_EXTRACT(line_score, '$.Q1')) AS UNSIGNED),
                            CAST(JSON_UNQUOTE(JSON_EXTRACT(line_score, '$.Q2')) AS UNSIGNED),
                            CAST(JSON_UNQUOTE(JSON_EXTRACT(line_score, '$.Q3')) AS UNSIGNED),
                            CAST(JSON_UNQUOTE(JSON_EXTRACT(line_score, '$.Q4')) AS UNSIGNED)
                        )
                    ) as highest_scoring_quarter
                FROM consecutive_quarters
                GROUP BY team_abv
                ORDER BY games_scored_every_quarter DESC
            ";

            return DB::select($sql, array_fill(0, 7, $teamFilter));
        });

        return [
            'data' => $data,
            'headings' => [
                'Team',
                'Games Analyzed',
                'Games Scored Every Quarter',
                'Fourth Quarter Only Scores',
                'Highest Quarter Score'
            ]
        ];
    }

    /**
     * Get best receivers statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public function getBestReceivers(?string $teamFilter = null): array
    {
        $query = DB::table('nfl_player_stats')
            ->join('nfl_box_scores', 'nfl_player_stats.game_id', '=', 'nfl_box_scores.game_id')
            ->join('nfl_team_schedules', 'nfl_box_scores.game_id', '=', 'nfl_team_schedules.game_id')
            ->where('nfl_team_schedules.season_type', 'Regular Season')
            ->whereNotNull('receiving')
            ->select(
                'nfl_player_stats.long_name',
                'nfl_player_stats.team_abv',
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.recYds")) AS SIGNED)) AS total_receiving_yards'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.receptions")) AS UNSIGNED)) AS total_receptions'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.recTD")) AS UNSIGNED)) AS total_recTDs'),
                DB::raw('COALESCE(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.recYds")) AS SIGNED)) / NULLIF(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.receptions")) AS UNSIGNED)), 0), 0) AS average_yards_per_reception'),
                DB::raw('COALESCE(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.recYds")) AS SIGNED)) / NULLIF(COUNT(DISTINCT nfl_box_scores.game_id), 0), 0) AS avg_yards_per_game')
            )
            ->when($teamFilter, function ($q) use ($teamFilter) {
                $q->where('nfl_player_stats.team_abv', $teamFilter);
            })
            ->groupBy('nfl_player_stats.long_name', 'nfl_player_stats.team_abv')
            ->orderBy('total_receiving_yards', 'desc')
            ->paginate(10);

        return [
            'data' => $query->items(),
            'headings' => [
                'Player',
                'Team',
                'Total Receiving Yards',
                'Total Receptions',
                'Receiving TDs',
                'Average Yards Per Reception',
                'Average Yards Per Game'
            ]
        ];
    }

    /**
     * Get best rushers statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public
    function getBestRushers(?string $teamFilter = null): array
    {
        $query = DB::table('nfl_player_stats')
            ->join('nfl_box_scores', 'nfl_player_stats.game_id', '=', 'nfl_box_scores.game_id')
            ->join('nfl_team_schedules', 'nfl_box_scores.game_id', '=', 'nfl_team_schedules.game_id')
            ->where('nfl_team_schedules.season_type', 'Regular Season')
            ->whereNotNull('rushing')
            ->select(
                'nfl_player_stats.long_name',
                'nfl_player_stats.team_abv',
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.rushYds")) AS SIGNED)) AS total_rushing_yards'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.carries")) AS UNSIGNED)) AS total_attempts'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.rushTD")) AS UNSIGNED)) AS total_rushing_TDs'),
                DB::raw('COUNT(DISTINCT nfl_box_scores.game_id) AS games_played'),
                DB::raw('COALESCE(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.rushYds")) AS SIGNED)) / NULLIF(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.carries")) AS UNSIGNED)), 0), 0) AS average_yards_per_attempt'),
                DB::raw('COALESCE(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.rushYds")) AS SIGNED)) / NULLIF(COUNT(DISTINCT nfl_box_scores.game_id), 0), 0) AS avg_yards_per_game')
            )
            ->when($teamFilter, function ($q) use ($teamFilter) {
                $q->where('nfl_player_stats.team_abv', $teamFilter);
            })
            ->groupBy('nfl_player_stats.long_name', 'nfl_player_stats.team_abv')
            ->orderBy('total_rushing_yards', 'desc')
            ->paginate(10);

        return [
            'data' => $query->items(),
            'headings' => [
                'Player',
                'Team',
                'Total Rushing Yards',
                'Total Attempts',
                'Rushing TDs',
                'Games Played',
                'Average Yards Per Attempt',
                'Average Yards Per Game'
            ]
        ];
    }


    /**
     * Get best tacklers statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public
    function getBestTacklers(?string $teamFilter = null): array
    {
        $query = DB::table('nfl_player_stats')
            ->join('nfl_box_scores', 'nfl_player_stats.game_id', '=', 'nfl_box_scores.game_id')
            ->join('nfl_team_schedules', 'nfl_box_scores.game_id', '=', 'nfl_team_schedules.game_id')
            ->where('nfl_team_schedules.season_type', 'Regular Season')
            ->whereNotNull('defense')
            ->select(
                'nfl_player_stats.long_name',
                'nfl_player_stats.team_abv',
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.totalTackles")) AS UNSIGNED)) AS total_tackles'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.sacks")) AS UNSIGNED)) AS total_sacks'),
                DB::raw('COALESCE(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.totalTackles")) AS UNSIGNED)) / NULLIF(COUNT(DISTINCT nfl_box_scores.game_id), 0), 0) AS avg_tackles_per_game'),
                DB::raw('COALESCE(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.sacks")) AS UNSIGNED)) / NULLIF(COUNT(DISTINCT nfl_box_scores.game_id), 0), 0) AS avg_sacks_per_game')
            )
            ->when($teamFilter, function ($q) use ($teamFilter) {
                $q->where('nfl_player_stats.team_abv', $teamFilter);
            })
            ->groupBy('nfl_player_stats.long_name', 'nfl_player_stats.team_abv')
            ->orderBy('total_tackles', 'desc')
            ->paginate(10);

        return [
            'data' => $query->items(),
            'headings' => [
                'Player',
                'Team',
                'Total Tackles',
                'Total Sacks',
                'Avg Tackles',
                'Avg Sacks'
            ]
        ];
    }


    /**
     * Get big playmakers statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public
    function getBigPlaymakers(?string $teamFilter = null): array
    {
        $cacheKey = 'big_playmakers_' . ($teamFilter ?? 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($teamFilter) {
            $sql = "
                WITH player_big_plays AS (
                    SELECT 
                        ps.long_name,
                        ps.team_abv,
                        CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.longRec')), '0') AS SIGNED) as longest_reception,
                        CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.longRush')), '0') AS SIGNED) as longest_rush,
                        CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.recYds')), '0') AS SIGNED) as receiving_yards,
                        CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.receptions')), '0') AS SIGNED) as receptions,
                        CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.rushYds')), '0') AS SIGNED) as rushing_yards,
                        CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.carries')), '0') AS SIGNED) as carries
                    FROM nfl_player_stats ps
                    WHERE (receiving IS NOT NULL OR rushing IS NOT NULL)
                    AND (ps.team_abv = ? OR ? IS NULL)
                )
                SELECT 
                    long_name,
                    team_abv,
                    COUNT(*) as games_played,
                    MAX(longest_reception) as longest_reception,
                    MAX(longest_rush) as longest_rush,
                    SUM(receiving_yards) as total_receiving_yards,
                    SUM(rushing_yards) as total_rushing_yards,
                    SUM(receptions) as total_receptions,
                    SUM(carries) as total_carries,
                    ROUND(
                        (SUM(receiving_yards) + SUM(rushing_yards)) / COUNT(*), 
                        1
                    ) as avg_yards_per_game,
                    COUNT(
                        CASE 
                            WHEN longest_reception >= 20 OR longest_rush >= 20 
                            THEN 1 
                        END
                    ) as games_with_20plus_plays,
                    SUM(receiving_yards + rushing_yards) as total_yards
                FROM player_big_plays
                GROUP BY long_name, team_abv
                HAVING (
                    MAX(longest_reception) >= 20 
                    OR MAX(longest_rush) >= 20
                    OR total_yards > 0
                )
                ORDER BY games_with_20plus_plays DESC, total_yards DESC
                LIMIT 20
            ";

            return DB::select($sql, array_fill(0, 2, $teamFilter));
        });

        return [
            'data' => $data,
            'headings' => [
                'Player',
                'Team',
                'Games',
                'Longest Reception',
                'Longest Rush',
                'Receiving Yards',
                'Rushing Yards',
                'Receptions',
                'Carries',
                'Yards/Game',
                '20+ Yard Plays',
                'Total Yards'
            ]
        ];
    }

    /**
     * Get defensive playmakers statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public function getDefensivePlaymakers(?string $teamFilter = null): array
    {
        $cacheKey = 'defensive_playmakers_' . ($teamFilter ?? 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($teamFilter) {
            $sql = "
                SELECT 
                    ps.long_name,
                    ps.team_abv,
                    COUNT(*) as games_played,
                    SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.totalTackles')) AS UNSIGNED)) as total_tackles,
                    SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.soloTackles')) AS UNSIGNED)) as solo_tackles,
                    SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.sacks')) AS UNSIGNED)) as sacks,
                    SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.defensiveInterceptions')) AS UNSIGNED)) as interceptions,
                    SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.passDeflections')) AS UNSIGNED)) as pass_deflections,
                    SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.forcedFumbles')) AS UNSIGNED)) as forced_fumbles,
                    SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.tfl')) AS UNSIGNED)) as tackles_for_loss,
                    ROUND(AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.totalTackles')) AS UNSIGNED)), 1) as avg_tackles_per_game,
                    SUM(
                        CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.sacks')) AS UNSIGNED) +
                        CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.defensiveInterceptions')) AS UNSIGNED) +
                        CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.forcedFumbles')) AS UNSIGNED)
                    ) as impact_plays
                FROM nfl_player_stats ps
                WHERE defense IS NOT NULL
                AND (ps.team_abv = ? OR ? IS NULL)
                GROUP BY ps.long_name, ps.team_abv
                HAVING total_tackles > 0
                ORDER BY impact_plays DESC, total_tackles DESC
                LIMIT 20
            ";

            return DB::select($sql, array_fill(0, 2, $teamFilter));
        });

        return [
            'data' => $data,
            'headings' => [
                'Player',
                'Team',
                'Games',
                'Total Tackles',
                'Solo Tackles',
                'Sacks',
                'INTs',
                'Pass Deflections',
                'Forced Fumbles',
                'TFLs',
                'Tackles/Game',
                'Impact Plays'
            ]
        ];
    }

    /**
     * Get dual threat players statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public function getDualThreatPlayers(?string $teamFilter = null): array
    {
        $cacheKey = 'dual_threat_' . ($teamFilter ?? 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($teamFilter) {
            $sql = "
                WITH player_stats AS (
                    SELECT 
                        ps.long_name,
                        ps.team_abv,
                        CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.recYds')) AS DECIMAL(12,2)) as receiving_yards,
                        CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.receptions')) AS DECIMAL(12,2)) as receptions,
                        CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.recTD')) AS DECIMAL(12,2)) as receiving_tds,
                        CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.rushYds')) AS DECIMAL(12,2)) as rushing_yards,
                        CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.carries')) AS DECIMAL(12,2)) as carries,
                        CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.rushTD')) AS DECIMAL(12,2)) as rushing_tds
                    FROM nfl_player_stats ps
                    WHERE receiving IS NOT NULL 
                    AND rushing IS NOT NULL
                    AND (ps.team_abv = ? OR ? IS NULL)
                )
                SELECT 
                    long_name,
                    team_abv,
                    COUNT(*) as games_played,
                    CAST(SUM(receiving_yards) AS DECIMAL(12,2)) as total_receiving_yards,
                    CAST(SUM(rushing_yards) AS DECIMAL(12,2)) as total_rushing_yards,
                    CAST(SUM(receptions) AS DECIMAL(12,2)) as total_receptions,
                    CAST(SUM(carries) AS DECIMAL(12,2)) as total_carries,
                    CAST(SUM(receiving_tds) AS DECIMAL(12,2)) as receiving_touchdowns,
                    CAST(SUM(rushing_tds) AS DECIMAL(12,2)) as rushing_touchdowns,
                    ROUND(SUM(receiving_yards) / NULLIF(SUM(receptions), 0), 1) as yards_per_reception,
                    ROUND(SUM(rushing_yards) / NULLIF(SUM(carries), 0), 1) as yards_per_carry,
                    ROUND((SUM(receiving_yards) + SUM(rushing_yards)) / COUNT(*), 1) as total_yards_per_game
                FROM player_stats
                GROUP BY long_name, team_abv
                HAVING total_receiving_yards > 0 AND total_rushing_yards > 0
                ORDER BY total_yards_per_game DESC
                LIMIT 20
            ";

            return DB::select($sql, array_fill(0, 2, $teamFilter));
        });

        return [
            'data' => $data,
            'headings' => [
                'Player',
                'Team',
                'Games',
                'Receiving Yards',
                'Rushing Yards',
                'Receptions',
                'Carries',
                'Receiving TDs',
                'Rushing TDs',
                'Yards/Reception',
                'Yards/Carry',
                'Total Yards/Game'
            ]
        ];
    }

    /**
     * Get offensive consistency statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public function getOffensiveConsistency(?string $teamFilter = null): array
    {
        $weeks = collect(config('nfl.weeks'));
        $currentDate = now()->format('Y-m-d');
        $currentWeek = $weeks->filter(function ($week) use ($currentDate) {
            return $currentDate >= $week['start'] && $currentDate <= $week['end'];
        })->keys()->first();

        // If current date is before season starts, use Week 1
        if (!$currentWeek) {
            $currentWeek = 'Week 1';
        }

        $activeWeeks = $weeks->take((int)str_replace('Week ', '', $currentWeek));
        $startDate = "'" . $activeWeeks->first()['start'] . "'";
        $endDate = "'" . $activeWeeks->last()['end'] . "'";
        $totalWeeks = $activeWeeks->count();

        $cacheKey = "offensive_consistency_{$teamFilter}_{$currentWeek}";

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($teamFilter, $startDate, $endDate, $totalWeeks) {
            $sql = "
                WITH game_stats AS (
                    SELECT 
                        ps.long_name,
                        ps.team_abv,
                        ps.game_id,
                        (CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.recYds')), '0') AS SIGNED) +
                         CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.rushYds')), '0') AS SIGNED)) as total_yards,
                        (CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.receptions')), '0') AS SIGNED) +
                         CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.carries')), '0') AS SIGNED)) as total_touches,
                        (CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.recTD')), '0') AS SIGNED) +
                         CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.rushTD')), '0') AS SIGNED)) as total_tds
                    FROM nfl_player_stats ps
                    JOIN nfl_box_scores b ON ps.game_id = b.game_id
                    JOIN nfl_team_schedules s ON b.game_id = s.game_id
                    WHERE (receiving IS NOT NULL OR rushing IS NOT NULL)
                    AND s.season_type = 'Regular Season'
                    AND s.game_date BETWEEN ${startDate} AND ${endDate}
                    AND (ps.team_abv = ? OR ? IS NULL)
                )
                -- Rest of your existing offensive consistency query...
            ";

            return DB::select($sql, [$teamFilter, $teamFilter, $totalWeeks]);
        });

        return [
            'data' => $data,
            'headings' => [
                'Player',
                'Team',
                'Games',
                'Avg Yards/Game',
                'Avg Touches/Game',
                'Avg TDs/Game',
                'Yards StdDev',
                'Touches StdDev',
                'Yards CV%',
                'Total Yards',
                'Total Touches',
                'Total TDs',
                '50+ Yard Games %',
                'Min Yards',
                'Max Yards',
                'Above Floor %',
                'Consistency Rating',
                'Reliability Score'
            ]
        ];
    }

    /**
     * Get NFL team statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public function getNflTeamStats(?string $teamFilter = null): array
    {
        $cacheKey = 'nfl_team_stats_' . ($teamFilter ?? 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($teamFilter) {
            $sql = "
                WITH team_game_stats AS (
                    SELECT 
                        ts.team_id,
                        ts.team_abv,
                        ts.game_id,
                        ts.total_yards,
                        ts.rushing_yards,
                        ts.passing_yards,
                        ts.points_allowed,
                        ROW_NUMBER() OVER (PARTITION BY ts.team_id ORDER BY ts.created_at DESC) as game_number
                    FROM nfl_team_stats ts
                    WHERE (ts.team_id = ? OR ? IS NULL)
                ),
                team_metrics AS (
                    SELECT 
                        team_id,
                        team_abv,
                        COUNT(*) as games_analyzed,
                        ROUND(AVG(total_yards), 1) as avg_total_yards,
                        ROUND(AVG(rushing_yards), 1) as avg_rushing_yards,
                        ROUND(AVG(passing_yards), 1) as avg_passing_yards,
                        ROUND(STDDEV(total_yards), 1) as total_yards_stddev,
                        ROUND(STDDEV(rushing_yards), 1) as rushing_yards_stddev,
                        ROUND(STDDEV(passing_yards), 1) as passing_yards_stddev,
                        MIN(total_yards) as min_total_yards,
                        MAX(total_yards) as max_total_yards,
                        MIN(rushing_yards) as min_rushing_yards,
                        MAX(rushing_yards) as max_rushing_yards,
                        MIN(passing_yards) as min_passing_yards,
                        MAX(passing_yards) as max_passing_yards,
                        GROUP_CONCAT(
                            CONCAT_WS(':', 
                                game_number,
                                total_yards,
                                rushing_yards,
                                passing_yards
                            )
                            ORDER BY game_number ASC
                        ) as recent_games,
                        ROUND(
                            (SUM(CASE WHEN total_yards >= 350 THEN 1 ELSE 0 END) * 100.0) / COUNT(*),
                            1
                        ) as pct_games_over_350,
                        ROUND(
                            (SUM(CASE WHEN rushing_yards >= 150 THEN 1 ELSE 0 END) * 100.0) / COUNT(*),
                            1
                        ) as pct_games_rush_150,
                        ROUND(
                            (SUM(CASE WHEN passing_yards >= 250 THEN 1 ELSE 0 END) * 100.0) / COUNT(*),
                            1
                        ) as pct_games_pass_250
                    FROM team_game_stats
                    WHERE game_number <= 5
                    GROUP BY team_id, team_abv
                ),
                trend_analysis AS (
                    SELECT
                        tm.*,
                        ROUND((total_yards_stddev / avg_total_yards) * 100, 1) as total_yards_cv,
                        ROUND((rushing_yards_stddev / avg_rushing_yards) * 100, 1) as rushing_yards_cv,
                        ROUND((passing_yards_stddev / avg_passing_yards) * 100, 1) as passing_yards_cv,
                        ROUND((avg_rushing_yards / avg_total_yards) * 100, 1) as rushing_yards_pct,
                        ROUND((avg_passing_yards / avg_total_yards) * 100, 1) as passing_yards_pct,
                        CASE 
                            WHEN avg_total_yards >= 350 AND total_yards_stddev <= 50 THEN 'ELITE'
                            WHEN avg_total_yards >= 300 AND total_yards_stddev <= 60 THEN 'STRONG'
                            WHEN avg_total_yards >= 250 AND total_yards_stddev <= 70 THEN 'ABOVE_AVERAGE'
                            WHEN avg_total_yards >= 200 THEN 'AVERAGE'
                            ELSE 'BELOW_AVERAGE'
                        END as performance_rating,
                        CASE 
                            WHEN SUBSTRING_INDEX(recent_games, ',', 1) > SUBSTRING_INDEX(recent_games, ',', -1) 
                            THEN 'IMPROVING'
                            WHEN SUBSTRING_INDEX(recent_games, ',', 1) < SUBSTRING_INDEX(recent_games, ',', -1) 
                            THEN 'DECLINING'
                            ELSE 'STABLE'
                        END as trend_direction
                    FROM team_metrics tm
                )
                SELECT 
                    team_id,
                    team_abv,
                    games_analyzed,
                    avg_total_yards,
                    avg_rushing_yards,
                    avg_passing_yards,
                    rushing_yards_pct,
                    passing_yards_pct,
                    total_yards_cv as consistency_score,
                    rushing_yards_cv as rushing_consistency,
                    passing_yards_cv as passing_consistency,
                    max_total_yards as best_game,
                    min_total_yards as worst_game,
                    pct_games_over_350 as explosive_offense_pct,
                    max_rushing_yards as best_rushing,
                    min_rushing_yards as worst_rushing,
                    pct_games_rush_150 as strong_rush_pct,
                    max_passing_yards as best_passing,
                    min_passing_yards as worst_passing,
                    pct_games_pass_250 as strong_pass_pct,
                    performance_rating,
                    trend_direction
                FROM trend_analysis
                ORDER BY avg_total_yards DESC;
            ";

            return DB::select($sql, [$teamFilter, $teamFilter]);
        });

        return [
            'data' => $data,
            'headings' => [
                'ID',
                'Team',
                'Games',
                'Avg Total Yards',
                'Avg Rush Yards',
                'Avg Pass Yards',
                'Rush %',
                'Pass %',
                'Consistency',
                'Rush Consistency',
                'Pass Consistency',
                'Best Game',
                'Worst Game',
                'Explosive %',
                'Best Rush',
                'Worst Rush',
                'Strong Rush %',
                'Best Pass',
                'Worst Pass',
                'Strong Pass %',
                'Rating',
                'Trend'
            ]
        ];
    }

    /**
     * Get over/under analysis statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public function getOverUnderAnalysis(?string $teamFilter = null): array
    {
        $cacheKey = 'over_under_' . ($teamFilter ?? 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($teamFilter) {
            $sql = "
                WITH game_totals AS (
                    SELECT 
                        b.game_id,
                        b.home_team,
                        b.away_team,
                        b.home_points + b.away_points as total_points,
                        JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, '$.Q1')) + 
                        JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, '$.Q2')) as home_first_half,
                        JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, '$.Q1')) + 
                        JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, '$.Q2')) as away_first_half,
                        JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, '$.Q4')) as home_fourth_quarter,
                        JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, '$.Q4')) as away_fourth_quarter
                    FROM nfl_box_scores b
                    JOIN nfl_team_schedules s ON b.game_id = s.game_id
                    WHERE s.season_type = 'Regular Season'
                    AND (b.home_team = ? OR b.away_team = ? OR ? IS NULL)
                )
                SELECT 
                    CASE 
                        WHEN ? IS NOT NULL THEN ?
                        ELSE CONCAT(home_team, ' vs ', away_team)
                    END as matchup,
                    COUNT(*) as games_analyzed,
                    ROUND(AVG(total_points), 1) as avg_total_points,
                    ROUND(AVG(home_first_half + away_first_half), 1) as avg_first_half_points,
                    ROUND(AVG(CAST(home_fourth_quarter AS UNSIGNED) + CAST(away_fourth_quarter AS UNSIGNED)), 1) as avg_fourth_quarter_points,
                    MAX(total_points) as highest_combined_score,
                    MIN(total_points) as lowest_combined_score,
                    ROUND(STDDEV(total_points), 1) as points_variance,
                    ROUND(
                        SUM(CASE WHEN total_points > 44.5 THEN 1 ELSE 0 END) * 100.0 / COUNT(*),
                        1
                    ) as pct_games_over_44_5,
                    ROUND(
                        SUM(CASE WHEN home_first_half + away_first_half > 21.5 THEN 1 ELSE 0 END) * 100.0 / COUNT(*),
                        1
                    ) as pct_first_half_over_21_5,
                    COUNT(CASE WHEN total_points > 44.5 AND home_first_half + away_first_half > 21.5 THEN 1 END) as correlated_overs,
                    ROUND(AVG(CAST(home_fourth_quarter AS UNSIGNED) + CAST(away_fourth_quarter AS UNSIGNED)) * 4, 1) as projected_pace
                FROM game_totals
                GROUP BY matchup;
            ";

            return DB::select($sql, array_fill(0, 5, $teamFilter));
        });

        return [
            'data' => $data,
            'headings' => [
                'Matchup',
                'Games Analyzed',
                'Avg Total Points',
                'Avg First Half Points',
                'Avg Fourth Quarter Points',
                'Highest Combined Score',
                'Lowest Combined Score',
                'Points Variance',
                'Games Over 44.5%',
                'First Half Over 21.5%',
                'Correlated Overs',
                'Projected Pace'
            ]
        ];
    }

    /**
     * Get team vs conference statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public function getTeamVsConference(?string $teamFilter = null): array
    {
        $cacheKey = 'team_vs_conference_' . ($teamFilter ?? 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($teamFilter) {
            $sql = "
                WITH conference_games AS (
                    SELECT 
                        b.game_id,
                        b.home_team,
                        b.away_team,
                        t1.conference_abv,
                        t1.conference as conference_name,
                        CASE 
                            WHEN b.home_team = t1.team_abv THEN b.home_points
                            ELSE b.away_points
                        END as team_points,
                        CASE 
                            WHEN b.home_team = t1.team_abv THEN b.away_points
                            ELSE b.home_points
                        END as opponent_points,
                        CASE 
                            WHEN b.home_team = t1.team_abv THEN ts1.total_yards
                            ELSE ts2.total_yards
                        END as team_yards,
                        CASE 
                            WHEN b.home_team = t1.team_abv THEN ts2.total_yards
                            ELSE ts1.total_yards
                        END as opponent_yards,
                        t1.team_abv
                    FROM nfl_box_scores b
                    JOIN nfl_team_schedules s ON b.game_id = s.game_id
                    JOIN nfl_teams t1 ON (b.home_team = t1.team_abv OR b.away_team = t1.team_abv)
                    JOIN nfl_teams t2 ON (
                        CASE 
                            WHEN b.home_team = t1.team_abv THEN b.away_team
                            ELSE b.home_team
                        END = t2.team_abv
                    )
                    JOIN nfl_team_stats ts1 ON (b.game_id = ts1.game_id AND ts1.team_abv = t1.team_abv)
                    JOIN nfl_team_stats ts2 ON (
                        b.game_id = ts2.game_id 
                        AND ts2.team_abv = CASE 
                            WHEN b.home_team = t1.team_abv THEN b.away_team
                            ELSE b.home_team
                        END
                    )
                    WHERE s.season_type = 'Regular Season'
                    AND t1.conference_abv = t2.conference_abv
                    AND (t1.team_abv = ? OR ? IS NULL)
                )
                SELECT 
                    team_abv,
                    conference_abv,
                    conference_name,
                    COUNT(*) as games_played,
                    ROUND(AVG(team_points), 1) as avg_points_for,
                    ROUND(AVG(opponent_points), 1) as avg_points_against,
                    ROUND(AVG(team_yards), 1) as avg_yards_for,
                    ROUND(AVG(opponent_yards), 1) as avg_yards_against,
                    SUM(CASE WHEN team_points > opponent_points THEN 1 ELSE 0 END) as wins,
                    SUM(CASE WHEN team_points < opponent_points THEN 1 ELSE 0 END) as losses,
                    ROUND(AVG(team_points - opponent_points), 1) as avg_margin,
                    ROUND(
                        SUM(CASE WHEN team_points > opponent_points THEN 1 ELSE 0 END) * 100.0 / 
                        COUNT(*), 1
                    ) as win_percentage,
                    ROUND(STDDEV(team_points), 1) as points_stddev,
                    ROUND(STDDEV(team_yards), 1) as yards_stddev
                FROM conference_games
                GROUP BY team_abv, conference_abv, conference_name
                ORDER BY win_percentage DESC, avg_margin DESC;
            ";

            return DB::select($sql, [$teamFilter, $teamFilter]);
        });

        return [
            'data' => $data,
            'headings' => [
                'Team',
                'Conf',
                'Conference',
                'Games Played',
                'Avg Points For',
                'Avg Points Against',
                'Avg Yards For',
                'Avg Yards Against',
                'Wins',
                'Losses',
                'Avg Margin',
                'Win %',
                'Points StdDev',
                'Yards StdDev'
            ]
        ];
    }

    /**
     * Get team vs division statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public function getTeamVsDivision(?string $teamFilter = null): array
    {
        $cacheKey = 'team_vs_division_' . ($teamFilter ?? 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($teamFilter) {
            $sql = "
                WITH division_games AS (
                    SELECT 
                        b.game_id,
                        b.home_team,
                        b.away_team,
                        t1.conference_abv,
                        t1.conference as conference_name,
                        t1.division,
                        CASE 
                            WHEN b.home_team = t1.team_abv THEN b.home_points
                            ELSE b.away_points
                        END as team_points,
                        CASE 
                            WHEN b.home_team = t1.team_abv THEN b.away_points
                            ELSE b.home_points
                        END as opponent_points,
                        CASE 
                            WHEN b.home_team = t1.team_abv THEN ts1.total_yards
                            ELSE ts2.total_yards
                        END as team_yards,
                        CASE 
                            WHEN b.home_team = t1.team_abv THEN ts2.total_yards
                            ELSE ts1.total_yards
                        END as opponent_yards,
                        t1.team_abv
                    FROM nfl_box_scores b
                    JOIN nfl_team_schedules s ON b.game_id = s.game_id
                    JOIN nfl_teams t1 ON (b.home_team = t1.team_abv OR b.away_team = t1.team_abv)
                    JOIN nfl_teams t2 ON (
                        CASE 
                            WHEN b.home_team = t1.team_abv THEN b.away_team
                            ELSE b.home_team
                        END = t2.team_abv
                    )
                    JOIN nfl_team_stats ts1 ON (b.game_id = ts1.game_id AND ts1.team_abv = t1.team_abv)
                    JOIN nfl_team_stats ts2 ON (
                        b.game_id = ts2.game_id 
                        AND ts2.team_abv = CASE 
                            WHEN b.home_team = t1.team_abv THEN b.away_team
                            ELSE b.home_team
                        END
                    )
                    WHERE s.season_type = 'Regular Season'
                    AND t1.division = t2.division
                    AND (t1.team_abv = ? OR ? IS NULL)
                )
                SELECT 
                    team_abv,
                    conference_abv,
                    conference_name,
                    division,
                    COUNT(*) as games_played,
                    ROUND(AVG(team_points), 1) as avg_points_for,
                    ROUND(AVG(opponent_points), 1) as avg_points_against,
                    ROUND(AVG(team_yards), 1) as avg_yards_for,
                    ROUND(AVG(opponent_yards), 1) as avg_yards_against,
                    SUM(CASE WHEN team_points > opponent_points THEN 1 ELSE 0 END) as wins,
                    SUM(CASE WHEN team_points < opponent_points THEN 1 ELSE 0 END) as losses,
                    ROUND(AVG(team_points - opponent_points), 1) as avg_margin,
                    ROUND(
                        SUM(CASE WHEN team_points > opponent_points THEN 1 ELSE 0 END) * 100.0 / 
                        COUNT(*), 1
                    ) as win_percentage,
                    ROUND(STDDEV(team_points), 1) as points_stddev,
                    ROUND(STDDEV(team_yards), 1) as yards_stddev
                FROM division_games
                GROUP BY team_abv, conference_abv, conference_name, division
                ORDER BY win_percentage DESC, avg_margin DESC;
            ";

            return DB::select($sql, [$teamFilter, $teamFilter]);
        });

        return [
            'data' => $data,
            'headings' => [
                'Team',
                'Conf',
                'Conference',
                'Division',
                'Games Played',
                'Avg Points For',
                'Avg Points Against',
                'Avg Yards For',
                'Avg Yards Against',
                'Wins',
                'Losses',
                'Avg Margin',
                'Win %',
                'Points StdDev',
                'Yards StdDev'
            ]
        ];
    }

    /**
     * Get player vs conference statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public function getPlayerVsConference(?string $teamFilter = null): array
    {
        $cacheKey = 'player_vs_conference_' . ($teamFilter ?? 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($teamFilter) {
            $sql = "
                WITH conference_games AS (
                    SELECT 
                        ps.*,
                        t1.conference_abv,
                        t1.conference as conference_name
                    FROM nfl_player_stats ps
                    JOIN nfl_box_scores b ON ps.game_id = b.game_id
                    JOIN nfl_team_schedules s ON b.game_id = s.game_id
                    JOIN nfl_teams t1 ON ps.team_abv = t1.team_abv
                    JOIN nfl_teams t2 ON (
                        CASE 
                            WHEN b.home_team = ps.team_abv THEN b.away_team 
                            ELSE b.home_team 
                        END = t2.team_abv
                    )
                    WHERE s.season_type = 'Regular Season'
                    AND t1.conference_abv = t2.conference_abv
                    AND (ps.team_abv = ? OR ? IS NULL)
                )
                SELECT 
                    cg.long_name as player,
                    cg.team_abv as team,
                    conference_abv,
                    conference_name,
                    COUNT(DISTINCT cg.game_id) as games_played,
                    ROUND(AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.recYds')) AS UNSIGNED)), 1) as avg_receiving_yards,
                    ROUND(AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.rushYds')) AS UNSIGNED)), 1) as avg_rushing_yards,
                    SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.recTD')) AS UNSIGNED)) as receiving_tds,
                    SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.rushTD')) AS UNSIGNED)) as rushing_tds,
                    ROUND(AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.totalTackles')) AS UNSIGNED)), 1) as avg_tackles,
                    SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.sacks')) AS UNSIGNED)) as total_sacks,
                    SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.defensiveInterceptions')) AS UNSIGNED)) as total_ints,
                    ROUND(STDDEV(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.recYds')) AS UNSIGNED)), 1) as receiving_yards_stddev,
                    ROUND(STDDEV(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.rushYds')) AS UNSIGNED)), 1) as rushing_yards_stddev
                FROM conference_games cg
                WHERE (receiving IS NOT NULL OR rushing IS NOT NULL OR defense IS NOT NULL)
                GROUP BY cg.long_name, cg.team_abv, conference_abv, conference_name
                HAVING games_played >= 2
                ORDER BY (receiving_tds + rushing_tds + total_sacks + total_ints) DESC
                LIMIT 50;
            ";

            return DB::select($sql, [$teamFilter, $teamFilter]);
        });

        return [
            'data' => $data,
            'headings' => [
                'Player',
                'Team',
                'Conf',
                'Conference',
                'Games Played',
                'Avg Receiving Yards',
                'Avg Rushing Yards',
                'Receiving TDs',
                'Rushing TDs',
                'Avg Tackles',
                'Total Sacks',
                'Total INTs',
                'Receiving Yards StdDev',
                'Rushing Yards StdDev'
            ]
        ];
    }

    /**
     * Get player vs division statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public function getPlayerVsDivision(?string $teamFilter = null): array
    {
        $cacheKey = 'player_vs_division_' . ($teamFilter ?? 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($teamFilter) {
            $sql = "
                WITH division_games AS (
                    SELECT 
                        ps.*,
                        t1.conference_abv,
                        t1.conference as conference_name,
                        t1.division
                    FROM nfl_player_stats ps
                    JOIN nfl_box_scores b ON ps.game_id = b.game_id
                    JOIN nfl_team_schedules s ON b.game_id = s.game_id
                    JOIN nfl_teams t1 ON ps.team_abv = t1.team_abv
                    JOIN nfl_teams t2 ON (
                        CASE 
                            WHEN b.home_team = ps.team_abv THEN b.away_team 
                            ELSE b.home_team 
                        END = t2.team_abv
                    )
                    WHERE s.season_type = 'Regular Season'
                    AND t1.division = t2.division
                    AND (ps.team_abv = ? OR ? IS NULL)
                )
                SELECT 
                    dg.long_name as player,
                    dg.team_abv as team,
                    conference_abv,
                    conference_name,
                    division,
                    COUNT(DISTINCT dg.game_id) as games_played,
                    ROUND(AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.recYds')) AS UNSIGNED)), 1) as avg_receiving_yards,
                    ROUND(AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.rushYds')) AS UNSIGNED)), 1) as avg_rushing_yards,
                    SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.recTD')) AS UNSIGNED)) as receiving_tds,
                    SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.rushTD')) AS UNSIGNED)) as rushing_tds,
                    ROUND(AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.totalTackles')) AS UNSIGNED)), 1) as avg_tackles,
                    SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.sacks')) AS UNSIGNED)) as total_sacks,
                    SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, '$.defensiveInterceptions')) AS UNSIGNED)) as total_ints,
                    ROUND(STDDEV(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, '$.recYds')) AS UNSIGNED)), 1) as receiving_yards_stddev,
                    ROUND(STDDEV(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, '$.rushYds')) AS UNSIGNED)), 1) as rushing_yards_stddev
                FROM division_games dg
                WHERE (receiving IS NOT NULL OR rushing IS NOT NULL OR defense IS NOT NULL)
                GROUP BY dg.long_name, dg.team_abv, conference_abv, conference_name, division
                HAVING games_played >= 2
                ORDER BY (receiving_tds + rushing_tds + total_sacks + total_ints) DESC
                LIMIT 50;
            ";

            return DB::select($sql, [$teamFilter, $teamFilter]);
        });

        return [
            'data' => $data,
            'headings' => [
                'Player',
                'Team',
                'Conf',
                'Conference',
                'Division',
                'Games Played',
                'Avg Receiving Yards',
                'Avg Rushing Yards',
                'Receiving TDs',
                'Rushing TDs',
                'Avg Tackles',
                'Total Sacks',
                'Total INTs',
                'Receiving Yards StdDev',
                'Rushing Yards StdDev'
            ]
        ];
    }

    /**
     * Get conference statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public function getConferenceStats(?string $teamFilter = null): array
    {
        $cacheKey = 'conference_stats_' . ($teamFilter ?? 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($teamFilter) {
            $sql = "
                WITH conference_summary AS (
                    SELECT 
                        t.conference_abv,
                        t.conference,
                        COUNT(DISTINCT b.game_id) as total_games,
                        ROUND(AVG(ts.total_yards), 1) as avg_yards,
                        ROUND(AVG(
                            CASE 
                                WHEN b.home_team = ts.team_abv THEN b.home_points
                                ELSE b.away_points
                            END
                        ), 1) as avg_points,
                        ROUND(STDDEV(ts.total_yards), 1) as yards_stddev,
                        ROUND(AVG(ts.rushing_yards / NULLIF(ts.total_yards, 0) * 100), 1) as rush_percentage
                    FROM nfl_team_stats ts
                    JOIN nfl_box_scores b ON ts.game_id = b.game_id
                    JOIN nfl_teams t ON ts.team_abv = t.team_abv
                    JOIN nfl_team_schedules s ON b.game_id = s.game_id
                    WHERE s.season_type = 'Regular Season'
                    AND (ts.team_abv = ? OR ? IS NULL)
                    GROUP BY t.conference_abv, t.conference
                )
                SELECT 
                    cs.*,
                    CASE 
                        WHEN yards_stddev <= 50 AND avg_yards >= 350 THEN 'Elite'
                        WHEN yards_stddev <= 60 AND avg_yards >= 300 THEN 'Strong'
                        WHEN yards_stddev <= 70 AND avg_yards >= 250 THEN 'Above Average'
                        ELSE 'Average'
                    END as conference_rating
                FROM conference_summary cs
                ORDER BY avg_yards DESC;
            ";

            return DB::select($sql, [$teamFilter, $teamFilter]);
        });

        return [
            'data' => $data,
            'headings' => [
                'Conference Abv',
                'Conference',
                'Total Games',
                'Avg Yards',
                'Avg Points',
                'Yards StdDev',
                'Rush %',
                'Conference Rating'
            ]
        ];
    }

    /**
     * Get division statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public function getDivisionStats(?string $teamFilter = null): array
    {
        $cacheKey = 'division_stats_' . ($teamFilter ?? 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($teamFilter) {
            $sql = "
                WITH division_summary AS (
                    SELECT 
                        t.conference_abv,
                        t.conference,
                        t.division,
                        COUNT(DISTINCT b.game_id) as total_games,
                        ROUND(AVG(ts.total_yards), 1) as avg_yards,
                        ROUND(AVG(
                            CASE 
                                WHEN b.home_team = ts.team_abv THEN b.home_points
                                ELSE b.away_points
                            END
                        ), 1) as avg_points,
                        ROUND(STDDEV(ts.total_yards), 1) as yards_stddev,
                        ROUND(AVG(ts.rushing_yards / NULLIF(ts.total_yards, 0) * 100), 1) as rush_percentage,
                        COUNT(DISTINCT ts.team_abv) as teams_in_division
                    FROM nfl_team_stats ts
                    JOIN nfl_box_scores b ON ts.game_id = b.game_id
                    JOIN nfl_teams t ON ts.team_abv = t.team_abv
                    JOIN nfl_team_schedules s ON b.game_id = s.game_id
                    WHERE s.season_type = 'Regular Season'
                    AND (ts.team_abv = ? OR ? IS NULL)
                    GROUP BY t.conference_abv, t.conference, t.division
                )
                SELECT 
                    ds.*,
                    CASE 
                        WHEN yards_stddev <= 50 AND avg_yards >= 350 THEN 'Elite'
                        WHEN yards_stddev <= 60 AND avg_yards >= 300 THEN 'Strong'
                        WHEN yards_stddev <= 70 AND avg_yards >= 250 THEN 'Above Average'
                        ELSE 'Average'
                    END as division_rating
                FROM division_summary ds
                ORDER BY avg_yards DESC;
            ";

            return DB::select($sql, [$teamFilter, $teamFilter]);
        });

        return [
            'data' => $data,
            'headings' => [
                'Conference Abv',
                'Conference',
                'Division',
                'Total Games',
                'Avg Yards',
                'Avg Points',
                'Yards StdDev',
                'Rush %',
                'Teams in Division',
                'Division Rating'
            ]
        ];
    }

    /**
     * Get team matchup edge statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public function getTeamMatchupEdge(?string $teamFilter = null): array
    {
        $cacheKey = 'team_matchup_edge_' . ($teamFilter ?? 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($teamFilter) {
            $sql = "
                WITH game_matchups AS (
                    SELECT 
                        b.game_id,
                        s.game_date,
                        ts.team_abv,
                        CASE 
                            WHEN b.home_team = ts.team_abv THEN b.away_team
                            ELSE b.home_team
                        END as opponent_team,
                        ts.total_yards,
                        ts.rushing_yards,
                        ts.passing_yards,
                        CASE 
                            WHEN b.home_team = ts.team_abv THEN b.home_points
                            ELSE b.away_points
                        END as points_scored,
                        CASE 
                            WHEN b.home_team = ts.team_abv THEN b.away_points
                            ELSE b.home_points
                        END as points_allowed,
                        CASE 
                            WHEN b.home_team = ts.team_abv THEN 'home'
                            ELSE 'away'
                        END as location
                    FROM nfl_team_stats ts
                    JOIN nfl_box_scores b ON ts.game_id = b.game_id
                    JOIN nfl_team_schedules s ON b.game_id = s.game_id
                    WHERE s.season_type = 'Regular Season'
                    AND (ts.team_abv = ? OR ? IS NULL)
                    ORDER BY s.game_date DESC
                    LIMIT 5
                ),
                team_averages AS (
                    SELECT 
                        team_abv,
                        opponent_team,
                        DATE_FORMAT(game_date, '%Y-%m-%d') as game_date,
                        total_yards,
                        rushing_yards,
                        passing_yards,
                        points_scored,
                        points_allowed,
                        total_yards - LAG(total_yards) OVER (PARTITION BY team_abv ORDER BY game_date) as yards_trend,
                        points_scored - LAG(points_scored) OVER (PARTITION BY team_abv ORDER BY game_date) as scoring_trend,
                        location
                    FROM game_matchups
                )
                SELECT 
                    ta.team_abv,
                    ta.opponent_team,
                    ta.game_date,
                    ta.total_yards,
                    ta.rushing_yards,
                    ta.passing_yards,
                    ta.points_scored,
                    ta.points_allowed,
                    ta.total_yards - AVG(gm.total_yards) OVER (PARTITION BY ta.opponent_team) as yards_differential,
                    ta.points_scored - ta.points_allowed as points_differential,
                    CASE 
                        WHEN ta.total_yards > AVG(gm.total_yards) OVER (PARTITION BY ta.opponent_team) 
                        AND ta.points_scored > AVG(gm.points_scored) OVER (PARTITION BY ta.opponent_team)
                        THEN 'High'
                        WHEN ta.total_yards < AVG(gm.total_yards) OVER (PARTITION BY ta.opponent_team) 
                        AND ta.points_scored < AVG(gm.points_scored) OVER (PARTITION BY ta.opponent_team)
                        THEN 'Low'
                        ELSE 'Medium'
                    END as win_probability,
                    ROUND(
                        (
                            (ta.total_yards / NULLIF(AVG(gm.total_yards) OVER (PARTITION BY ta.opponent_team), 0)) * 40 +
                            (ta.points_scored / NULLIF(AVG(gm.points_scored) OVER (PARTITION BY ta.opponent_team), 0)) * 40 +
                            CASE 
                                WHEN ta.location = 'home' THEN 20
                                ELSE 0
                            END
                        ),
                        1
                    ) as edge_score
                FROM team_averages ta
                JOIN game_matchups gm ON ta.opponent_team = gm.team_abv
                ORDER BY ta.game_date DESC, edge_score DESC;
            ";

            return DB::select($sql, [$teamFilter, $teamFilter]);
        });

        return [
            'data' => $data,
            'headings' => [
                'Team',
                'Opponent',
                'Game Date',
                'Total Yards',
                'Rush Yards',
                'Pass Yards',
                'Points Scored',
                'Points Allowed',
                'Yards Differential',
                'Points Differential',
                'Win Probability',
                'Edge Score'
            ]
        ];
    }

    /**
     * Get first half tendencies statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public function getFirstHalfTendencies(?string $teamFilter = null): array
    {
        $cacheKey = 'first_half_tendencies_' . ($teamFilter ?? 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($teamFilter) {
            $sql = "
                WITH first_half_stats AS (
                    SELECT 
                        b.game_id,
                        CASE 
                            WHEN b.home_team = ? OR ? IS NULL THEN b.home_team
                            ELSE b.away_team 
                        END as team_abv,
                        CASE 
                            WHEN b.home_team = ? OR ? IS NULL THEN 
                                CAST(JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, '$.Q1')) AS UNSIGNED) +
                                CAST(JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, '$.Q2')) AS UNSIGNED)
                            ELSE 
                                CAST(JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, '$.Q1')) AS UNSIGNED) +
                                CAST(JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, '$.Q2')) AS UNSIGNED)
                        END as team_first_half_points,
                        CASE 
                            WHEN b.home_team = ? OR ? IS NULL THEN 
                                CAST(JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, '$.Q1')) AS UNSIGNED) +
                                CAST(JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, '$.Q2')) AS UNSIGNED)
                            ELSE 
                                CAST(JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, '$.Q1')) AS UNSIGNED) +
                                CAST(JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, '$.Q2')) AS UNSIGNED)
                        END as opponent_first_half_points
                    FROM nfl_box_scores b
                    JOIN nfl_team_schedules s ON b.game_id = s.game_id
                    WHERE s.season_type = 'Regular Season'
                    AND (b.home_team = ? OR b.away_team = ? OR ? IS NULL)
                )
                SELECT 
                    team_abv,
                    COUNT(*) as games_analyzed,
                    ROUND(AVG(team_first_half_points), 1) as avg_first_half_points,
                    ROUND(AVG(opponent_first_half_points), 1) as avg_opponent_first_half_points,
                    ROUND(AVG(team_first_half_points + opponent_first_half_points), 1) as avg_first_half_total,
                    ROUND(STDDEV(team_first_half_points), 1) as first_half_points_stddev,
                    ROUND(
                        SUM(CASE WHEN team_first_half_points > opponent_first_half_points THEN 1 ELSE 0 END) * 100.0 / COUNT(*),
                        1
                    ) as first_half_win_percentage,
                    ROUND(
                        SUM(CASE WHEN team_first_half_points + opponent_first_half_points > 21.5 THEN 1 ELSE 0 END) * 100.0 / COUNT(*),
                        1
                    ) as first_half_over_percentage,
                    MAX(team_first_half_points) as max_first_half_points,
                    MIN(team_first_half_points) as min_first_half_points
                FROM first_half_stats
                GROUP BY team_abv
                ORDER BY avg_first_half_points DESC;
            ";

            return DB::select($sql, array_fill(0, 9, $teamFilter));
        });

        return [
            'data' => $data,
            'headings' => [
                'Team',
                'Games Analyzed',
                'Avg First Half Points',
                'Avg Opponent First Half Points',
                'Avg First Half Total',
                'First Half Points StdDev',
                'First Half Win %',
                'First Half Over %',
                'Max First Half Points',
                'Min First Half Points'
            ]
        ];
    }

    /**
     * Get opponent adjusted statistics
     *
     * @param int $teamId
     * @param int $gamesBack
     * @return array
     */
    public function getOpponentAdjustedStats(int $teamId, int $gamesBack): array
    {
        $cacheKey = "opponent_adjusted_stats_{$teamId}_{$gamesBack}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($teamId, $gamesBack) {
            // Get league averages for comparison
            $leagueAverages = DB::table('nfl_team_stats')
                ->selectRaw('
                    AVG(total_yards) as avg_total_yards,
                    AVG(rushing_yards) as avg_rushing_yards,
                    AVG(passing_yards) as avg_passing_yards
                ')
                ->first();

            // Get team stats
            $teamStats = DB::table('nfl_team_stats')
                ->where('team_id', $teamId)
                ->orderBy('created_at', 'desc')
                ->take($gamesBack)
                ->get();

            if ($teamStats->isEmpty()) {
                return [
                    'adjusted_per_game' => [
                        'total_yards' => 0,
                        'rushing_yards' => 0,
                        'passing_yards' => 0,
                    ],
                    'strength_of_schedule' => [
                        'rating' => 0,
                        'difficulty' => 'insufficient_data',
                        'opponent_count' => 0
                    ]
                ];
            }

            $adjustedStats = [
                'total_yards' => 0,
                'rushing_yards' => 0,
                'passing_yards' => 0,
            ];

            // Calculate opponent-adjusted stats
            foreach ($teamStats as $stat) {
                // Get opponent stats
                $opponent = $this->getOpponentFromGameId($stat->game_id, $stat->team_abv);
                if ($opponent) {
                    $opponentAverages = $this->getOpponentAverages($opponent, $gamesBack);

                    // Calculate adjustment factors
                    $adjustedStats['total_yards'] += $stat->total_yards * ($leagueAverages->avg_total_yards / $opponentAverages['total_yards']);
                    $adjustedStats['rushing_yards'] += $stat->rushing_yards * ($leagueAverages->avg_rushing_yards / $opponentAverages['rushing_yards']);
                    $adjustedStats['passing_yards'] += $stat->passing_yards * ($leagueAverages->avg_passing_yards / $opponentAverages['passing_yards']);
                }
            }

            $gameCount = $teamStats->count();
            $strengthOfSchedule = $this->calculateStrengthOfSchedule($teamId, $gamesBack);

            return [
                'adjusted_per_game' => [
                    'total_yards' => round($adjustedStats['total_yards'] / $gameCount, 1),
                    'rushing_yards' => round($adjustedStats['rushing_yards'] / $gameCount, 1),
                    'passing_yards' => round($adjustedStats['passing_yards'] / $gameCount, 1),
                ],
                'strength_of_schedule' => $strengthOfSchedule
            ];
        });
    }

    /**
     * Get opponent team abbreviation from game ID
     *
     * @param string $gameId
     * @param string $teamAbv
     * @return string|null
     */
    private function getOpponentFromGameId(string $gameId, string $teamAbv): ?string
    {
        $game = DB::table('nfl_box_scores')
            ->where('game_id', $gameId)
            ->first();

        if (!$game) {
            return null;
        }

        return $game->home_team === $teamAbv ? $game->away_team : $game->home_team;
    }

    /**
     * Get opponent averages
     *
     * @param string $teamAbv
     * @param int $gamesBack
     * @return array
     */
    private function getOpponentAverages(string $teamAbv, int $gamesBack): array
    {
        $stats = DB::table('nfl_team_stats')
            ->where('team_abv', $teamAbv)
            ->orderBy('created_at', 'desc')
            ->take($gamesBack)
            ->get();

        if ($stats->isEmpty()) {
            return [
                'total_yards' => 350, // League average fallback
                'rushing_yards' => 120,
                'passing_yards' => 230,
            ];
        }

        return [
            'total_yards' => $stats->avg('total_yards') ?: 350,
            'rushing_yards' => $stats->avg('rushing_yards') ?: 120,
            'passing_yards' => $stats->avg('passing_yards') ?: 230,
        ];
    }

    /**
     * Calculate strength of schedule
     *
     * @param int $teamId
     * @param int $gamesBack
     * @return array
     */
    private function calculateStrengthOfSchedule(int $teamId, int $gamesBack): array
    {
        $schedules = DB::table('nfl_team_schedules')
            ->where(function ($query) use ($teamId) {
                $query->where('home_team_id', $teamId)
                    ->orWhere('away_team_id', $teamId);
            })
            ->orderBy('game_date', 'desc')
            ->take($gamesBack)
            ->get();

        $opponentIds = $schedules->map(function ($schedule) use ($teamId) {
            return $schedule->home_team_id === $teamId
                ? $schedule->away_team_id
                : $schedule->home_team_id;
        });

        $opponentStats = DB::table('nfl_team_stats')
            ->whereIn('team_id', $opponentIds)
            ->get()
            ->groupBy('team_id');

        $totalRating = 0;
        $count = 0;

        foreach ($opponentStats as $stats) {
            $avgYards = $stats->avg('total_yards');
            $totalRating += $this->getStrengthRating($avgYards);
            $count++;
        }

        $averageRating = $count > 0 ? $totalRating / $count : 0;

        return [
            'rating' => round($averageRating, 2),
            'difficulty' => $this->getScheduleDifficulty($averageRating),
            'opponent_count' => $count
        ];
    }

    /**
     * Get strength rating based on average yards
     *
     * @param float $avgYards
     * @return float
     */
    private function getStrengthRating(float $avgYards): float
    {
        if ($avgYards >= 400) return 1.0;
        if ($avgYards >= 350) return 0.8;
        if ($avgYards >= 300) return 0.6;
        if ($avgYards >= 250) return 0.4;
        return 0.2;
    }

    /**
     * Get schedule difficulty description
     *
     * @param float $rating
     * @return string
     */
    private function getScheduleDifficulty(float $rating): string
    {
        if ($rating >= 0.8) return 'very_difficult';
        if ($rating >= 0.6) return 'difficult';
        if ($rating >= 0.4) return 'moderate';
        if ($rating >= 0.2) return 'easy';
        return 'very_easy';
    }

}