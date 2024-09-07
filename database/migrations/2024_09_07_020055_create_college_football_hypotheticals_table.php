<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollegeFootballHypotheticalsTable extends Migration
{
    public function up()
    {
        Schema::create('college_football_hypotheticals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_id');
            $table->integer('week'); // Add 'week' after 'game_id'
            $table->string('home_team_school');
            $table->unsignedBigInteger('home_team_id');
            $table->string('away_team_school');
            $table->unsignedBigInteger('away_team_id');
            $table->integer('home_elo');
            $table->integer('away_elo');
            $table->float('home_fpi', 8, 2);
            $table->float('away_fpi', 8, 2);
            $table->float('hypothetical_spread', 8, 2);
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('game_id')->references('id')->on('college_football_games')->onDelete('cascade');
            $table->foreign('home_team_id')->references('id')->on('college_football_teams')->onDelete('cascade');
            $table->foreign('away_team_id')->references('id')->on('college_football_teams')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('college_football_hypotheticals');
    }
}