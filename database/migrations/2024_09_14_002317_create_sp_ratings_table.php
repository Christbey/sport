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
        Schema::create('sp_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable(); // Nullable if no team is matched
            $table->string('team');
            $table->string('conference')->nullable();
            $table->float('overall_rating');
            $table->integer('ranking');
            $table->integer('offense_ranking')->nullable();
            $table->float('offense_rating')->nullable();
            $table->integer('defense_ranking')->nullable();
            $table->float('defense_rating')->nullable();
            $table->float('special_teams_rating')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sp_ratings');
    }
};
