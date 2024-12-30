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
        Schema::create('nba_teams', function (Blueprint $table) {
            $table->id(); // Primary key (auto-increment)

            // We store ESPN-specific identifiers and other fields
            $table->string('espn_id')->nullable();        // "id" from ESPN
            $table->string('guid')->nullable();
            $table->string('uid')->nullable();
            $table->string('slug')->nullable();
            $table->string('location')->nullable();
            $table->string('name')->nullable();
            $table->string('abbreviation')->nullable();
            $table->string('display_name')->nullable();
            $table->string('short_display_name')->nullable();
            $table->string('color')->nullable();
            $table->string('alternate_color')->nullable();
            $table->boolean('is_active')->default(false);

            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nba_teams');
    }
};
