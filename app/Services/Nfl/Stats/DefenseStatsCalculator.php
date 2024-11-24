<?php

namespace App\Services\Nfl\Stats;

use Illuminate\Support\Collection;

class DefenseStatsCalculator
{
    /**
     * Calculate defensive statistics from game data
     *
     * @param Collection $games
     * @return array
     */
    public function calculate(Collection $games): array
    {
        if ($games->isEmpty()) {
            return $this->getEmptyStats();
        }

        return [
            'yards_allowed' => $this->calculateYardsAllowed($games),
            'scoring_defense' => $this->calculateScoringDefense($games),
            'pressure_stats' => $this->calculatePressureStats($games),
            'turnover_stats' => $this->calculateTurnoverStats($games),
            'situational_stats' => $this->calculateSituationalStats($games),
            'efficiency_metrics' => $this->calculateEfficiencyMetrics($games)
        ];
    }

    /**
     * Get empty stats structure
     *
     * @return array
     */
    protected function getEmptyStats(): array
    {
        return [
            'yards_allowed' => [
                'total_per_game' => 0,
                'rushing_per_game' => 0,
                'passing_per_game' => 0,
                'first_downs_per_game' => 0,
                'yards_per_play' => 0
            ],
            'scoring_defense' => [
                'points_per_game' => 0,
                'touchdowns_allowed' => 0,
                'field_goals_allowed' => 0,
                'safeties' => 0,
                'shutouts' => 0,
                'games_under_20_points' => 0,
                'red_zone_touchdown_rate' => 0
            ],
            'pressure_stats' => [
                'sacks_per_game' => 0,
                'qb_hits_per_game' => 0,
                'tackles_for_loss' => 0,
                'pressure_rate' => 0,
                'blitz_rate' => 0,
                'hurry_rate' => 0
            ],
            'turnover_stats' => [
                'interceptions_per_game' => 0,
                'fumbles_forced_per_game' => 0,
                'fumbles_recovered_per_game' => 0,
                'total_takeaways' => 0,
                'turnover_rate' => 0,
                'points_off_turnovers_per_game' => 0
            ],
            'situational_stats' => [
                'third_down_stop_rate' => 0,
                'fourth_down_stop_rate' => 0,
                'red_zone_stop_rate' => 0,
                'goal_line_stop_rate' => 0
            ],
            'efficiency_metrics' => [
                'yards_per_rush_allowed' => 0,
                'yards_per_pass_allowed' => 0,
                'qb_rating_allowed' => 0,
                'completion_percentage_allowed' => 0,
                'big_plays_allowed' => 0,
                'three_and_out_forced_rate' => 0
            ]
        ];
    }

    /**
     * Calculate yards allowed metrics
     *
     * @param Collection $games
     * @return array
     */
    protected function calculateYardsAllowed(Collection $games): array
    {
        return [
            'total_per_game' => round($games->avg('opponent_total_yards'), 1),
            'rushing_per_game' => round($games->avg('opponent_rushing_yards'), 1),
            'passing_per_game' => round($games->avg('opponent_passing_yards'), 1),
            'first_downs_per_game' => round($games->avg('opponent_first_downs'), 1),
            'yards_per_play' => $games->sum('opponent_total_plays') > 0 ?
                round($games->sum('opponent_total_yards') / $games->sum('opponent_total_plays'), 2) : 0
        ];
    }

    /**
     * Calculate scoring defense metrics
     *
     * @param Collection $games
     * @return array
     */
    protected function calculateScoringDefense(Collection $games): array
    {
        return [
            'points_per_game' => round($games->avg('opponent_points'), 1),
            'touchdowns_allowed' => round($games->avg('opponent_touchdowns'), 1),
            'field_goals_allowed' => round($games->avg('opponent_field_goals'), 1),
            'safeties' => round($games->avg('safeties'), 1),
            'shutouts' => $games->where('opponent_points', 0)->count(),
            'games_under_20_points' => $games->where('opponent_points', '<', 20)->count(),
            'red_zone_touchdown_rate' => $games->sum('opponent_red_zone_attempts') > 0 ?
                round(($games->sum('opponent_red_zone_touchdowns') / $games->sum('opponent_red_zone_attempts')) * 100, 1) : 0
        ];
    }

