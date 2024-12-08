<?php

namespace App\Repositories\Nfl;

use App\Models\Nfl\{NflBoxScore, NflPlayerStat, NflTeam, NflTeamSchedule, NflTeamStat};
use App\Repositories\Nfl\Interfaces\TeamStatsRepositoryInterface;
use App\Repositories\NflTeamScheduleRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;


class TeamStatsRepository implements TeamStatsRepositoryInterface

{
    private const SCORE_TYPES = [
        'half' => [
            'order_by' => 'avg_first_half_points',
            'headings' => [
                'Team', 'Location Type', 'Conference', 'Division',
                'First Half Avg', 'Second Half Avg', 'First Half Stronger %', 'Games Played'
            ]
        ],
        'score_margin' => [
            'order_by' => 'avg_total_points',
            'headings' => [
                'Team', 'Location Type', 'Conference', 'Division',
                'Avg Total Points', 'Games Played'
            ]
        ],
        'quarter_comeback' => [
            'order_by' => 'total_games',
            'headings' => [
                'Team', 'Location Type', 'Conference', 'Division',
                'Total Games', 'Comeback Wins', 'Blown Leads',
                'Avg 4th Qtr Points', 'Avg Comeback 4th Qtr Points'
            ]
        ],
        'scoring_streak' => [
            'order_by' => 'games_analyzed',
            'headings' => [
                'Team', 'Location Type', 'Conference', 'Division',
                'Games Analyzed', 'Games Scored Every Quarter',
                'Fourth Quarter Only Scores', 'Highest Quarter Score'
            ]
        ],
        'over_under_analysis' => [
            'order_by' => 'avg_total_points',
            'headings' => [
                'Team', 'Location Type', 'Conference', 'Division',
                'Avg Total Points', 'Avg First Half Points', 'Avg Fourth Quarter Points',
                'Highest Combined Score', 'Lowest Combined Score', 'Points Variance',
                'Pct Games Over 44.5', 'Pct First Half Over 21.5',
                'Correlated Overs', 'Projected Pace'
            ]
        ],
        'quarter' => [
            'order_by' => 'avg_first_half_points',
            'headings' => [
                'Team', 'Location Type', 'Conference', 'Division',
                'Q1 Avg', 'Q2 Avg', 'Q3 Avg', 'Q4 Avg',
                'First Half Avg', 'Second Half Avg', 'Games Played'
            ]
        ]
    ];
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
    public function getRecentGames(): Collection
    {
        $scheduleRepository = new NflTeamScheduleRepository();
        // Fetch the 3 most recent games for team ID 1234
        return $scheduleRepository->getRecentGames('$teamId', 3);
    }

    /**
     * Get average points statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public function getAveragePoints(string $teamFilter = null, string $locationFilter = null, string $conferenceFilter = null, string $divisionFilter = null): array
    {
        // Step 1: Get all distinct team names for dropdown filter purposes
        $teamsList = NflBoxScore::query()
            ->select('home_team')
            ->union(NflBoxScore::select('away_team'))
            ->distinct()
            ->pluck('home_team');

        // Step 2: Base query to get all regular season game IDs involving the filtered teams
        $gameIdsQuery = NflBoxScore::query()
            ->join('nfl_team_schedules as s', 'nfl_box_scores.game_id', '=', 's.game_id')
            ->where('s.season_type', 'Regular Season');

        // Step 3: Apply filters for team, location, conference, and division using reusable method
        $gameIdsQuery = $this->applyFilters($gameIdsQuery, $teamFilter, $locationFilter, $conferenceFilter, $divisionFilter);

        // Step 4: Get the unique game IDs
        $gameIds = $gameIdsQuery->pluck('nfl_box_scores.game_id')->unique()->values()->toArray();

        // Step 5: Calculate team scores using filtered game IDs
        $teamScores = $this->calculateTeamScores($gameIds, $locationFilter);

        return [
            'data' => $teamScores,
            'headings' => $this->getTableHeadings('average_points'),
            'metadata' => $this->getMetadata($teamScores),
            'teamsList' => $teamsList,
        ];
    }

    /**
     * Apply filters for team, location, conference, and division.
     */
    protected function applyFilters($query, $teamFilter, $locationFilter, $conferenceFilter, $divisionFilter)
    {
        // Apply team filter scope
        if ($teamFilter) {
            $query->where(function ($q) use ($teamFilter) {
                $q->where('nfl_box_scores.home_team', $teamFilter)
                    ->orWhere('nfl_box_scores.away_team', $teamFilter);
            });
        }

        // Apply location filter if provided
        if ($locationFilter && $teamFilter) {
            if ($locationFilter === 'home') {
                $query->where('nfl_box_scores.home_team', $teamFilter);
            } elseif ($locationFilter === 'away') {
                $query->where('nfl_box_scores.away_team', $teamFilter);
            }
        }

        // Apply conference and division filters
        if ($conferenceFilter || $divisionFilter) {
            $teamIds = NflTeam::query()
                ->when($conferenceFilter, fn($q) => $q->where('conference_abv', $conferenceFilter))
                ->when($divisionFilter, fn($q) => $q->where('division', $divisionFilter))
                ->pluck('team_abv');

            $query->where(function ($q) use ($teamIds) {
                $q->whereIn('nfl_box_scores.home_team', $teamIds)
                    ->orWhereIn('nfl_box_scores.away_team', $teamIds);
            });
        }

        return $query;
    }

