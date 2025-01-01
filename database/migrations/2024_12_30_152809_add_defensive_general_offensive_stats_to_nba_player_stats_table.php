<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nba_player_stats', function (Blueprint $table) {
            // Defensive Stats
            $table->float('blocks')->nullable()->after('splits_json')->comment('Number of blocks');
            $table->float('defensive_rebounds')->nullable()->comment('Number of defensive rebounds');
            $table->float('steals')->nullable()->comment('Number of steals');
            $table->float('avg_defensive_rebounds')->nullable()->comment('Average defensive rebounds per game');
            $table->float('avg_blocks')->nullable()->comment('Average blocks per game');
            $table->float('avg_steals')->nullable()->comment('Average steals per game');
            $table->float('avg_48_defensive_rebounds')->nullable()->comment('Average defensive rebounds per 48 minutes');
            $table->float('avg_48_blocks')->nullable()->comment('Average blocks per 48 minutes');
            $table->float('avg_48_steals')->nullable()->comment('Average steals per 48 minutes');

            // General Stats
            $table->float('largest_lead')->nullable()->comment('Largest lead in points');
            $table->float('disqualifications')->nullable()->comment('Number of disqualifications');
            $table->float('flagrant_fouls')->nullable()->comment('Number of flagrant fouls');
            $table->float('fouls')->nullable()->comment('Number of fouls');
            $table->float('ejections')->nullable()->comment('Number of ejections');
            $table->float('technical_fouls')->nullable()->comment('Number of technical fouls');
            $table->float('rebounds')->nullable()->comment('Total number of rebounds');
            $table->float('minutes')->nullable()->comment('Total minutes played');
            $table->float('avg_minutes')->nullable()->comment('Average minutes per game');
            $table->float('nba_rating')->nullable()->comment('NBA Rating');
            $table->float('plus_minus')->nullable()->comment('Plus-Minus');
            $table->float('avg_rebounds')->nullable()->comment('Average rebounds per game');
            $table->float('avg_fouls')->nullable()->comment('Average fouls per game');
            $table->float('avg_flagrant_fouls')->nullable()->comment('Average flagrant fouls per game');
            $table->float('avg_technical_fouls')->nullable()->comment('Average technical fouls per game');
            $table->float('avg_ejections')->nullable()->comment('Average ejections per game');
            $table->float('avg_disqualifications')->nullable()->comment('Average disqualifications per game');
            $table->float('assist_turnover_ratio')->nullable()->comment('Assist to turnover ratio');
            $table->float('steal_foul_ratio')->nullable()->comment('Steal to foul ratio');
            $table->float('block_foul_ratio')->nullable()->comment('Block to foul ratio');
            $table->float('avg_team_rebounds')->nullable()->comment('Average team rebounds per game');
            $table->float('total_rebounds')->nullable()->comment('Total rebounds');
            $table->float('total_technical_fouls')->nullable()->comment('Total technical fouls');
            $table->float('team_assist_turnover_ratio')->nullable()->comment('Team assist to turnover ratio');
            $table->float('steal_turnover_ratio')->nullable()->comment('Steal to turnover ratio');
            $table->float('avg_48_rebounds')->nullable()->comment('Average rebounds per 48 minutes');
            $table->float('avg_48_fouls')->nullable()->comment('Average fouls per 48 minutes');
            $table->float('avg_48_flagrant_fouls')->nullable()->comment('Average flagrant fouls per 48 minutes');
            $table->float('avg_48_technical_fouls')->nullable()->comment('Average technical fouls per 48 minutes');
            $table->float('avg_48_ejections')->nullable()->comment('Average ejections per 48 minutes');
            $table->float('avg_48_disqualifications')->nullable()->comment('Average disqualifications per 48 minutes');
            $table->float('r40')->nullable()->comment('Rebounds per 40 minutes');
            $table->float('games_played')->nullable()->comment('Games played');
            $table->float('games_started')->nullable()->comment('Games started');
            $table->float('double_double')->nullable()->comment('Number of double-doubles');
            $table->float('triple_double')->nullable()->comment('Number of triple-doubles');

            // Offensive Stats
            $table->float('assists')->nullable()->comment('Number of assists');
            $table->float('field_goals')->nullable()->comment('Field goals made');
            $table->float('field_goals_attempted')->nullable()->comment('Field goals attempted');
            $table->float('field_goals_made')->nullable()->comment('Field goals made');
            $table->float('field_goal_pct')->nullable()->comment('Field goal percentage');
            $table->float('free_throws')->nullable()->comment('Free throws made and attempted');
            $table->float('free_throw_pct')->nullable()->comment('Free throw percentage');
            $table->float('free_throws_attempted')->nullable()->comment('Free throws attempted');
            $table->float('free_throws_made')->nullable()->comment('Free throws made');
            $table->float('offensive_rebounds')->nullable()->comment('Number of offensive rebounds');
            $table->float('points')->nullable()->comment('Number of points scored');
            $table->float('turnovers')->nullable()->comment('Number of turnovers');
            $table->float('three_point_pct')->nullable()->comment('Three-point field goal percentage');
            $table->float('three_point_field_goals_attempted')->nullable()->comment('Three-point field goals attempted');
            $table->float('three_point_field_goals_made')->nullable()->comment('Three-point field goals made');
            $table->float('total_turnovers')->nullable()->comment('Total turnovers');
            $table->float('points_in_paint')->nullable()->comment('Points scored in the paint');
            $table->float('brick_index')->nullable()->comment('Brick Index');
            $table->float('avg_field_goals_made')->nullable()->comment('Average field goals made per game');
            $table->float('avg_field_goals_attempted')->nullable()->comment('Average field goals attempted per game');
            $table->float('avg_three_point_field_goals_made')->nullable()->comment('Average three-point field goals made per game');
            $table->float('avg_three_point_field_goals_attempted')->nullable()->comment('Average three-point field goals attempted per game');
            $table->float('avg_free_throws_made')->nullable()->comment('Average free throws made per game');
            $table->float('avg_free_throws_attempted')->nullable()->comment('Average free throws attempted per game');
            $table->float('avg_points')->nullable()->comment('Average points per game');
            $table->float('avg_offensive_rebounds')->nullable()->comment('Average offensive rebounds per game');
            $table->float('avg_assists')->nullable()->comment('Average assists per game');
            $table->float('avg_turnovers')->nullable()->comment('Average turnovers per game');
            $table->float('offensive_rebound_pct')->nullable()->comment('Offensive rebound percentage');
            $table->float('estimated_possessions')->nullable()->comment('Estimated possessions');
            $table->float('avg_estimated_possessions')->nullable()->comment('Average estimated possessions per game');
            $table->float('points_per_estimated_possession')->nullable()->comment('Points per estimated possession');
            $table->float('avg_team_turnovers')->nullable()->comment('Average team turnovers per game');
            $table->float('avg_total_turnovers')->nullable()->comment('Average total turnovers per game');
            $table->float('three_point_field_goal_pct')->nullable()->comment('Three-point field goal percentage');
            $table->float('two_point_field_goals_made')->nullable()->comment('Two-point field goals made');
            $table->float('two_point_field_goals_attempted')->nullable()->comment('Two-point field goals attempted');
            $table->float('avg_two_point_field_goals_made')->nullable()->comment('Average two-point field goals made per game');
            $table->float('avg_two_point_field_goals_attempted')->nullable()->comment('Average two-point field goals attempted per game');
            $table->float('two_point_field_goal_pct')->nullable()->comment('Two-point field goal percentage');
            $table->float('shooting_efficiency')->nullable()->comment('Shooting Efficiency');
            $table->float('scoring_efficiency')->nullable()->comment('Scoring Efficiency');
            $table->float('avg_48_field_goals_made')->nullable()->comment('Average field goals made per 48 minutes');
            $table->float('avg_48_field_goals_attempted')->nullable()->comment('Average field goals attempted per 48 minutes');
            $table->float('avg_48_three_point_field_goals_made')->nullable()->comment('Average three-point field goals made per 48 minutes');
            $table->float('avg_48_three_point_field_goals_attempted')->nullable()->comment('Average three-point field goals attempted per 48 minutes');
            $table->float('avg_48_free_throws_made')->nullable()->comment('Average free throws made per 48 minutes');
            $table->float('avg_48_free_throws_attempted')->nullable()->comment('Average free throws attempted per 48 minutes');
            $table->float('avg_48_points')->nullable()->comment('Average points per 48 minutes');
            $table->float('avg_48_offensive_rebounds')->nullable()->comment('Average offensive rebounds per 48 minutes');
            $table->float('avg_48_assists')->nullable()->comment('Average assists per 48 minutes');
            $table->float('avg_48_turnovers')->nullable()->comment('Average turnovers per 48 minutes');
            $table->float('p40')->nullable()->comment('Points per 40 minutes');
            $table->float('a40')->nullable()->comment('Assists per 40 minutes');
        });
    }

    public function down(): void
    {
        Schema::table('nba_player_stats', function (Blueprint $table) {
            // Defensive Stats
            $table->dropColumn([
                'blocks',
                'defensive_rebounds',
                'steals',
                'avg_defensive_rebounds',
                'avg_blocks',
                'avg_steals',
                'avg_48_defensive_rebounds',
                'avg_48_blocks',
                'avg_48_steals',
            ]);

            // General Stats
            $table->dropColumn([
                'largest_lead',
                'disqualifications',
                'flagrant_fouls',
                'fouls',
                'ejections',
                'technical_fouls',
                'rebounds',
                'minutes',
                'avg_minutes',
                'nba_rating',
                'plus_minus',
                'avg_rebounds',
                'avg_fouls',
                'avg_flagrant_fouls',
                'avg_technical_fouls',
                'avg_ejections',
                'avg_disqualifications',
                'assist_turnover_ratio',
                'steal_foul_ratio',
                'block_foul_ratio',
                'avg_team_rebounds',
                'total_rebounds',
                'total_technical_fouls',
                'team_assist_turnover_ratio',
                'steal_turnover_ratio',
                'avg_48_rebounds',
                'avg_48_fouls',
                'avg_48_flagrant_fouls',
                'avg_48_technical_fouls',
                'avg_48_ejections',
                'avg_48_disqualifications',
                'r40',
                'games_played',
                'games_started',
                'double_double',
                'triple_double',
            ]);

            // Offensive Stats
            $table->dropColumn([
                'assists',
                'field_goals',
                'field_goals_attempted',
                'field_goals_made',
                'field_goal_pct',
                'free_throws',
                'free_throw_pct',
                'free_throws_attempted',
                'free_throws_made',
                'offensive_rebounds',
                'points',
                'turnovers',
                'three_point_pct',
                'three_point_field_goals_attempted',
                'three_point_field_goals_made',
                'total_turnovers',
                'points_in_paint',
                'brick_index',
                'avg_field_goals_made',
                'avg_field_goals_attempted',
                'avg_three_point_field_goals_made',
                'avg_three_point_field_goals_attempted',
                'avg_free_throws_made',
                'avg_free_throws_attempted',
                'avg_points',
                'avg_offensive_rebounds',
                'avg_assists',
                'avg_turnovers',
                'offensive_rebound_pct',
                'estimated_possessions',
                'avg_estimated_possessions',
                'points_per_estimated_possession',
                'avg_team_turnovers',
                'avg_total_turnovers',
                'three_point_field_goal_pct',
                'two_point_field_goals_made',
                'two_point_field_goals_attempted',
                'avg_two_point_field_goals_made',
                'avg_two_point_field_goals_attempted',
                'two_point_field_goal_pct',
                'shooting_efficiency',
                'scoring_efficiency',
                'avg_48_field_goals_made',
                'avg_48_field_goals_attempted',
                'avg_48_three_point_field_goals_made',
                'avg_48_three_point_field_goals_attempted',
                'avg_48_free_throws_made',
                'avg_48_free_throws_attempted',
                'avg_48_points',
                'avg_48_offensive_rebounds',
                'avg_48_assists',
                'avg_48_turnovers',
                'p40',
                'a40',
            ]);
        });
    }
};
