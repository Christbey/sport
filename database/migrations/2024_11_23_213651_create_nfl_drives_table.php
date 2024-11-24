<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNflDrivesTable extends Migration
{
    public function up()
    {
        Schema::create('nfl_drives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_id');
            $table->unsignedInteger('team_id');
            $table->unique('drive_number');
            $table->bigInteger('drive_number');
            $table->unsignedTinyInteger('start_quarter');
            $table->string('start_time', 10);
            $table->string('start_yard_line', 10);
            $table->unsignedTinyInteger('end_quarter');
            $table->string('end_time', 10);
            $table->string('end_yard_line', 10);
            $table->unsignedSmallInteger('plays');
            $table->bigInteger('yards');
            $table->string('drive_result', 50);
            $table->boolean('scoring_drive')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('nfl_drives');
    }
}