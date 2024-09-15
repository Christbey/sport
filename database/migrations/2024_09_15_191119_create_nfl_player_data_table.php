<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNflPlayerDataTable extends Migration
{
    public function up()
    {
        Schema::create('nfl_player_data', function (Blueprint $table) {
            $table->id();
            $table->string('playerID')->unique();
            $table->string('fantasyProsLink')->nullable();
            $table->string('jerseyNum')->nullable();
            $table->string('espnName')->nullable();
            $table->string('cbsLongName')->nullable();
            $table->string('yahooLink')->nullable();
            $table->string('sleeperBotID')->nullable();
            $table->string('fantasyProsPlayerID')->nullable();
            $table->string('lastGamePlayed')->nullable();
            $table->string('espnLink')->nullable();
            $table->string('yahooPlayerID')->nullable();
            $table->boolean('isFreeAgent')->default(false);
            $table->string('pos')->nullable();
            $table->string('school')->nullable();
            $table->integer('teamID')->nullable();
            $table->string('cbsShortName')->nullable();
            $table->string('injury_return_date')->nullable();
            $table->string('injury_description')->nullable();
            $table->string('injury_date')->nullable();
            $table->string('injury_designation')->nullable();
            $table->string('rotoWirePlayerIDFull')->nullable();
            $table->string('rotoWirePlayerID')->nullable();
            $table->integer('exp')->nullable();
            $table->string('height')->nullable();
            $table->string('espnHeadshot')->nullable();
            $table->string('fRefID')->nullable();
            $table->integer('weight')->nullable();
            $table->string('team')->nullable();
            $table->string('espnIDFull')->nullable();
            $table->string('bDay')->nullable();
            $table->integer('age')->nullable();
            $table->string('longName')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('nfl_player_data');
    }
}
