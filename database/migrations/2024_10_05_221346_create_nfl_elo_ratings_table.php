<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNflEloRatingsTable extends Migration
{
    public function up()
    {
        Schema::create('nfl_elo_ratings', function (Blueprint $table) {
            $table->id();
            $table->string('team', 10);
            $table->integer('year');
            $table->float('final_elo');
            $table->float('expected_wins');
            $table->float('predicted_spread');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('nfl_elo_ratings');
    }
}
