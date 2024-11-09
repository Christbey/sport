<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollegeBasketballHypotheticalsTable extends Migration
{
    public function up()
    {
        Schema::create('college_basketball_hypotheticals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('college_basketball_games')->onDelete('cascade'); // Game ID
            $table->foreignId('home_id')->constrained('college_basketball_teams')->onDelete('cascade'); // Home team ID
            $table->foreignId('away_id')->constrained('college_basketball_teams')->onDelete('cascade'); // Away team ID
            $table->date('game_date'); // Date of the game
            $table->string('home_team'); // Name of the home team
            $table->string('away_team'); // Name of the away team
            $table->decimal('hypothetical_spread', 5, 2)->nullable(); // Hypothetical spread (away - home)
            $table->decimal('offense_difference', 5, 2)->nullable(); // Offense rating difference
            $table->decimal('defense_difference', 5, 2)->nullable(); // Defense rating difference
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('college_basketball_hypotheticals');
    }
}