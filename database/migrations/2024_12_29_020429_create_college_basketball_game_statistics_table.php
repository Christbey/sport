<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollegeBasketballGameStatisticsTable extends Migration
{
    public function up()
    {
        Schema::create('college_basketball_game_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('college_basketball_games')->onDelete('cascade');
            $table->enum('team_type', ['home', 'away']); // To differentiate stats for home/away teams
            $table->integer('rebounds')->nullable();
            $table->integer('assists')->nullable();
            $table->integer('field_goals_attempted')->nullable();
            $table->integer('field_goals_made')->nullable();
            $table->float('field_goal_percentage')->nullable();
            $table->float('free_throw_percentage')->nullable();
            $table->integer('free_throws_attempted')->nullable();
            $table->integer('free_throws_made')->nullable();
            $table->integer('points')->nullable();
            $table->integer('three_point_field_goals_attempted')->nullable();
            $table->integer('three_point_field_goals_made')->nullable();
            $table->float('three_point_field_goal_percentage')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('college_basketball_game_statistics');
    }
}
