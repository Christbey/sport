<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nba_player_stats', function (Blueprint $table) {
            $table->id();

            // Basic identifiers
            $table->string('event_id')->comment('ESPN event ID, e.g. 401584703');
            $table->string('team_id')->comment('Which competitor/team this player is on, e.g. 20');
            $table->string('player_id')->comment('Athlete ESPN ID, e.g. 3059318');
            $table->string('opponent_id')->comment('The other competitor/team in this event');

            // Store the event’s date/time
            $table->dateTime('event_date')->nullable()->comment('Date/time of this event');

            // ESPN references
            $table->string('competition_ref')->nullable();
            $table->string('athlete_ref')->nullable();

            // The full block of stats in JSON
            $table->json('splits_json')->nullable();

            // Avoid duplicates: one row per event+player
            $table->unique(['event_id', 'player_id'], 'unique_event_player');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nba_player_stats');
    }
};
