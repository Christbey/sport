<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content');
            $table->integer('week'); // Changed from 'game_week' for consistency
            $table->integer('season');
            $table->string('away_team', 10);
            $table->string('home_team', 10);
            $table->date('game_date'); // Changed from string to date
            $table->string('game_time'); // Changed from string to time
            $table->string('game_id', 20)->nullable(); // Adjusted data type and made nullable
            $table->longText('prediction')->nullable(); // Made nullable
            $table->boolean('published')->default(false);
            $table->timestamps();

            // Optional Indexes for Performance
            $table->index('week');
            $table->index('season');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['game_id']);
        });
        Schema::dropIfExists('posts');
    }
}
