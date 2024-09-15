<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

        public function up()
    {
        Schema::create('nfl_sheet', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('game_id')->nullable();

            $table->unsignedBigInteger('user_id'); // The user who made the note
            $table->text('user_inputted_notes')->nullable();
            $table->integer('fezzik_rankings')->nullable();
            $table->integer('elo_rankings')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('team_id')->references('id')->on('nfl_teams')->onDelete('cascade');
            $table->foreign('game_id')->references('id')->on('nfl_team_schedules')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nfl_sheet');
    }
};