    // Calculate Team Scores
    public function calculateTeamScores(
        array   $gameIds = [],
        ?string $teamAbv1 = null,
        ?string $teamAbv2 = null,
        ?int    $week = null,
        ?string $locationFilter = null
    ): Collection
    {
        $query = NflTeamSchedule::query();

        // Case 1: Two team abbreviations provided
        if ($teamAbv1 && $teamAbv2) {
            return $query
                ->where(function ($q) use ($teamAbv1, $teamAbv2) {
                    $q->where(function ($q1) use ($teamAbv1, $teamAbv2) {
                        $q1->where('home_team', $teamAbv1)
                            ->where('away_team', $teamAbv2);
                    })->orWhere(function ($q2) use ($teamAbv1, $teamAbv2) {
                        $q2->where('home_team', $teamAbv2)
                            ->where('away_team', $teamAbv1);
                    });
                })
                ->get([
                    'game_id',
                    'home_team',
                    'away_team',
                    'home_pts',
                    'away_pts',
                    'game_week'
                ]);
        }

        // Case 2: One team abbreviation and a specific week
        if ($teamAbv1 && $week) {
            return $query
                ->where(function ($q) use ($teamAbv1) {
                    $q->where('home_team', $teamAbv1)
                        ->orWhere('away_team', $teamAbv1);
                })
                ->where('game_week', $week)
                ->get([
                    'game_id',
                    'home_team',
                    'away_team',
                    'home_pts',
                    'away_pts',
                    'game_week'
                ]);
        }

        // Case 3: Scores for all games in a specific week
        if ($week) {
            return $query
                ->where('game_week', $week)
                ->get([
                    'game_id',
                    'home_team',
                    'away_team',
                    'home_pts',
                    'away_pts',
                    'game_week'
                ]);
        }

        // Default case: Use game IDs and location filter logic
        // Query for home team scores
        $homeScoresQuery = NflBoxScore::query()
            ->filterByGameIds($gameIds)
            ->joinHomeTeam()
            ->selectHomeTeamColumns();

        // Query for away team scores
        $awayScoresQuery = NflBoxScore::query()
            ->filterByGameIds($gameIds)
            ->joinAwayTeam()
            ->selectAwayTeamColumns();

        // Union the home and away queries
        $scoresQuery = $homeScoresQuery->union($awayScoresQuery);

        // Case 4: No location filter - sum scores for both home and away
        if (!$locationFilter) {
            return DB::query()
                ->fromSub($scoresQuery, 'team_scores')
                ->select([
                    'team_abv',
                    DB::raw("'combined' as location_type"),
                    'conference',
                    'division',
                    DB::raw('ROUND(AVG(CAST(Q1 AS UNSIGNED)), 1) as avg_q1_points'),
                    DB::raw('ROUND(AVG(CAST(Q2 AS UNSIGNED)), 1) as avg_q2_points'),
                    DB::raw('ROUND(AVG(CAST(Q3 AS UNSIGNED)), 1) as avg_q3_points'),
                    DB::raw('ROUND(AVG(CAST(Q4 AS UNSIGNED)), 1) as avg_q4_points'),
                    DB::raw('ROUND(AVG(CAST(Q1 AS UNSIGNED) + CAST(Q2 AS UNSIGNED)), 1) as avg_first_half_points'),
                    DB::raw('ROUND(AVG(CAST(Q3 AS UNSIGNED) + CAST(Q4 AS UNSIGNED)), 1) as avg_second_half_points'),
                    DB::raw('ROUND(AVG(CAST(totalPts AS UNSIGNED)), 1) as avg_total_points'),
                    DB::raw('COUNT(*) as games_played')
                ])
                ->groupBy('team_abv', 'conference', 'division')
                ->orderBy('team_abv')
                ->get();
        }

        // Case 5: Location filter ('home' or 'away')
        return DB::query()
            ->fromSub($scoresQuery, 'team_scores')
            ->select([
                'team_abv',
                'location_type',
                'conference',
                'division',
                DB::raw('ROUND(AVG(CAST(Q1 AS UNSIGNED)), 1) as avg_q1_points'),
                DB::raw('ROUND(AVG(CAST(Q2 AS UNSIGNED)), 1) as avg_q2_points'),
                DB::raw('ROUND(AVG(CAST(Q3 AS UNSIGNED)), 1) as avg_q3_points'),
                DB::raw('ROUND(AVG(CAST(Q4 AS UNSIGNED)), 1) as avg_q4_points'),
                DB::raw('ROUND(AVG(CAST(Q1 AS UNSIGNED) + CAST(Q2 AS UNSIGNED)), 1) as avg_first_half_points'),
                DB::raw('ROUND(AVG(CAST(Q3 AS UNSIGNED) + CAST(Q4 AS UNSIGNED)), 1) as avg_second_half_points'),
                DB::raw('ROUND(AVG(CAST(totalPts AS UNSIGNED)), 1) as avg_total_points'),
                DB::raw('COUNT(*) as games_played')
            ])
            ->groupBy('team_abv', 'location_type', 'conference', 'division')
            ->orderBy('team_abv')
            ->orderBy('location_type')
            ->get();
    }

    public function getTableHeadings(string $queryType): array
    {
        return match ($queryType) {
            'average_points' => ['Team', 'Location Type', 'Conf', 'Div', 'Q1', 'Q2', 'Q3', 'Q4', 'First Half', 'Second Half', 'Total Points', 'Games Played'],
            'quarter_scoring' => ['Team', 'Q1 Avg', 'Q2 Avg', 'Q3 Avg', 'Q4 Avg', 'Games', 'Q1 Scoring %', 'Q2 Scoring %', 'Q3 Scoring %', 'Q4 Scoring %', 'Best Qtr', 'Worst Qtr'],
            'score_margins' => ['Team', 'Location', 'Games', 'Avg Margin', 'Largest Win', 'Largest Loss', 'Wins', 'Losses', 'Avg Win Margin', 'Avg Loss Margin', 'One Score Games', 'Win %'],
            default => [],
        };
    }

    public function getMetaData(): array
    {
        return [
            'team' => 'Team',
            'location' => 'Location Type',
            'conference' => 'Conf',
            'division' => 'Div',
            'q1' => 'Q1',
            'q2' => 'Q2',
            'q3' => 'Q3',
            'q4' => 'Q4',
            'first_half' => 'First Half',
            'second_half' => 'Second Half',
            'total_points' => 'Total Points',
            'games_played' => 'Games Played',
        ];

    }

    public function getHalfScoring(?string $teamFilter = null, ?string $locationFilter = null, ?string $conferenceFilter = null, ?string $divisionFilter = null, ?string $opponentFilter = null): array
    {
        $teamsList = $this->getTeamsList();
        $scoringData = $this->getScoringData('half', $teamFilter, $locationFilter, $conferenceFilter, $divisionFilter, $opponentFilter);
        $scoringData['teamsList'] = $teamsList;
        return $scoringData;
    }

    protected function getTeamsList(): Collection
    {
        return NflBoxScore::query()
            ->select('home_team')
            ->union(NflBoxScore::select('away_team'))
            ->distinct()
            ->pluck('home_team');
    }

    protected function getScoringData(
        string  $type,
        ?string $teamFilter = null,
        ?string $locationFilter = null,
        ?string $conferenceFilter = null,
        ?string $divisionFilter = null
    ): array
    {
        $sql = $this->buildScoringQuery($type, $teamFilter, $locationFilter, $conferenceFilter, $divisionFilter);
        $bindings = $this->prepareBindings($teamFilter, $conferenceFilter, $divisionFilter, $locationFilter);

        return [
            'data' => DB::select($sql, $bindings),
            'headings' => $this->getHeadings($type)
        ];
    }

    private function buildScoringQuery(
        string  $type,
        ?string $teamFilter,
        ?string $locationFilter,
        ?string $conferenceFilter,
        ?string $divisionFilter
    ): string
    {
        $baseTeamScores = $this->buildBaseTeamScoresQuery();
        $selectColumns = $this->getSelectColumnsForType($type);
        $whereClause = $this->buildWhereClause($conferenceFilter, $divisionFilter, $locationFilter);
        $orderBy = self::SCORE_TYPES[$type]['order_by'] ?? 'games_played';

        return "
            WITH team_scores AS ({$baseTeamScores})
            SELECT
                team_abv,
                location_type,
                conference,
                division,
                {$selectColumns}
            FROM team_scores
            {$whereClause}
            GROUP BY team_abv, location_type, conference, division
            ORDER BY {$orderBy} DESC
        ";
    }