    /**
     * Calculate pressure-related stats
     *
     * @param Collection $games
     * @return array
     */
    protected function calculatePressureStats(Collection $games): array
    {
        return [
            'sacks_per_game' => round($games->avg('sacks'), 1),
            'qb_hits_per_game' => round($games->avg('qb_hits'), 1),
            'tackles_for_loss' => round($games->avg('tackles_for_loss'), 1),
            'pressure_rate' => $games->sum('opponent_passing_attempts') > 0 ?
                round(($games->sum('pressures') / $games->sum('opponent_passing_attempts')) * 100, 1) : 0,
            'blitz_rate' => $games->sum('opponent_passing_plays') > 0 ?
                round(($games->sum('blitzes') / $games->sum('opponent_passing_plays')) * 100, 1) : 0,
            'hurry_rate' => $games->sum('opponent_passing_plays') > 0 ?
                round(($games->sum('hurries') / $games->sum('opponent_passing_plays')) * 100, 1) : 0
        ];
    }

    /**
     * Calculate turnover-related stats
     *
     * @param Collection $games
     * @return array
     */
    protected function calculateTurnoverStats(Collection $games): array
    {
        return [
            'interceptions_per_game' => round($games->avg('interceptions'), 1),
            'fumbles_forced_per_game' => round($games->avg('forced_fumbles'), 1),
            'fumbles_recovered_per_game' => round($games->avg('fumbles_recovered'), 1),
            'total_takeaways' => $games->sum('interceptions') + $games->sum('fumbles_recovered'),
            'turnover_rate' => $games->sum('opponent_total_plays') > 0 ?
                round(($games->sum('total_takeaways') / $games->sum('opponent_total_plays')) * 100, 2) : 0,
            'points_off_turnovers_per_game' => round($games->avg('points_off_turnovers'), 1)
        ];
    }

    /**
     * Calculate situational defensive stats
     *
     * @param Collection $games
     * @return array
     */
    protected function calculateSituationalStats(Collection $games): array
    {
        return [
            'third_down_stop_rate' => $games->sum('opponent_third_down_attempts') > 0 ?
                round((($games->sum('opponent_third_down_attempts') - $games->sum('opponent_third_down_conversions'))
                        / $games->sum('opponent_third_down_attempts')) * 100, 1) : 0,
            'fourth_down_stop_rate' => $games->sum('opponent_fourth_down_attempts') > 0 ?
                round((($games->sum('opponent_fourth_down_attempts') - $games->sum('opponent_fourth_down_conversions'))
                        / $games->sum('opponent_fourth_down_attempts')) * 100, 1) : 0,
            'red_zone_stop_rate' => $games->sum('opponent_red_zone_attempts') > 0 ?
                round((($games->sum('opponent_red_zone_attempts') - $games->sum('opponent_red_zone_scores'))
                        / $games->sum('opponent_red_zone_attempts')) * 100, 1) : 0,
            'goal_line_stop_rate' => $games->sum('opponent_goal_line_attempts') > 0 ?
                round((($games->sum('opponent_goal_line_attempts') - $games->sum('opponent_goal_line_scores'))
                        / $games->sum('opponent_goal_line_attempts')) * 100, 1) : 0
        ];
    }

    /**
     * Calculate efficiency metrics
     *
     * @param Collection $games
     * @return array
     */
    protected function calculateEfficiencyMetrics(Collection $games): array
    {
        return [
            'yards_per_rush_allowed' => $games->sum('opponent_rushing_attempts') > 0 ?
                round($games->sum('opponent_rushing_yards') / $games->sum('opponent_rushing_attempts'), 2) : 0,
            'yards_per_pass_allowed' => $games->sum('opponent_passing_attempts') > 0 ?
                round($games->sum('opponent_passing_yards') / $games->sum('opponent_passing_attempts'), 2) : 0,
            'qb_rating_allowed' => round($games->avg('opponent_qb_rating'), 1),
            'completion_percentage_allowed' => $games->sum('opponent_passing_attempts') > 0 ?
                round(($games->sum('opponent_completions') / $games->sum('opponent_passing_attempts')) * 100, 1) : 0,
            'big_plays_allowed' => round($games->avg('opponent_plays_20_plus_yards'), 1),
            'three_and_out_forced_rate' => $games->sum('opponent_drives') > 0 ?
                round(($games->sum('opponent_three_and_outs') / $games->sum('opponent_drives')) * 100, 1) : 0
        ];
    }
}