<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nba_team_stats', function (Blueprint $table) {
            $table->id();

            // Basic identifiers
            $table->string('event_id')->comment('ESPN event ID, e.g. 401584703');
            $table->string('team_id')->comment('Team competitor ID in this event, e.g. 15');
            $table->string('opponent_id')->nullable()->comment('The other competitor/team in this event');

            // Store the event’s date/time
            $table->dateTime('event_date')->nullable()->comment('Date/time of this event');

            // Store entire "splits" object as JSON
            $table->json('splits_json')->nullable();

            // Optional ESPN references
            $table->string('team_ref')->nullable();
            $table->string('competition_ref')->nullable();

            // Ensure we only have one record per (event_id, team_id)
            $table->unique(['event_id', 'team_id'], 'unique_event_team');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nba_team_stats');
    }
};
