<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNflPlayPlayers extends Migration
{
    public function up()
    {
        Schema::create('nfl_play_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('play_id')->constrained('nfl_plays')->onDelete('cascade');
            $table->string('player_id');
            $table->string('player_name')->nullable();
            $table->string('role');
            $table->string('team_id');
            $table->timestamps();
            // Add unique constraint
        });
    }

    public function down()
    {
        Schema::dropIfExists('nfl_play_players');
    }
}

