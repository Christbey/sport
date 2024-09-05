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
        Schema::create('user_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // References the users table

            // Assuming espn_event_id references the id in the nfl_team_schedule table
            $table->foreignId('espn_event_id')->constrained('nfl_team_schedules')->onDelete('cascade');

            // Assuming week_id is not a foreign key but an integer
            $table->integer('week_id');

            // Store team_id as an unsigned big integer without a foreign key
            $table->unsignedBigInteger('team_id');

            // Boolean to track if the user's pick was correct
            $table->boolean('is_correct')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_submissions');
    }
};
