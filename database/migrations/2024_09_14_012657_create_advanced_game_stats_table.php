<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdvancedGameStatsTable extends Migration
{
    public function up()
    {
        Schema::create('advanced_game_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_id'); // Game ID
            $table->integer('season');
            $table->integer('week');
            $table->string('team');
            $table->unsignedBigInteger('team_id')->nullable(); // Add team_id to the migration
            $table->string('opponent');

            // Offense Stats
            $table->integer('offense_plays');
            $table->integer('offense_drives');
            $table->float('offense_ppa');
            $table->float('offense_total_ppa');
            $table->float('offense_success_rate');
            $table->float('offense_explosiveness');
            $table->float('offense_power_success')->nullable();
            $table->float('offense_stuff_rate')->nullable();
            $table->float('offense_line_yards')->nullable();
            $table->integer('offense_line_yards_total')->nullable();
            $table->float('offense_second_level_yards')->nullable();
            $table->integer('offense_second_level_yards_total')->nullable();
            $table->float('offense_open_field_yards')->nullable();
            $table->integer('offense_open_field_yards_total')->nullable();

            // Standard Downs Stats
            $table->float('offense_standard_downs_ppa')->nullable();
            $table->float('offense_standard_downs_success_rate')->nullable();
            $table->float('offense_standard_downs_explosiveness')->nullable();

            // Passing Downs Stats
            $table->float('offense_passing_downs_ppa')->nullable();
            $table->float('offense_passing_downs_success_rate')->nullable();
            $table->float('offense_passing_downs_explosiveness')->nullable();

            // Rushing Plays
            $table->float('offense_rushing_ppa')->nullable();
            $table->float('offense_rushing_total_ppa')->nullable();
            $table->float('offense_rushing_success_rate')->nullable();
            $table->float('offense_rushing_explosiveness')->nullable();

            // Passing Plays
            $table->float('offense_passing_ppa')->nullable();
            $table->float('offense_passing_total_ppa')->nullable();
            $table->float('offense_passing_success_rate')->nullable();
            $table->float('offense_passing_explosiveness')->nullable();

            // Defense Stats
            $table->integer('defense_plays');
            $table->integer('defense_drives');
            $table->float('defense_ppa');
            $table->float('defense_total_ppa');
            $table->float('defense_success_rate');
            $table->float('defense_explosiveness');
            $table->float('defense_power_success')->nullable();
            $table->float('defense_stuff_rate')->nullable();
            $table->float('defense_line_yards')->nullable();
            $table->integer('defense_line_yards_total')->nullable();
            $table->float('defense_second_level_yards')->nullable();
            $table->integer('defense_second_level_yards_total')->nullable();
            $table->float('defense_open_field_yards')->nullable();
            $table->integer('defense_open_field_yards_total')->nullable();

            // Standard Downs Stats
            $table->float('defense_standard_downs_ppa')->nullable();
            $table->float('defense_standard_downs_success_rate')->nullable();
            $table->float('defense_standard_downs_explosiveness')->nullable();

            // Passing Downs Stats
            $table->float('defense_passing_downs_ppa')->nullable();
            $table->float('defense_passing_downs_success_rate')->nullable();
            $table->float('defense_passing_downs_explosiveness')->nullable();

            // Rushing Plays
            $table->float('defense_rushing_ppa')->nullable();
            $table->float('defense_rushing_total_ppa')->nullable();
            $table->float('defense_rushing_success_rate')->nullable();
            $table->float('defense_rushing_explosiveness')->nullable();

            // Passing Plays
            $table->float('defense_passing_ppa')->nullable();
            $table->float('defense_passing_total_ppa')->nullable();
            $table->float('defense_passing_success_rate')->nullable();
            $table->float('defense_passing_explosiveness')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('advanced_game_stats');
    }
}
