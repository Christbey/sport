<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNflDepthChartsTable extends Migration
{
    public function up()
    {
        Schema::create('nfl_depth_charts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('team_name')->nullable();
            $table->string('position');
            $table->unsignedBigInteger('player_id');
            $table->string('player_name');
            $table->integer('depth_order')->nullable();
            $table->timestamps();

            // Unique constraint to prevent duplicates
            $table->unique(['team_id', 'position', 'player_id'], 'unique_depth_chart');
        });
    }

    public function down()
    {
        Schema::dropIfExists('nfl_depth_charts');
    }
}
