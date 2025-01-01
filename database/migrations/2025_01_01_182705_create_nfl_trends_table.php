<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNflTrendsTable extends Migration
{
    public function up()
    {
        Schema::create('nfl_trends', function (Blueprint $table) {
            $table->id();
            $table->integer('team_id');
            $table->integer('opponent_id');
            $table->string('team_abbr');

            $table->integer('week');
            $table->date('game_date');
            $table->string('trend_type');
            $table->text('trend_text');
            $table->integer('occurred');
            $table->integer('total_games');
            $table->float('percentage');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('nfl_trends');
    }


}
