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
        Schema::table('college_basketball_games', function (Blueprint $table) {
            $table->string('short_name')->nullable();
            $table->integer('season_year')->nullable();
            $table->integer('season_type')->nullable();
            $table->string('season_slug')->nullable();
        });
    }

    public function down()
    {
        Schema::table('college_basketball_games', function (Blueprint $table) {
            $table->dropColumn(['short_name', 'season_year', 'season_type', 'season_slug']);
        });
    }

};
