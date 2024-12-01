<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRefereesToNflTeamSchedulesTable extends Migration
{
    public function up()
    {
        Schema::table('nfl_team_schedules', function (Blueprint $table) {
            $table->json('referees')->nullable()->after('game_week');
        });
    }

    public function down()
    {
        Schema::table('nfl_team_schedules', function (Blueprint $table) {
            $table->dropColumn('referees');
        });
    }
}
