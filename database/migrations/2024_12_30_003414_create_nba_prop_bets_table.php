<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nba_prop_bets', function (Blueprint $table) {
            $table->id();

            // Basic fields
            $table->string('event_id')->index();
            $table->string('opponent_id')->nullable(); // if you want to store which team they face
            $table->dateTime('event_date')->nullable();

            // The $ref from the 'athlete' link or the ID we parse
            $table->string('athlete_id')->nullable();
            $table->string('athlete_name')->nullable();  // or store the name if you have it
            $table->string('prop_type')->nullable();     // e.g. "Total Points"

            // The "target" or "total" (like 22.5)
            $table->decimal('total', 5, 1)->nullable();

            // The current Over line (like "-130" or "Even")
            $table->string('current_over')->nullable();
            // The current "target" if we want
            $table->decimal('current_target', 5, 1)->nullable();

            // The $ref from the prop bet item if you want to store it.
            // You can also make it unique if you prefer:
            $table->string('prop_ref')->nullable()->unique()->comment('The $ref from the ESPN prop bet');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nba_prop_bets');
    }
};
