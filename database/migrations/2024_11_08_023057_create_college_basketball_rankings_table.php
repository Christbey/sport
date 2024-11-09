<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('college_basketball_rankings', function (Blueprint $table) {
            $table->id();
            $table->integer('rank');
            $table->foreignId('team_id')->nullable()->constrained('college_basketball_teams')->onDelete('set null');
            $table->string('team');
            $table->string('conference');
            $table->string('record'); // Store as a string to maintain "W-L" format
            $table->decimal('net_rating', 5, 2); // NetRtg as a decimal
            $table->decimal('offensive_rating', 5, 2); // ORtg as a decimal
            $table->decimal('defensive_rating', 5, 2)->nullable(); // DRtg as a decimal, nullable if data is "+.000"
            $table->string('tempo')->nullable(); // Store as string if "N/A" might appear
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_ranks');
    }
};
