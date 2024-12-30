<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nba_events', function (Blueprint $table) {
            $table->id();

            // Basic ESPN fields
            $table->string('espn_id')->unique(); // e.g. "401584691"
            $table->string('uid')->nullable();   // e.g. "s:40~l:46~e:401584691"
            $table->dateTime('date')->nullable();
            $table->string('name')->nullable();        // e.g. "Atlanta Hawks at Charlotte Hornets"
            $table->string('short_name')->nullable();  // e.g. "ATL @ CHA"

            // Venue
            $table->string('venue_name')->nullable();  // e.g. "Spectrum Center"
            $table->string('venue_city')->nullable();  // e.g. "Charlotte"
            $table->string('venue_state')->nullable(); // e.g. "NC"

            // Home / Away teams
            // e.g. "Team ID: 30" (home), "Team ID: 1" (away)
            $table->string('home_team_id')->nullable();
            $table->string('away_team_id')->nullable();

            // Final scores
            $table->unsignedSmallInteger('home_score')->nullable();
            $table->unsignedSmallInteger('away_score')->nullable();

            // Results (did they win?), we can store booleans or strings
            $table->boolean('home_result')->default(false); // e.g. True if winner
            $table->boolean('away_result')->default(false);

            // Linescores as separate JSON columns
            $table->json('home_linescores')->nullable(); // e.g. [ {"period":1,"points":25}, ... ]
            $table->json('away_linescores')->nullable(); // e.g. [ {"period":1,"points":29}, ... ]

            // Predictor data (entire object or partial)
            $table->json('predictor_json')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nba_events');
    }
};
