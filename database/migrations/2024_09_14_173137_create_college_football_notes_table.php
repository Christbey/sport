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
        Schema::create('college_football_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_id');
            $table->unsignedBigInteger('team_id');
            $table->text('note');
            $table->timestamps();

            $table->foreign('game_id')->references('id')->on('college_football_games')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('college_football_teams')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('college_football_notes');
    }
};
