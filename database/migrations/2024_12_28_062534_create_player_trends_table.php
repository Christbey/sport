<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlayerTrendsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('player_trends', function (Blueprint $table) {
            $table->id();
            $table->string('player'); // Player's name
            $table->float('point'); // Point value for over/under
            $table->integer('over_count'); // Number of times the player went over the point
            $table->integer('under_count'); // Number of times the player went under the point
            $table->string('game_id'); // Associated game ID
            $table->string('odds_api_id'); // ID from the Odds API (non-unique)
            $table->string('season'); // Season year
            $table->integer('week')->nullable(); // Week number of the game
            $table->timestamps(); // Created and updated timestamps
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('player_trends');
    }
}
