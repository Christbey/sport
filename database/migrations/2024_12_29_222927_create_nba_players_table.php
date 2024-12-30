<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nba_players', function (Blueprint $table) {
            $table->id(); // Primary key

            // ESPN’s "id" field for the athlete
            $table->string('espn_id')->nullable()->index();

            // Link to the team’s "espn_id" in nba_teams table (not an integer ID, but ESPN’s ID)
            $table->string('team_espn_id')->nullable()->index();

            // Basic athlete info
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('full_name')->nullable();
            $table->string('display_name')->nullable();
            $table->string('slug')->nullable();

            // Position, jersey, height, weight, etc.
            $table->string('position')->nullable();
            $table->string('jersey')->nullable();
            $table->string('height')->nullable();
            $table->string('weight')->nullable();

            // Birthplace
            $table->string('birth_city')->nullable();
            $table->string('birth_state')->nullable();
            $table->string('birth_country')->nullable();

            // Contract details
            $table->unsignedBigInteger('salary')->nullable();
            $table->unsignedBigInteger('salary_remaining')->nullable();
            $table->unsignedInteger('years_remaining')->nullable();
            $table->boolean('contract_active')->default(false);

            // Draft info
            $table->integer('draft_year')->nullable();
            $table->integer('draft_round')->nullable();
            $table->integer('draft_selection')->nullable();

            // Active status
            $table->boolean('is_active')->default(false);

            $table->timestamps();
        });

        // Optional: If you want to add a foreign key constraint referencing
        // nba_teams.espn_id (assuming that field is unique):
        //
        // Schema::table('nba_players', function (Blueprint $table) {
        //     $table->foreign('team_espn_id')
        //           ->references('espn_id')
        //           ->on('nba_teams')
        //           ->cascadeOnUpdate()
        //           ->nullOnDelete();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // If you added a foreign key, drop it before dropping the table, e.g.:
        // Schema::table('nba_players', function (Blueprint $table) {
        //     $table->dropForeign(['team_espn_id']);
        // });

        Schema::dropIfExists('nba_players');
    }
};
