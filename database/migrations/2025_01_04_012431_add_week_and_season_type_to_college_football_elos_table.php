<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('college_football_elos', function (Blueprint $table) {
            $table->integer('week')->nullable()->after('year');
            $table->string('season_type')->nullable()->after('week');
        });
    }

    public function down()
    {
        Schema::table('college_football_elos', function (Blueprint $table) {
            $table->dropColumn(['week', 'season_type']);
        });
    }
};