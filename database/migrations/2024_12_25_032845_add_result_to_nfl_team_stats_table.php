<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddResultToNflTeamStatsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('nfl_team_stats', function (Blueprint $table) {
            $table->char('result', 1)->nullable()->after('points_allowed')
                ->comment('W for Win, L for Loss, T for Tie');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nfl_team_stats', function (Blueprint $table) {
            $table->dropColumn('result');
        });
    }
}
