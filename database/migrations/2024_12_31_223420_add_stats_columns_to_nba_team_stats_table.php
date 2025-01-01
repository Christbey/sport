<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatsColumnsToNbaTeamStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nba_team_stats', function (Blueprint $table) {
            // Defensive stats

            $table->integer('blocks')->nullable();
            $table->integer('defensive_rebounds')->nullable();
            $table->integer('steals')->nullable();
            $table->integer('turnover_points')->nullable();
            $table->decimal('avg_defensive_rebounds', 5, 2)->nullable();
            $table->decimal('avg_blocks', 5, 2)->nullable();
            $table->decimal('avg_steals', 5, 2)->nullable();
            $table->decimal('avg_48_defensive_rebounds', 5, 2)->nullable();
            $table->decimal('avg_48_blocks', 5, 2)->nullable();
            $table->decimal('avg_48_steals', 5, 2)->nullable();
            $table->decimal('total_rebounds', 5, 2)->nullable();

            // General stats
            $table->integer('largest_lead')->nullable();
            $table->integer('disqualifications')->nullable();
            $table->integer('flagrant_fouls')->nullable();
            $table->integer('fouls')->nullable();
            $table->integer('ejections')->nullable();
            $table->integer('technical_fouls')->nullable();
            $table->integer('rebounds')->nullable();
            $table->integer('games_played')->nullable();
            $table->integer('games_started')->nullable();
            $table->decimal('avg_rebounds', 5, 2)->nullable();
            $table->decimal('avg_fouls', 5, 2)->nullable();
            $table->decimal('avg_flagrant_fouls', 5, 2)->nullable();
            $table->decimal('avg_technical_fouls', 5, 2)->nullable();
            $table->decimal('avg_ejections', 5, 2)->nullable();
            $table->decimal('avg_team_rebounds', 5, 2)->nullable();
            $table->decimal('avg_disqualifications', 5, 2)->nullable();
            $table->decimal('assist_turnover_ratio', 5, 2)->nullable();
            $table->decimal('steal_foul_ratio', 5, 2)->nullable();
            $table->decimal('block_foul_ratio', 5, 2)->nullable();

            // Offensive stats
            $table->integer('assists')->nullable();
            $table->integer('field_goals_made')->nullable();
            $table->integer('field_goals_attempted')->nullable();
            $table->decimal('field_goal_pct', 5, 2)->nullable();
            $table->integer('free_throws_made')->nullable();
            $table->integer('free_throws_attempted')->nullable();
            $table->decimal('free_throw_pct', 5, 2)->nullable();
            $table->integer('offensive_rebounds')->nullable();
            $table->integer('points')->nullable();
            $table->integer('turnovers')->nullable();
            $table->decimal('three_point_field_goal_pct', 5, 2)->nullable();
            $table->integer('three_point_field_goals_made')->nullable();
            $table->integer('three_point_field_goals_attempted')->nullable();
            $table->integer('points_in_paint')->nullable();
            $table->integer('fast_break_points')->nullable();
            $table->integer('double_double')->nullable();
            $table->integer('triple_double')->nullable();

            // Efficiency stats
            $table->decimal('shooting_efficiency', 5, 2)->nullable();
            $table->decimal('scoring_efficiency', 5, 2)->nullable();

            // Advanced stats
            $table->decimal('estimated_possessions', 5, 2)->nullable();
            $table->decimal('points_per_estimated_possession', 5, 2)->nullable();
            $table->decimal('offensive_rebound_pct', 5, 2)->nullable();
            $table->decimal('avg_estimated_possessions', 5, 2)->nullable();

            // Averages
            $table->decimal('avg_points', 5, 2)->nullable();
            $table->decimal('avg_turnovers', 5, 2)->nullable();
            $table->decimal('avg_offensive_rebounds', 5, 2)->nullable();
            $table->decimal('avg_48_points', 5, 2)->nullable();
            $table->decimal('avg_48_offensive_rebounds', 5, 2)->nullable();
            $table->decimal('avg_48_turnovers', 5, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('nba_team_stats', function (Blueprint $table) {
            $table->dropColumn([
                'blocks',
                'defensive_rebounds',
                'steals',
                'turnover_points',
                'avg_defensive_rebounds',
                'avg_blocks',
                'avg_steals',
                'avg_48_defensive_rebounds',
                'avg_48_blocks',
                'avg_48_steals',
                'largest_lead',
                'disqualifications',
                'flagrant_fouls',
                'fouls',
                'ejections',
                'technical_fouls',
                'rebounds',
                'games_played',
                'games_started',
                'avg_rebounds',
                'avg_fouls',
                'avg_flagrant_fouls',
                'avg_technical_fouls',
                'avg_ejections',
                'avg_disqualifications',
                'assist_turnover_ratio',
                'steal_foul_ratio',
                'block_foul_ratio',
                'assists',
                'field_goals_made',
                'field_goals_attempted',
                'field_goal_pct',
                'free_throws_made',
                'free_throws_attempted',
                'free_throw_pct',
                'offensive_rebounds',
                'points',
                'turnovers',
                'three_point_field_goal_pct',
                'three_point_field_goals_made',
                'three_point_field_goals_attempted',
                'points_in_paint',
                'fast_break_points',
                'double_double',
                'triple_double',
                'shooting_efficiency',
                'scoring_efficiency',
                'estimated_possessions',
                'points_per_estimated_possession',
                'offensive_rebound_pct',
                'avg_estimated_possessions',
                'avg_points',
                'avg_turnovers',
                'avg_offensive_rebounds',
                'avg_48_points',
                'avg_48_offensive_rebounds',
                'avg_48_turnovers',
            ]);
        });
    }
}
