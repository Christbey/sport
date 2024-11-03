<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNflNewsTable extends Migration
{
    public function up()
    {
        Schema::create('nfl_news', function (Blueprint $table) {
            $table->id();
            $table->string('link')->unique();
            $table->text('title');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('nfl_news');
    }
}
