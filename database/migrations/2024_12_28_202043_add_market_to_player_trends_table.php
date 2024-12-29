<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMarketToPlayerTrendsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('player_trends', function (Blueprint $table) {
            $table->string('market')->after('odds_api_id')->nullable(); // Add the market column
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('player_trends', function (Blueprint $table) {
            $table->dropColumn('market'); // Drop the market column if rolled back
        });
    }
}
