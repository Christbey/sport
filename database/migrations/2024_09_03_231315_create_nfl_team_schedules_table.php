<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNFLTeamSchedulesTable extends Migration
{
    public function up()
    {
        Schema::create('nfl_team_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('team_abv');  // Team abbreviation
            $table->string('game_id');  // Game ID
            $table->string('season_type');  // Preseason, Regular Season, Postseason
            $table->string('away_team');  // Away team abbreviation
            $table->unsignedBigInteger('home_team_id');  // Home team ID
            $table->bigInteger('espn_event_id')->nullable(); // ESPN event ID
            $table->string('uid')->nullable(); // ESPN UID
            $table->string('status_type_detail')->nullable(); // Status detail from ESPN
            $table->string('home_team_record')->nullable(); // Home team record
            $table->string('away_team_record')->nullable(); // Away team record
            $table->boolean('neutral_site')->nullable(); // Neutral site flag
            $table->boolean('conference_competition')->nullable(); // Conference competition flag
            $table->integer('attendance')->nullable(); // Attendance count
            $table->string('name')->nullable(); // Event name
            $table->string('short_name')->nullable(); // Event short name
            $table->date('game_date');  // Game date
            $table->string('game_status');  // Completed, Scheduled, etc.
            $table->string('game_week');  // Week number or description
            $table->unsignedBigInteger('away_team_id');  // Away team ID
            $table->string('home_team');  // Home team abbreviation
            $table->string('away_result')->nullable();  // Result for away team (W/L)
            $table->string('home_result')->nullable();  // Result for home team (W/L)
            $table->integer('home_pts')->nullable();  // Points scored by the home team
            $table->integer('away_pts')->nullable();  // Points scored by the away team
            $table->string('game_time')->nullable();  // Game time
            $table->unsignedBigInteger('game_time_epoch')->nullable();  // Game time in epoch
            $table->string('game_status_code')->nullable();  // Status code for the game
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('nfl_team_schedules');
    }
}
