<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nba_odds', function (Blueprint $table) {
            $table->id();

            // Basic identifiers
            $table->string('event_id')->index()->comment('ESPN event ID, e.g. 401704651');
            $table->string('opponent_id')->nullable()->comment('If you want to store the away or home team competitor ID as opponent');
            $table->dateTime('event_date')->nullable();

            // The "odds" item’s reference link
            $table->string('odds_ref')->unique()->comment('The $ref from the odds item, ensures no duplicates');

            // Basic odds info
            $table->string('provider_name')->nullable();
            $table->string('details')->nullable();    // e.g. "LAL -1.5"
            $table->decimal('over_under', 5, 1)->nullable();  // e.g. 227.5
            $table->decimal('spread', 5, 1)->nullable();       // e.g. -1.5

            // Away team lines
            $table->string('away_money_line')->nullable();  // e.g. "100"
            $table->string('away_spread_odds')->nullable(); // e.g. "-110"

            // Home team lines
            $table->string('home_money_line')->nullable();  // e.g. "-120"
            $table->string('home_spread_odds')->nullable(); // e.g. "-110"

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nba_odds');
    }
};
