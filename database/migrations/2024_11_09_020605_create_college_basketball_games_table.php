<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollegeBasketballGamesTable extends Migration
{
    public function up()
    {
        Schema::create('college_basketball_games', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id')->unique()->nullable(); // Unique ESPN event ID
            $table->string('event_uid')->unique()->nullable(); // ESPN unique event identifier
            $table->integer('attendance')->nullable(); // Attendance for the game
            $table->float('hotness_score')->nullable(); // Hotness score
            $table->string('matchup')->nullable(); // Matchup details (e.g., "#184 Georgia St at #37 Miss State")
            $table->integer('home_rank')->nullable();
            $table->foreignId('home_team_id')->constrained('college_basketball_teams')->onDelete('cascade');
            $table->string('home_team')->nullable(); // Location of the game
            $table->integer('away_rank')->nullable();
            $table->foreignId('away_team_id')->constrained('college_basketball_teams')->onDelete('cascade');
            $table->string('away_team')->nullable(); // Location of the game
            $table->date('game_date'); // Game date and time
            $table->string('location')->nullable(); // Location of the game
            $table->integer('home_team_score')->nullable(); // Score for home team
            $table->integer('away_team_score')->nullable(); // Score for away team
            $table->boolean('is_completed')->default(false); // Status of the game
            $table->string('game_time')->nullable(); // Game time as string (e.g., "7:00 PM")
            $table->timestamps();
        });
    }


    public function down()
    {
        Schema::dropIfExists('college_basketball_games');
    }
}