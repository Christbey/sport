<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create nfl_box_scores table
        Schema::create('nfl_box_scores', function (Blueprint $table) {
            $table->id();
            $table->string('game_id')->unique();
            $table->string('home_team');
            $table->string('away_team');
            $table->integer('home_points')->default(0);
            $table->integer('away_points')->default(0);
            $table->date('game_date');
            $table->string('location')->nullable();
            $table->json('home_line_score')->nullable();
            $table->json('away_line_score')->nullable();
            $table->string('away_result')->nullable();   // New field for awayResult
            $table->string('home_result')->nullable();   // New field for homeResult
            $table->string('game_status')->nullable();   // New field for gameStatus
            $table->timestamps();
        });

        // Create nfl_player_stats table
        Schema::create('nfl_player_stats', function (Blueprint $table) {
            $table->id();
            $table->string('game_id');
            $table->string('player_id');
            $table->string('team_id');
            $table->string('long_name')->nullable();
            $table->string('team_abv');
            $table->json('receiving')->nullable();
            $table->json('rushing')->nullable();
            $table->json('kicking')->nullable();
            $table->json('punting')->nullable();
            $table->json('defense')->nullable();
            $table->timestamps();

            // Add foreign key constraints
            $table->foreign('game_id')->references('game_id')->on('nfl_box_scores')->onDelete('cascade');
        });

        // Create nfl_team_stats table
        Schema::create('nfl_team_stats', function (Blueprint $table) {
            $table->id();
            $table->string('game_id');
            $table->string('team_id');
            $table->string('team_abv');
            $table->integer('total_yards')->nullable();
            $table->integer('rushing_yards')->nullable();
            $table->integer('passing_yards')->nullable();
            $table->integer('points_allowed')->nullable();
            $table->timestamps();

            // Add foreign key constraints
            $table->foreign('game_id')->references('game_id')->on('nfl_box_scores')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nfl_team_stats');
        Schema::dropIfExists('nfl_player_stats');
        Schema::dropIfExists('nfl_box_scores');
    }
};