    private function buildBaseTeamScoresQuery(): string
    {
        $homeQuery = $this->buildLocationQuery('home');
        $awayQuery = $this->buildLocationQuery('away');

        return "
            SELECT
                scores.team_abv,
                scores.location_type,
                scores.conference,
                scores.division,
                scores.Q1,
                scores.Q2,
                scores.Q3,
                scores.Q4
            FROM (
                {$homeQuery}
                UNION ALL
                {$awayQuery}
            ) scores
        ";
    }

    private function buildLocationQuery(string $location): string
    {
        return "
            SELECT
                b.game_id,
                b.{$location}_team AS team_abv,
                '{$location}' AS location_type,
                home_team.conference_abv AS conference,
                home_team.division AS division,
                JSON_UNQUOTE(JSON_EXTRACT(b.{$location}_line_score, '$.Q1')) AS Q1,
                JSON_UNQUOTE(JSON_EXTRACT(b.{$location}_line_score, '$.Q2')) AS Q2,
                JSON_UNQUOTE(JSON_EXTRACT(b.{$location}_line_score, '$.Q3')) AS Q3,
                JSON_UNQUOTE(JSON_EXTRACT(b.{$location}_line_score, '$.Q4')) AS Q4
            FROM nfl_box_scores b
            INNER JOIN nfl_team_schedules s ON b.game_id = s.game_id
            INNER JOIN nfl_teams AS home_team ON b.home_team = home_team.team_abv
            WHERE s.season_type = 'Regular Season'
            AND (b.{$location}_team = ? OR ? IS NULL)
        ";
    }

    private function getSelectColumnsForType(string $type): string
    {
        return match ($type) {
            'half' => '
                ROUND(AVG(CAST(Q1 AS UNSIGNED) + CAST(Q2 AS UNSIGNED)), 1) AS avg_first_half_points,
                ROUND(AVG(CAST(Q3 AS UNSIGNED) + CAST(Q4 AS UNSIGNED)), 1) AS avg_second_half_points,
                ROUND(AVG(
                    CASE 
                        WHEN CAST(Q1 AS UNSIGNED) + CAST(Q2 AS UNSIGNED) > CAST(Q3 AS UNSIGNED) + CAST(Q4 AS UNSIGNED) THEN 1
                        ELSE 0
                    END
                ) * 100, 1) AS first_half_stronger_percentage,
                COUNT(*) as games_played',

            'score_margin' => '
                ROUND(AVG(CAST(Q1 AS UNSIGNED) + CAST(Q2 AS UNSIGNED) + CAST(Q3 AS UNSIGNED) + CAST(Q4 AS UNSIGNED)), 1) AS avg_total_points,
                COUNT(*) as games_played',

            'quarter_comeback' => '
                COUNT(*) as total_games,
                SUM(CASE 
                    WHEN CAST(Q1 AS UNSIGNED) + CAST(Q2 AS UNSIGNED) + CAST(Q3 AS UNSIGNED) < CAST(Q4 AS UNSIGNED) THEN 1
                    ELSE 0
                END) as comeback_wins,
                SUM(CASE 
                    WHEN CAST(Q1 AS UNSIGNED) + CAST(Q2 AS UNSIGNED) + CAST(Q3 AS UNSIGNED) > CAST(Q4 AS UNSIGNED) THEN 1
                    ELSE 0
                END) as blown_leads,
                ROUND(AVG(CAST(Q4 AS UNSIGNED)), 1) as avg_fourth_quarter_points,
                ROUND(AVG(CASE 
                    WHEN CAST(Q1 AS UNSIGNED) + CAST(Q2 AS UNSIGNED) + CAST(Q3 AS UNSIGNED) < CAST(Q4 AS UNSIGNED) THEN CAST(Q4 AS UNSIGNED)
                    ELSE NULL
                END), 1) as avg_comeback_q4_points',

            'scoring_streak' => '
                COUNT(*) as games_analyzed,
                SUM(CASE 
                    WHEN CAST(Q1 AS UNSIGNED) > 0 AND CAST(Q2 AS UNSIGNED) > 0 
                         AND CAST(Q3 AS UNSIGNED) > 0 AND CAST(Q4 AS UNSIGNED) > 0 THEN 1
                    ELSE 0
                END) as games_scored_every_quarter,
                SUM(CASE 
                    WHEN CAST(Q1 AS UNSIGNED) = 0 AND CAST(Q2 AS UNSIGNED) = 0 
                         AND CAST(Q3 AS UNSIGNED) = 0 AND CAST(Q4 AS UNSIGNED) > 0 THEN 1
                    ELSE 0
                END) as fourth_quarter_only_scores,
                MAX(GREATEST(
                    CAST(Q1 AS UNSIGNED), CAST(Q2 AS UNSIGNED),
                    CAST(Q3 AS UNSIGNED), CAST(Q4 AS UNSIGNED)
                )) as highest_scoring_quarter',

            'over_under_analysis' => $this->getOverUnderAnalysisColumns(),

            default => '
                ROUND(AVG(CAST(Q1 AS UNSIGNED)), 1) AS avg_q1_points,
                ROUND(AVG(CAST(Q2 AS UNSIGNED)), 1) AS avg_q2_points,
                ROUND(AVG(CAST(Q3 AS UNSIGNED)), 1) AS avg_q3_points,
                ROUND(AVG(CAST(Q4 AS UNSIGNED)), 1) AS avg_q4_points,
                ROUND(AVG(CAST(Q1 AS UNSIGNED) + CAST(Q2 AS UNSIGNED)), 1) AS avg_first_half_points,
                ROUND(AVG(CAST(Q3 AS UNSIGNED) + CAST(Q4 AS UNSIGNED)), 1) AS avg_second_half_points,
                COUNT(*) as games_played'
        };
    }

