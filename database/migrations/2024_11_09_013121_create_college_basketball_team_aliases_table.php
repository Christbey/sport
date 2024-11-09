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
        Schema::create('college_basketball_team_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('college_basketball_rankings')->onDelete('cascade');
            $table->string('alias');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('college_basketball_team_aliases');
    }
};
