<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSagarinTable extends Migration
{
    public function up()
    {
        Schema::create('sagarin', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary(); // referencing CollegeFootballTeam's id
            $table->string('team_name');
            $table->decimal('rating', 8, 2);
            $table->timestamps();

            // If needed, add a foreign key constraint to CollegeFootballTeam
            $table->foreign('id')->references('id')->on('college_football_teams')->onDelete('cascade');
        });

    }

    public function down()
    {
        Schema::dropIfExists('sagarin');
    }
}