    private function getOverUnderAnalysisColumns(): string
    {
        return '
            ROUND(AVG(CAST(Q1 AS UNSIGNED) + CAST(Q2 AS UNSIGNED) + CAST(Q3 AS UNSIGNED) + CAST(Q4 AS UNSIGNED)), 1) AS avg_total_points,
            ROUND(AVG(CAST(Q1 AS UNSIGNED) + CAST(Q2 AS UNSIGNED)), 1) AS avg_first_half_points,
            ROUND(AVG(CAST(Q4 AS UNSIGNED)), 1) AS avg_fourth_quarter_points,
            MAX(CAST(Q1 AS UNSIGNED) + CAST(Q2 AS UNSIGNED) + CAST(Q3 AS UNSIGNED) + CAST(Q4 AS UNSIGNED)) as highest_combined_score,
            MIN(CAST(Q1 AS UNSIGNED) + CAST(Q2 AS UNSIGNED) + CAST(Q3 AS UNSIGNED) + CAST(Q4 AS UNSIGNED)) as lowest_combined_score,
            ROUND(STDDEV(CAST(Q1 AS UNSIGNED) + CAST(Q2 AS UNSIGNED) + CAST(Q3 AS UNSIGNED) + CAST(Q4 AS UNSIGNED)), 1) as points_variance,
            ROUND(SUM(CASE WHEN CAST(Q1 AS UNSIGNED) + CAST(Q2 AS UNSIGNED) + CAST(Q3 AS UNSIGNED) + CAST(Q4 AS UNSIGNED) > 44.5 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as pct_games_over_44_5,
            ROUND(SUM(CASE WHEN CAST(Q1 AS UNSIGNED) + CAST(Q2 AS UNSIGNED) > 21.5 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as pct_first_half_over_21_5,
            COUNT(CASE WHEN CAST(Q1 AS UNSIGNED) + CAST(Q2 AS UNSIGNED) + CAST(Q3 AS UNSIGNED) + CAST(Q4 AS UNSIGNED) > 44.5 AND CAST(Q1 AS UNSIGNED) + CAST(Q2 AS UNSIGNED) > 21.5 THEN 1 END) as correlated_overs,
            ROUND(AVG(CAST(Q4 AS UNSIGNED)) * 4, 1) as projected_pace';
    }

    private function buildWhereClause(
        ?string $conferenceFilter,
        ?string $divisionFilter,
        ?string $locationFilter
    ): string
    {
        $conditions = [];

        if ($conferenceFilter) {
            $conditions[] = 'team_scores.conference = ?';
        }
        if ($divisionFilter) {
            $conditions[] = 'team_scores.division = ?';
        }
        if ($locationFilter) {
            $conditions[] = 'team_scores.location_type = ?';
        }

        return $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
    }

    private function prepareBindings(
        ?string $teamFilter,
        ?string $conferenceFilter,
        ?string $divisionFilter,
        ?string $locationFilter
    ): array
    {
        return array_merge(
            array_fill(0, 4, $teamFilter), // 2 for home query, 2 for away query
            array_filter([$conferenceFilter, $divisionFilter, $locationFilter])
        );
    }

    private function getHeadings(string $type): array
    {
        return self::SCORE_TYPES[$type]['headings'] ?? self::SCORE_TYPES['quarter']['headings'];
    }

    public function getQuarterScoring(?string $teamFilter = null, ?string $locationFilter = null, ?string $conferenceFilter = null, ?string $divisionFilter = null, ?string $opponentFilter = null): array
    {
        $teamsList = $this->getTeamsList();
        $scoringData = $this->getScoringData('quarter', $teamFilter, $locationFilter, $conferenceFilter, $divisionFilter, $opponentFilter);
        $scoringData['teamsList'] = $teamsList;
        return $scoringData;
    }

    /**
     * Get situational performance
     *
     * @param int $teamId
     * @param int $gamesBack
     * @return array
     */
    public function getSituationalPerformance(
        ?string $teamFilter = null,
        ?string $locationFilter = null,
        ?string $againstConference = null
    ): array
    {
        // Get all team abbreviations if no filter is provided
        if (!$teamFilter) {
            $teams = NflTeamSchedule::select('home_team')
                ->distinct()
                ->pluck('home_team')
                ->filter();
        } else {
            $teams = collect([$teamFilter]);
        }

        $allTeamStats = [];

        foreach ($teams as $team) {
            // Get all games for the team
            $gamesQuery = NflTeamSchedule::where(function ($q) use ($team) {
                $q->where('home_team', $team)->orWhere('away_team', $team);
            });

            // Apply location filter
            if ($locationFilter === 'home') {
                $gamesQuery->where('home_team', $team);
            } elseif ($locationFilter === 'away') {
                $gamesQuery->where('away_team', $team);
            }

            // Apply conference filter for opponents
            if ($againstConference) {
                $gamesQuery->whereHas('opponent', function ($q) use ($againstConference) {
                    $q->where('conference', $againstConference);
                });
            }

            $games = $gamesQuery->orderBy('game_date', 'desc')->get();

            // Get all stats for these games
            $gameIds = $games->pluck('game_id');
            $stats = NflTeamStat::whereIn('game_id', $gameIds)
                ->where('team_abv', $team)
                ->get();

            // Separate home and away games
            $homeGames = $games->where('home_team', $team)->pluck('game_id');
            $awayGames = $games->where('away_team', $team)->pluck('game_id');

            $homeStats = $stats->whereIn('game_id', $homeGames);
            $awayStats = $stats->whereIn('game_id', $awayGames);

            $allTeamStats[] = [
                'Team' => $team,
                'Home Games' => $homeStats->count(),
                'Home Avg Yards' => $this->calculateSituationalMetrics($homeStats->toArray())['average_yards'],
                'Home Rating' => $this->calculateSituationalMetrics($homeStats->toArray())['performance_rating'],
                'Away Games' => $awayStats->count(),
                'Away Avg Yards' => $this->calculateSituationalMetrics($awayStats->toArray())['average_yards'],
                'Away Rating' => $this->calculateSituationalMetrics($awayStats->toArray())['performance_rating']
            ];
        }

        return [
            'data' => $allTeamStats,
            'headings' => [
                'Team',
                'Home Games',
                'Home Avg Yards',
                'Home Rating',
                'Away Games',
                'Away Avg Yards',
                'Away Rating'
            ]
        ];
    }

    protected function calculateSituationalMetrics(array $stats): array
    {
        if (empty($stats)) {
            return [
                'average_yards' => 0,
                'yards_consistency' => 0,
                'performance_rating' => 'insufficient_data'
            ];
        }

        $yardValues = collect($stats)->pluck('total_yards')->all();
        $average = collect($yardValues)->average();
        $consistency = $this->calculateConsistencyScore($yardValues);

        return [
            'average_yards' => round($average, 1),
            'yards_consistency' => round($consistency, 2),
            'performance_rating' => $this->getRatingFromStats($average, $consistency)
        ];
    }

    protected function calculateConsistencyScore(array $values): float
    {
        $mean = collect($values)->average();
        $variance = collect($values)
            ->map(fn($value) => pow($value - $mean, 2))
            ->average();

        return sqrt($variance) / $mean * 100;
    }

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
     * Get score margins statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public function getScoreMargins(?string $teamFilter = null, ?string $locationFilter = null, ?string $conferenceFilter = null, ?string $divisionFilter = null): array
    {
        $teamsList = $this->getTeamsList();
        return array_merge(
            $this->getScoringData('score_margin', $teamFilter, $locationFilter, $conferenceFilter, $divisionFilter),
            ['teamsList' => $teamsList]
        );
    }
    /**
     * Get half scoring statistics
     *
     * @param string|null $teamFilter
     * @return array
     */

    /**
     * Get quarter comebacks statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public function getQuarterComebacks(?string $teamFilter = null, ?string $locationFilter = null, ?string $conferenceFilter = null, ?string $divisionFilter = null): array
    {
        $teamsList = $this->getTeamsList();
        return array_merge(
            $this->getScoringData('quarter_comeback', $teamFilter, $locationFilter, $conferenceFilter, $divisionFilter),
            ['teamsList' => $teamsList]
        );
    }

    /**
     * Get scoring streaks statistics
     *
     * @param string|null $teamFilter
     * @return array
     */

    public function getScoringStreaks(?string $teamFilter = null, ?string $locationFilter = null, ?string $conferenceFilter = null, ?string $divisionFilter = null): array
    {
        $teamsList = $this->getTeamsList();
        return array_merge(
            $this->getScoringData('scoring_streak', $teamFilter, $locationFilter, $conferenceFilter, $divisionFilter),
            ['teamsList' => $teamsList]
        );

    }

    public function getBestReceivers(?string $teamFilter = null, ?int $week = null, ?int $startWeek = null, ?int $endWeek = null): array
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
            // If a specific week is provided, filter by that week.
            ->when($week !== null, function ($q) use ($week) {
                $q->where('nfl_team_schedules.game_week', $week);
            })
            // If a start and end week are provided (and no single week is given), filter by the range.
            ->when($week === null && $startWeek !== null && $endWeek !== null, function ($q) use ($startWeek, $endWeek) {
                $q->whereBetween('nfl_team_schedules.game_week', [$startWeek, $endWeek]);
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
    function getBestRushers(?string $teamFilter = null, ?int $week = null, ?int $startWeek = null, ?int $endWeek = null): array
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
            // If a specific week is provided, filter by that week.
            ->when($week !== null, function ($q) use ($week) {
                $q->where('nfl_team_schedules.game_week', $week);
            })
            // If a start and end week are provided (and no single week is given), filter by the range.
            ->when($week === null && $startWeek !== null && $endWeek !== null, function ($q) use ($startWeek, $endWeek) {
                $q->whereBetween('nfl_team_schedules.game_week', [$startWeek, $endWeek]);
            })
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
    function getBestTacklers(?string $teamFilter = null, ?int $week = null, ?int $startWeek = null, ?int $endWeek = null): array
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
            // If a specific week is provided, filter by that week.
            ->when($week !== null, function ($q) use ($week) {
                $q->where('nfl_team_schedules.game_week', $week);
            })
            // If a start and end week are provided (and no single week is given), filter by the range.
            ->when($week === null && $startWeek !== null && $endWeek !== null, function ($q) use ($startWeek, $endWeek) {
                $q->whereBetween('nfl_team_schedules.game_week', [$startWeek, $endWeek]);
            })
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
    public function getBigPlaymakers(?string $teamFilter = null): array
    {
        $query = DB::table('nfl_player_stats as ps')
            ->where(function ($query) {
                $query->whereNotNull('ps.receiving')->orWhereNotNull('ps.rushing');
            })
            ->when($teamFilter, function ($query, $teamFilter) {
                return $query->where('ps.team_abv', $teamFilter);
            })
            ->select(
                'ps.long_name',
                'ps.team_abv',
                DB::raw("CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(ps.receiving, '$.longRec')), '0') AS SIGNED) as longest_reception"),
                DB::raw("CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(ps.rushing, '$.longRush')), '0') AS SIGNED) as longest_rush"),
                DB::raw("CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(ps.receiving, '$.recYds')), '0') AS SIGNED) as receiving_yards"),
                DB::raw("CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(ps.receiving, '$.receptions')), '0') AS SIGNED) as receptions"),
                DB::raw("CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(ps.rushing, '$.rushYds')), '0') AS SIGNED) as rushing_yards"),
                DB::raw("CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(ps.rushing, '$.carries')), '0') AS SIGNED) as carries")
            );

        $playerBigPlays = $query->get();

        $data = collect($playerBigPlays)->groupBy(fn($item) => $item->long_name . '|' . $item->team_abv)->map(function ($group) {
            $firstItem = $group->first();
            return [
                'long_name' => $firstItem->long_name,
                'team_abv' => $firstItem->team_abv,
                'games_played' => $group->count(),
                'longest_reception' => $group->max('longest_reception'),
                'longest_rush' => $group->max('longest_rush'),
                'total_receiving_yards' => $group->sum('receiving_yards'),
                'total_rushing_yards' => $group->sum('rushing_yards'),
                'total_receptions' => $group->sum('receptions'),
                'total_carries' => $group->sum('carries'),
                'avg_yards_per_game' => round(($group->sum('receiving_yards') + $group->sum('rushing_yards')) / max(1, $group->count()), 1),
                'games_with_20plus_plays' => $group->filter(function ($item) {
                    return $item->longest_reception >= 20 || $item->longest_rush >= 20;
                })->count(),
                'total_yards' => $group->sum('receiving_yards') + $group->sum('rushing_yards')
            ];
        })->filter(function ($player) {
            return $player['longest_reception'] >= 20 || $player['longest_rush'] >= 20 || $player['total_yards'] > 0;
        })->sortByDesc('games_with_20plus_plays')->sortByDesc('total_yards')->take(20)->values()->toArray();

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
        $query = DB::table('nfl_player_stats as ps')
            ->select(
                'ps.long_name',
                'ps.team_abv',
                DB::raw('COUNT(*) as games_played'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.totalTackles")) AS UNSIGNED)) as total_tackles'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.soloTackles")) AS UNSIGNED)) as solo_tackles'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.sacks")) AS UNSIGNED)) as sacks'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.defensiveInterceptions")) AS UNSIGNED)) as interceptions'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.passDeflections")) AS UNSIGNED)) as pass_deflections'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.forcedFumbles")) AS UNSIGNED)) as forced_fumbles'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.tfl")) AS UNSIGNED)) as tackles_for_loss'),
                DB::raw('ROUND(AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.totalTackles")) AS UNSIGNED)), 1) as avg_tackles_per_game'),
                DB::raw('SUM(
                CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.sacks")) AS UNSIGNED) +
                CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.defensiveInterceptions")) AS UNSIGNED) +
                CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.forcedFumbles")) AS UNSIGNED)
            ) as impact_plays')
            )
            ->whereNotNull('defense')
            ->when($teamFilter, function ($query, $teamFilter) {
                return $query->where('ps.team_abv', $teamFilter);
            })
            ->groupBy('ps.long_name', 'ps.team_abv')
            ->having('total_tackles', '>', 0)
            ->orderByDesc('impact_plays')
            ->orderByDesc('total_tackles')
            ->limit(20)
            ->get();

