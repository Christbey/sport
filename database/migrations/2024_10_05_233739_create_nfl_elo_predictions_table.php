<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNflEloPredictionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nfl_elo_predictions', function (Blueprint $table) {
            $table->id();
            $table->string('team'); // Team abbreviation
            $table->string('opponent'); // Opponent team abbreviation
            $table->integer('year');
            $table->string('week');
            $table->double('team_elo');
            $table->double('opponent_elo');
            $table->double('expected_outcome');
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
        Schema::dropIfExists('nfl_elo_predictions');
    }
}
