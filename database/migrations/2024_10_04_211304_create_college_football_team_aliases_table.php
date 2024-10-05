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
        Schema::create('college_football_team_aliases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');  // References the main college football team
            $table->string('alias_name');           // The alternative team name
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('team_id')->references('id')->on('college_football_teams')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('college_football_team_aliases');
    }

};