        return [
            'data' => $query,
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
        $query = DB::table('nfl_player_stats as ps')
            ->select(
                'ps.long_name',
                'ps.team_abv',
                DB::raw('CAST(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.recYds")) AS DECIMAL(12,2))) AS DECIMAL(12,2)) as total_receiving_yards'),
                DB::raw('CAST(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.rushYds")) AS DECIMAL(12,2))) AS DECIMAL(12,2)) as total_rushing_yards'),
                DB::raw('CAST(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.receptions")) AS DECIMAL(12,2))) AS DECIMAL(12,2)) as total_receptions'),
                DB::raw('CAST(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.carries")) AS DECIMAL(12,2))) AS DECIMAL(12,2)) as total_carries'),
                DB::raw('CAST(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.recTD")) AS DECIMAL(12,2))) AS DECIMAL(12,2)) as receiving_touchdowns'),
                DB::raw('CAST(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.rushTD")) AS DECIMAL(12,2))) AS DECIMAL(12,2)) as rushing_touchdowns'),
                DB::raw('ROUND(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.recYds")) AS DECIMAL(12,2))) / NULLIF(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.receptions")) AS DECIMAL(12,2))), 0), 1) as yards_per_reception'),
                DB::raw('ROUND(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.rushYds")) AS DECIMAL(12,2))) / NULLIF(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.carries")) AS DECIMAL(12,2))), 0), 1) as yards_per_carry'),
                DB::raw('ROUND((SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.recYds")) AS DECIMAL(12,2))) + SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.rushYds")) AS DECIMAL(12,2)))) / COUNT(*), 1) as total_yards_per_game'),
                DB::raw('COUNT(*) as games_played')
            )
            ->whereNotNull('receiving')
            ->whereNotNull('rushing')
            ->when($teamFilter, function ($query, $teamFilter) {
                return $query->where('ps.team_abv', $teamFilter);
            })
            ->groupBy('ps.long_name', 'ps.team_abv')
            ->havingRaw('total_receiving_yards > 0 AND total_rushing_yards > 0')
            ->orderByDesc('total_yards_per_game')
            ->limit(20)
            ->get();

        return [
            'data' => $query,
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
        $startDate = $activeWeeks->first()['start'];
        $endDate = $activeWeeks->last()['end'];

        $query = DB::table('nfl_player_stats as ps')
            ->join('nfl_box_scores as b', 'ps.game_id', '=', 'b.game_id')
            ->join('nfl_team_schedules as s', 'b.game_id', '=', 's.game_id')
            ->where(function ($query) {
                $query->whereNotNull('receiving')->orWhereNotNull('rushing');
            })
            ->where('s.season_type', 'Regular Season')
            ->whereBetween('s.game_date', [$startDate, $endDate])
            ->when($teamFilter, function ($query, $teamFilter) {
                return $query->where('ps.team_abv', $teamFilter);
            })
            ->select(
                'ps.long_name',
                'ps.team_abv',
                DB::raw('COUNT(DISTINCT ps.game_id) as games_played'),
                DB::raw('SUM(CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$\.recYds")), "0") AS SIGNED) + CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$\.rushYds")), "0") AS SIGNED)) as total_yards'),
                DB::raw('SUM(CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$\.receptions")), "0") AS SIGNED) + CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$\.carries")), "0") AS SIGNED)) as total_touches'),
                DB::raw('SUM(CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$\.recTD")), "0") AS SIGNED) + CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$\.rushTD")), "0") AS SIGNED)) as total_tds'),
                DB::raw('ROUND(AVG(CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$\.recYds")), "0") AS SIGNED) + CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$\.rushYds")), "0") AS SIGNED)), 1) as avg_yards_per_game'),
                DB::raw('ROUND(AVG(CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$\.receptions")), "0") AS SIGNED) + CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$\.carries")), "0") AS SIGNED)), 1) as avg_touches_per_game'),
                DB::raw('ROUND(AVG(CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$\.recTD")), "0") AS SIGNED) + CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$\.rushTD")), "0") AS SIGNED)), 1) as avg_tds_per_game'),
                DB::raw('MIN(CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$\.recYds")), "0") AS SIGNED) + CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$\.rushYds")), "0") AS SIGNED)) as min_yards'),
                DB::raw('MAX(CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$\.recYds")), "0") AS SIGNED) + CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$\.rushYds")), "0") AS SIGNED)) as max_yards'),
                DB::raw('SUM(CASE WHEN (CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$\.recYds")), "0") AS SIGNED) + CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$\.rushYds")), "0") AS SIGNED)) >= 50 THEN 1 ELSE 0 END) / COUNT(DISTINCT ps.game_id) * 100 as fifty_plus_yard_games_pct'),
                DB::raw('ROUND(STDDEV_SAMP(CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$\.recYds")), "0") AS SIGNED) + CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$\.rushYds")), "0") AS SIGNED)), 1) as yards_stddev'),
                DB::raw('ROUND(STDDEV_SAMP(CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$\.receptions")), "0") AS SIGNED) + CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$\.carries")), "0") AS SIGNED)), 1) as touches_stddev'),
                DB::raw('ROUND(STDDEV_SAMP(CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$\.recYds")), "0") AS SIGNED) + CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$\.rushYds")), "0") AS SIGNED)) / AVG(CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$\.recYds")), "0") AS SIGNED) + CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$\.rushYds")), "0") AS SIGNED)) * 100, 1) as yards_cv_pct'),
                DB::raw('SUM(CASE WHEN (CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$\.recYds")), "0") AS SIGNED) + CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$\.rushYds")), "0") AS SIGNED)) >= 10 THEN 1 ELSE 0 END) / COUNT(DISTINCT ps.game_id) * 100 as above_floor_pct'),
                DB::raw('ROUND((1 - (STDDEV_SAMP(CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$\.recYds")), "0") AS SIGNED) + CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$\.rushYds")), "0") AS SIGNED)) / AVG(CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$\.recYds")), "0") AS SIGNED) + CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$\.rushYds")), "0") AS SIGNED)))) * 100, 1) as consistency_rating'),
                DB::raw('ROUND((AVG(CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$\.recYds")), "0") AS SIGNED) + CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$\.rushYds")), "0") AS SIGNED)) / MAX(CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$\.recYds")), "0") AS SIGNED) + CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$\.rushYds")), "0") AS SIGNED))) * 100, 1) as reliability_score')
            )
            ->limit(20)
            ->groupBy('ps.long_name', 'ps.team_abv');

        $data = $query->get();

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
                    WHERE game_number <= 10
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


    public function getOverUnderAnalysis(?string $teamFilter = null, ?string $locationFilter = null, ?string $conferenceFilter = null, ?string $divisionFilter = null): array
    {
        return array_merge(
            (array)$teamsList = $this->getTeamsList(),

            $this->getScoringData('over_under_analysis', $teamFilter, $locationFilter, $conferenceFilter, $divisionFilter),
            ['teamsList' => $teamsList]
        );

    }

    /**
     * Get team vs division statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public function getTeamVsConference(?string $teamFilter = null, ?string $locationFilter = null, ?string $conferenceFilter = null, ?string $divisionFilter = null): array
    {

        return NflTeam::getTeamVsConference($teamFilter, $locationFilter, $conferenceFilter, $divisionFilter);
    }


    /**
     * Get player vs conference statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public function getPlayerVsConference(
        ?string $teamFilter = null,
        ?string $playerFilter = null,
        ?string $conferenceFilter = null
    ): array
    {
        return NflPlayerStat::getPlayerVsConference($teamFilter, $playerFilter, $conferenceFilter);
    }


    /**
     * Get player vs division statistics
     *
     * @param string|null $teamFilter
     * @return array
     */
    public function getPlayerVsDivision(?string $teamFilter = null): array
    {
        $sql = "
        WITH division_games AS (
            SELECT 
                ps.long_name,
                ps.team_abv,
                ps.game_id,
                JSON_UNQUOTE(JSON_EXTRACT(ps.receiving, '$.recYds')) AS receiving_yards,
                JSON_UNQUOTE(JSON_EXTRACT(ps.rushing, '$.rushYds')) AS rushing_yards,
                JSON_UNQUOTE(JSON_EXTRACT(ps.receiving, '$.recTD')) AS receiving_tds,
                JSON_UNQUOTE(JSON_EXTRACT(ps.rushing, '$.rushTD')) AS rushing_tds,
                JSON_UNQUOTE(JSON_EXTRACT(ps.defense, '$.totalTackles')) AS total_tackles,
                JSON_UNQUOTE(JSON_EXTRACT(ps.defense, '$.sacks')) AS sacks,
                JSON_UNQUOTE(JSON_EXTRACT(ps.defense, '$.defensiveInterceptions')) AS interceptions,
                t1.conference_abv as team_conference_abv,
                t1.conference as conference_name,
                t1.division,
                b.home_team,
                b.away_team
            FROM nfl_player_stats ps
            JOIN nfl_box_scores b ON ps.game_id = b.game_id
            JOIN nfl_team_schedules s ON b.game_id = s.game_id
            JOIN nfl_teams t1 ON ps.team_abv = t1.team_abv
            WHERE s.season_type = 'Regular Season'
            AND (ps.team_abv = ? OR ? IS NULL)
        )
        SELECT 
            dg.long_name as player,
            dg.team_abv as team,
            dg.team_conference_abv as conference_abv,
            dg.conference_name,
            dg.division,
            t2.division as opponent_division,
            COUNT(DISTINCT dg.game_id) as games_played,
            ROUND(AVG(CAST(dg.receiving_yards AS UNSIGNED)), 1) as avg_receiving_yards,
            ROUND(AVG(CAST(dg.rushing_yards AS UNSIGNED)), 1) as avg_rushing_yards,
            SUM(CAST(dg.receiving_tds AS UNSIGNED)) as receiving_tds,
            SUM(CAST(dg.rushing_tds AS UNSIGNED)) as rushing_tds,
            ROUND(AVG(CAST(dg.total_tackles AS UNSIGNED)), 1) as avg_tackles,
            SUM(CAST(dg.sacks AS UNSIGNED)) as total_sacks,
            SUM(CAST(dg.interceptions AS UNSIGNED)) as total_ints,
            ROUND(STDDEV(CAST(dg.receiving_yards AS UNSIGNED)), 1) as receiving_yards_stddev,
            ROUND(STDDEV(CAST(dg.rushing_yards AS UNSIGNED)), 1) as rushing_yards_stddev
        FROM division_games dg
        JOIN nfl_teams t2 ON (
            CASE 
                WHEN dg.team_abv = dg.home_team THEN dg.away_team 
                ELSE dg.home_team 
            END = t2.team_abv
        )
        WHERE (dg.receiving_yards IS NOT NULL OR dg.rushing_yards IS NOT NULL OR dg.total_tackles IS NOT NULL)
        GROUP BY dg.long_name, dg.team_abv, dg.team_conference_abv, dg.conference_name, dg.division, t2.division
        ORDER BY dg.long_name, t2.division;
    ";

        $data = DB::select($sql, [$teamFilter, $teamFilter]);

        return [
            'data' => $data,
            'headings' => [
                'Player',
                'Team',
                'Conf',
                'Conference',
                'Division',
                'Opponent Division',
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
    public function getTeamMatchupEdge(
        ?string $teamFilter = null,
        ?string $teamAbv1 = null,
        ?string $teamAbv2 = null,
        ?int    $week = null,
        ?string $locationFilter = null
    ): array
    {
        // First, get opponent averages
        $opponentStats = DB::table('nfl_team_stats as ts')
            ->join('nfl_box_scores as b', 'ts.game_id', '=', 'b.game_id')
            ->join('nfl_team_schedules as s', 'b.game_id', '=', 's.game_id')
            ->select([
                'ts.team_abv',
                DB::raw('AVG(ts.total_yards) as avg_total_yards'),
                DB::raw('AVG(CASE 
                WHEN b.home_team = ts.team_abv THEN b.home_points 
                ELSE b.away_points 
            END) as avg_points_scored')
            ])
            ->where('s.season_type', 'Regular Season')
            ->groupBy('ts.team_abv');

        // Main query
        $query = DB::table('nfl_team_stats as ts')
            ->join('nfl_box_scores as b', 'ts.game_id', '=', 'b.game_id')
            ->join('nfl_team_schedules as s', 'b.game_id', '=', 's.game_id')
            ->leftJoinSub($opponentStats, 'opp_stats', function ($join) {
                $join->on(DB::raw('CASE 
                WHEN b.home_team = ts.team_abv THEN b.away_team 
                ELSE b.home_team 
            END'), '=', 'opp_stats.team_abv');
            })
            ->select([
                'ts.team_abv',
                DB::raw('CASE 
                WHEN b.home_team = ts.team_abv THEN b.away_team 
                ELSE b.home_team 
            END as opponent_team'),
                DB::raw('DATE_FORMAT(s.game_date, "%Y-%m-%d") as game_date'),
                'ts.total_yards',
                'ts.rushing_yards',
                'ts.passing_yards',
                DB::raw('CASE 
                WHEN b.home_team = ts.team_abv THEN b.home_points 
                ELSE b.away_points 
            END as points_scored'),
                DB::raw('CASE 
                WHEN b.home_team = ts.team_abv THEN b.away_points 
                ELSE b.home_points 
            END as points_allowed'),
                DB::raw('ts.total_yards - opp_stats.avg_total_yards as yards_differential'),
                DB::raw('CASE 
                WHEN b.home_team = ts.team_abv THEN b.home_points - b.away_points 
                ELSE b.away_points - b.home_points 
            END as points_differential'),
                DB::raw('CASE 
                WHEN ts.total_yards > opp_stats.avg_total_yards 
                    AND (CASE 
                        WHEN b.home_team = ts.team_abv THEN b.home_points 
                        ELSE b.away_points 
                    END) > opp_stats.avg_points_scored THEN "High"
                WHEN ts.total_yards < opp_stats.avg_total_yards 
                    AND (CASE 
                        WHEN b.home_team = ts.team_abv THEN b.home_points 
                        ELSE b.away_points 
                    END) < opp_stats.avg_points_scored THEN "Low"
                ELSE "Medium"
            END as win_probability'),
                DB::raw('ROUND(
                (
                    (ts.total_yards / NULLIF(opp_stats.avg_total_yards, 0)) * 40 +
                    (CASE 
                        WHEN b.home_team = ts.team_abv THEN b.home_points 
                        ELSE b.away_points 
                    END / NULLIF(opp_stats.avg_points_scored, 0)) * 40 +
                    CASE 
                        WHEN b.home_team = ts.team_abv THEN 20 
                        ELSE 0 
                    END
                ),
                1
            ) as edge_score')
            ])
            ->where('s.season_type', 'Regular Season')
            ->when($teamFilter, function ($query) use ($teamFilter) {
                $query->where('ts.team_abv', $teamFilter);
            })
            ->when($teamAbv1 && $teamAbv2, function ($query) use ($teamAbv1, $teamAbv2) {
                $query->where(function ($q) use ($teamAbv1, $teamAbv2) {
                    $q->where('b.home_team', $teamAbv1)
                        ->where('b.away_team', $teamAbv2)
                        ->orWhere(function ($q) use ($teamAbv1, $teamAbv2) {
                            $q->where('b.home_team', $teamAbv2)
                                ->where('b.away_team', $teamAbv1);
                        });
                });
            })
            ->when($week, function ($query) use ($week) {
                $query->where('s.game_week', $week);
            })
            ->when($locationFilter === 'home', function ($query) {
                $query->whereRaw('b.home_team = ts.team_abv');
            })
            ->when($locationFilter === 'away', function ($query) {
                $query->whereRaw('b.away_team = ts.team_abv');
            })
            ->orderByDesc('s.game_date')
            ->orderByDesc(DB::raw('edge_score'))
            ->limit(5);

        return [
            'data' => $query->get(),
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
    public function getFirstHalfTendencies(
        ?string $teamFilter = null,
        ?string $againstConference = null,
        ?string $locationFilter = null
    ): array
    {
        $firstHalfStats = DB::table('nfl_box_scores as b')
            ->join('nfl_team_schedules as s', 'b.game_id', '=', 's.game_id')
            ->select([
                'b.game_id',
                DB::raw('CASE 
                WHEN b.home_team = ? OR ? IS NULL THEN b.home_team
                ELSE b.away_team 
            END as team_abv'),
                DB::raw('CASE 
                WHEN b.home_team = ? OR ? IS NULL THEN 
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, "$.Q1")) AS UNSIGNED) +
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, "$.Q2")) AS UNSIGNED)
                ELSE 
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, "$.Q1")) AS UNSIGNED) +
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, "$.Q2")) AS UNSIGNED)
                END as team_first_half_points'),
                DB::raw('CASE 
                WHEN b.home_team = ? OR ? IS NULL THEN 
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, "$.Q1")) AS UNSIGNED) +
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(b.away_line_score, "$.Q2")) AS UNSIGNED)
                ELSE 
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, "$.Q1")) AS UNSIGNED) +
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(b.home_line_score, "$.Q2")) AS UNSIGNED)
                END as opponent_first_half_points')
            ])
            ->where('s.season_type', 'Regular Season')
            ->when($teamFilter, function ($query) use ($teamFilter) {
                $query->where(function ($q) use ($teamFilter) {
                    $q->where('b.home_team', $teamFilter)
                        ->orWhere('b.away_team', $teamFilter);
                });
            });

        // Add bindings for the CASE statements
        $bindings = array_fill(0, 6, $teamFilter);
        foreach ($bindings as $binding) {
            $firstHalfStats->addBinding($binding, 'select');
        }

        $result = DB::query()
            ->fromSub($firstHalfStats, 'fhs')
            ->select([
                'team_abv',
                DB::raw('COUNT(*) as games_analyzed'),
                DB::raw('ROUND(AVG(team_first_half_points), 1) as avg_first_half_points'),
                DB::raw('ROUND(AVG(opponent_first_half_points), 1) as avg_opponent_first_half_points'),
                DB::raw('ROUND(AVG(team_first_half_points + opponent_first_half_points), 1) as avg_first_half_total'),
                DB::raw('ROUND(STDDEV(team_first_half_points), 1) as first_half_points_stddev'),
                DB::raw('ROUND(
                SUM(CASE WHEN team_first_half_points > opponent_first_half_points THEN 1 ELSE 0 END) * 100.0 / COUNT(*),
                1
            ) as first_half_win_percentage'),
                DB::raw('ROUND(
                SUM(CASE WHEN team_first_half_points + opponent_first_half_points > 21.5 THEN 1 ELSE 0 END) * 100.0 / COUNT(*),
                1
            ) as first_half_over_percentage'),
                DB::raw('MAX(team_first_half_points) as max_first_half_points'),
                DB::raw('MIN(team_first_half_points) as min_first_half_points')
            ])
            ->groupBy('team_abv')
            ->orderByDesc('avg_first_half_points');

        return [
            'data' => $result->get(),
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
        // Get league averages
        $leagueAverages = DB::table('nfl_team_stats')
            ->select([
                DB::raw('AVG(total_yards) as avg_total_yards'),
                DB::raw('AVG(rushing_yards) as avg_rushing_yards'),
                DB::raw('AVG(passing_yards) as avg_passing_yards')
            ])
            ->first();

        // Get recent team stats with opponent info
        $teamStats = DB::table('nfl_team_stats as ts')
            ->join('nfl_box_scores as b', 'ts.game_id', '=', 'b.game_id')
            ->select([
                'ts.total_yards',
                'ts.rushing_yards',
                'ts.passing_yards',
                'ts.game_id',
                'ts.team_abv',
                DB::raw('CASE 
                WHEN b.home_team = ts.team_abv THEN b.away_team 
                ELSE b.home_team 
            END as opponent_team')
            ])
            ->where('ts.team_id', $teamId)
            ->orderByDesc('ts.created_at')
            ->limit($gamesBack)
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
            // Get opponent averages using query builder
            $opponentAverages = DB::table('nfl_team_stats as ts')
                ->join('nfl_box_scores as b', 'ts.game_id', '=', 'b.game_id')
                ->select([
                    DB::raw('AVG(ts.total_yards) as total_yards'),
                    DB::raw('AVG(ts.rushing_yards) as rushing_yards'),
                    DB::raw('AVG(ts.passing_yards) as passing_yards')
                ])
                ->where('ts.team_abv', $stat->opponent_team)
                ->orderByDesc('ts.created_at')
                ->limit($gamesBack)
                ->first();

            if ($opponentAverages) {
                // Calculate adjustment factors using league averages
                $adjustedStats['total_yards'] += $stat->total_yards *
                    ($leagueAverages->avg_total_yards / max($opponentAverages->total_yards, 1));
                $adjustedStats['rushing_yards'] += $stat->rushing_yards *
                    ($leagueAverages->avg_rushing_yards / max($opponentAverages->rushing_yards, 1));
                $adjustedStats['passing_yards'] += $stat->passing_yards *
                    ($leagueAverages->avg_passing_yards / max($opponentAverages->passing_yards, 1));
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