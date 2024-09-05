<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNFLTeamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nfl_teams', function (Blueprint $table) {
            $table->id();
            $table->string('team_abv', 10);
            $table->string('team_city');
            $table->string('team_name');
            $table->string('team_id');
            $table->string('espn_id')->nullable(); // ESPN team ID
            $table->string('uid')->nullable(); // Unique ID from ESPN
            $table->string('slug')->nullable(); // ESPN slug
            $table->string('color', 7)->nullable(); // Primary color
            $table->string('alternate_color', 7)->nullable(); // Alternate color
            $table->string('division');
            $table->string('conference_abv');
            $table->string('conference');
            $table->string('nfl_com_logo1')->nullable();
            $table->string('espn_logo1')->nullable();
            $table->integer('wins')->nullable();
            $table->integer('loss')->nullable();
            $table->integer('tie')->nullable();
            $table->integer('pf')->nullable(); // Points For
            $table->integer('pa')->nullable(); // Points Against
            $table->json('current_streak')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('nfl_teams');
    }
}
