<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingFieldsToNflTeamStatsTable extends Migration
{
    public function up()
    {
        Schema::table('nfl_team_stats', function (Blueprint $table) {
            // Add new columns
            $table->integer('rushing_attempts')->nullable();
            $table->integer('fumbles_lost')->nullable();
            $table->string('penalties')->nullable();
            $table->integer('total_plays')->nullable();
            $table->string('possession')->nullable();
            $table->integer('safeties')->nullable();
            $table->string('pass_completions_and_attempts')->nullable();
            $table->integer('passing_first_downs')->nullable();
            $table->integer('interceptions_thrown')->nullable();
            $table->string('sacks_and_yards_lost')->nullable();
            $table->string('third_down_efficiency')->nullable();
            $table->decimal('yards_per_play', 5, 2)->nullable();
            $table->string('red_zone_scored_and_attempted')->nullable();
            $table->integer('defensive_interceptions')->nullable();
            $table->integer('defensive_or_special_teams_tds')->nullable();
            $table->integer('total_drives')->nullable();
            $table->integer('rushing_first_downs')->nullable();
            $table->integer('first_downs')->nullable();
            $table->integer('first_downs_from_penalties')->nullable();
            $table->string('fourth_down_efficiency')->nullable();
            $table->decimal('yards_per_rush', 5, 2)->nullable();
            $table->integer('turnovers')->nullable();
            $table->decimal('yards_per_pass', 5, 2)->nullable();
        });
    }

    public function down()
    {
        Schema::table('nfl_team_stats', function (Blueprint $table) {
            // Drop the columns if the migration is rolled back
            $table->dropColumn([
                'rushing_attempts',
                'fumbles_lost',
                'penalties',
                'total_plays',
                'possession',
                'safeties',
                'pass_completions_and_attempts',
                'passing_first_downs',
                'interceptions_thrown',
                'sacks_and_yards_lost',
                'third_down_efficiency',
                'yards_per_play',
                'red_zone_scored_and_attempted',
                'defensive_interceptions',
                'defensive_or_special_teams_tds',
                'total_drives',
                'rushing_first_downs',
                'first_downs',
                'first_downs_from_penalties',
                'fourth_down_efficiency',
                'yards_per_rush',
                'turnovers',
                'yards_per_pass',
            ]);
        });
    }
}
