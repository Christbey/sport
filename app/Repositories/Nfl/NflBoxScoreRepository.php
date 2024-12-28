<?php

namespace App\Repositories\Nfl;

use App\Models\Nfl\NflBoxScore;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class NflBoxScoreRepository
{
    /**
     * Update or create a box score record using API data.
     *
     * @param array $boxScoreData Merged game and box score data.
     */
    public function updateOrCreateFromRapidApi(array $boxScoreData): void
    {
        // Extract the lineScore data
        $lineScore = $boxScoreData['lineScore'] ?? null;

        $data = [
            'game_id' => $boxScoreData['gameID'] ?? null,
            'home_team' => $boxScoreData['home'] ?? null,
            'away_team' => $boxScoreData['away'] ?? null,
            'home_points' => isset($boxScoreData['homePts']) ? (int)$boxScoreData['homePts'] : null,
            'away_points' => isset($boxScoreData['awayPts']) ? (int)$boxScoreData['awayPts'] : null,
            'game_date' => isset($boxScoreData['gameDate']) ? Carbon::createFromFormat('Ymd', $boxScoreData['gameDate'])->toDateString() : null,
            'location' => $boxScoreData['gameLocation'] ?? null,
            'home_line_score' => $lineScore['home'] ?? null,
            'away_line_score' => $lineScore['away'] ?? null,
            'away_result' => $boxScoreData['awayResult'] ?? null,
            'home_result' => $boxScoreData['homeResult'] ?? null,
            'game_status' => $boxScoreData['gameStatus'] ?? null,
        ];

        // Remove null values
        $data = array_filter($data, fn($value) => !is_null($value));

        // Update or create the box score record
        NflBoxScore::updateOrCreate(
            ['game_id' => $data['game_id']],
            $data
        );
    }


    public function getGamesByTeam(string $teamName, ?int $season, ?int $week = null, int $limit = 20): array|Collection
    {
        return NflBoxScore::query()
            ->join('nfl_team_schedules', function ($join) {
                $join->on('nfl_box_scores.game_id', '=', 'nfl_team_schedules.game_id')
                    ->where('nfl_team_schedules.season_type', 'Regular Season');
            })
            ->where(function ($query) use ($teamName) {
                $query->where('nfl_box_scores.home_team', $teamName)
                    ->orWhere('nfl_box_scores.away_team', $teamName);
            })
            ->when($season, function ($query) use ($season) {
                $query->whereYear('nfl_box_scores.game_date', $season);
            })
            ->when($week, function ($query) use ($week) {
                if (!is_numeric($week) || $week < 1 || $week > 17) {
                    throw new InvalidArgumentException("Invalid week number '{$week}'. Week must be between 1 and 17.");
                }
                $query->whereRaw('CAST(nfl_team_schedules.game_week AS UNSIGNED) < ?', [$week]);
            })
            ->with('teamStats')
            ->orderBy('nfl_box_scores.game_date', 'desc')
            ->take($limit)
            ->get();
    }

    public function getQuarterlyPointsAnalysis(?array $teams = null, ?int $season = null): array
    {
        // Start with a base query for box scores
        $query = NflBoxScore::query()
            ->join('nfl_team_schedules', function ($join) {
                $join->on('nfl_box_scores.game_id', '=', 'nfl_team_schedules.game_id')
                    ->where('nfl_team_schedules.season_type', 'Regular Season');
            });

        // Filter by teams if provided
        if (!empty($teams)) {
            $query->where(function ($q) use ($teams) {
                $q->whereIn('nfl_box_scores.home_team', $teams)
                    ->orWhereIn('nfl_box_scores.away_team', $teams);
            });
        }

        // Filter by season if provided
        if ($season) {
            $query->whereYear('nfl_box_scores.game_date', $season);
        }

        // Combine home and away quarterly data
        $quarterlyData = $query->get()->flatMap(function ($boxScore) {
            // Safely decode line score data
            $homeQuarters = is_string($boxScore->home_line_score)
                ? json_decode($boxScore->home_line_score, true) ?? []
                : (is_array($boxScore->home_line_score) ? $boxScore->home_line_score : []);
            $awayQuarters = is_string($boxScore->away_line_score)
                ? json_decode($boxScore->away_line_score, true) ?? []
                : (is_array($boxScore->away_line_score) ? $boxScore->away_line_score : []);

            $homeData = array_merge(
                ['team' => $boxScore->home_team],
                array_map('intval', $homeQuarters)
            );
            $awayData = array_merge(
                ['team' => $boxScore->away_team],
                array_map('intval', $awayQuarters)
            );

            return [$homeData, $awayData];
        });

        // Group by team and calculate statistics
        $teamQuarterStats = $quarterlyData->groupBy('team')->map(function ($teamQuarters) {
            $quarterStats = [];

            // Calculate stats for Q1-Q4
            for ($quarter = 1; $quarter <= 4; $quarter++) {
                $quarterKey = "Q{$quarter}";
                $quarterPoints = $teamQuarters->pluck($quarterKey)->filter();

                $quarterStats[$quarterKey] = [
                    'avg' => round($quarterPoints->avg(), 2),
                    'min' => $quarterPoints->min(),
                    'max' => $quarterPoints->max(),
                    'total_games' => $quarterPoints->count(),
                ];
            }

            return $quarterStats;
        });

        // Prepare comparison data if two teams are provided
        $comparisonData = null;
        if (count($teams) === 2) {
            $comparisonData = [];
            for ($quarter = 1; $quarter <= 4; $quarter++) {
                $quarterKey = "Q{$quarter}";
                $comparisonData[$quarterKey] = [
                    $teams[0] => $teamQuarterStats[$teams[0]][$quarterKey] ?? [],
                    $teams[1] => $teamQuarterStats[$teams[1]][$quarterKey] ?? [],
                    'difference' => isset($teamQuarterStats[$teams[0]][$quarterKey], $teamQuarterStats[$teams[1]][$quarterKey])
                        ? round(
                            $teamQuarterStats[$teams[0]][$quarterKey]['avg'] -
                            $teamQuarterStats[$teams[1]][$quarterKey]['avg'],
                            2
                        )
                        : null,
                ];
            }
        }

        return [
            'team_quarterly_stats' => $teamQuarterStats,
            'team_comparison' => $comparisonData,
        ];
    }

    public function analyzeTeamQuarterlyPerformance(
        ?string $teamAbv = null,
        ?int    $season = null,
        ?string $locationFilter = null,
        array   $performanceMetrics = ['points'],
        string  $aggregationType = 'detailed'
    ): array
    {
        if (!$teamAbv) {
            throw new InvalidArgumentException('Team abbreviation is required.');
        }

        $query = NflBoxScore::query()
            ->join('nfl_team_schedules', function ($join) {
                $join->on('nfl_box_scores.game_id', '=', 'nfl_team_schedules.game_id')
                    ->where('nfl_team_schedules.season_type', 'Regular Season');
            })
            ->where(function ($q) use ($teamAbv) {
                $q->where('nfl_box_scores.home_team', $teamAbv)
                    ->orWhere('nfl_box_scores.away_team', $teamAbv);
            });

        if ($season) {
            $query->whereYear('nfl_box_scores.game_date', $season);
        }

        if ($locationFilter === 'home') {
            $query->where('nfl_box_scores.home_team', $teamAbv);
        } elseif ($locationFilter === 'away') {
            $query->where('nfl_box_scores.away_team', $teamAbv);
        }

        $boxScores = $query->get();

        // Check for no data and return an error
        if ($boxScores->isEmpty()) {
            return [
                'error' => "No games found for team {$teamAbv} in the specified season {$season}.",
            ];
        }

        // Process data as before...
        $quarterlyData = $boxScores->flatMap(function ($boxScore) use ($teamAbv) {
            $isHome = $boxScore->home_team === $teamAbv;
            $lineScore = $isHome ? $boxScore->home_line_score : $boxScore->away_line_score;

            $quarters = is_string($lineScore) ? json_decode($lineScore, true) : [];
            if (json_last_error() !== JSON_ERROR_NONE) {
                $quarters = [];
            }

            $relevantQuarters = collect($quarters)->filter(function ($value, $key) {
                return preg_match('/^Q[1-4]$/', $key);
            });

            return $relevantQuarters->map(function ($points, $quarter) use ($teamAbv, $isHome) {
                return [
                    'team' => $teamAbv,
                    'quarter' => $quarter,
                    'points' => (int)$points,
                    'location' => $isHome ? 'home' : 'away',
                ];
            });
        });

        $groupedData = collect($quarterlyData)->groupBy('quarter');

        if ($groupedData->isEmpty()) {
            return [
                'error' => "No valid quarterly data found for team {$teamAbv} in the specified season {$season}.",
            ];
        }

        $result = $groupedData->map(function ($data, $quarter) {
            $points = collect($data)->pluck('points');
            return [
                'quarter' => $quarter,
                'avg_points' => round($points->avg(), 2),
                'min_points' => $points->min(),
                'max_points' => $points->max(),
                'games' => $points->count(),
            ];
        });

        return $result->toArray();
    }


}

