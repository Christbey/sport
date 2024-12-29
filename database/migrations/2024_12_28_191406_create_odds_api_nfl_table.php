<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOddsApiNflTable extends Migration
{
    public function up()
    {
        Schema::create('odds_api_nfl', function (Blueprint $table) {
            $table->string('event_id')->primary();
            $table->string('sport');
            $table->timestamp('datetime');
            $table->string('home_team');
            $table->string('away_team');
            $table->json('source')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('odds_api_nfl');
    }
}
