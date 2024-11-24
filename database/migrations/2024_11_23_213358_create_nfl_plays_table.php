<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNflPlaysTable extends Migration
{
    public function up()
    {
        Schema::create('nfl_plays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_id');
            $table->string('team_id');
            $table->unsignedBigInteger('drive_id');
            $table->unsignedBigInteger('play_id');
            $table->unique('play_id');
            $table->unsignedTinyInteger('quarter');
            $table->string('time', 10);
            $table->unsignedTinyInteger('down')->nullable();
            $table->unsignedTinyInteger('distance')->nullable();
            $table->string('yard_line', 10)->nullable();
            $table->text('description');
            $table->string('play_type', 50);
            $table->smallInteger('yards_gained');
            $table->boolean('first_down')->default(false);
            $table->boolean('touchdown')->default(false);
            $table->boolean('turnover')->default(false);
            $table->decimal('epa', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('plays');
    }
}