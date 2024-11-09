<?php

// database/migrations/xxxx_xx_xx_create_college_basketball_teams_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollegeBasketballTeamsTable extends Migration
{
    public function up()
    {
        Schema::create('college_basketball_teams', function (Blueprint $table) {
            $table->id();
            $table->string('team_id')->unique();
            $table->string('uid');
            $table->string('slug');
            $table->string('abbreviation');
            $table->string('display_name');
            $table->string('short_display_name');
            $table->string('name');
            $table->string('nickname');
            $table->string('location');
            $table->string('color');
            $table->string('alternate_color')->nullable();
            $table->boolean('is_active');
            $table->boolean('is_all_star');
            $table->string('logo_url')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('college_basketball_teams');
    }
}