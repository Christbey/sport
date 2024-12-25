<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPredictionColumnsToPosts extends Migration
{
    public function up()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('predicted_winner')->nullable();
            $table->string('predicted_score')->nullable();
            $table->string('predicted_spread')->nullable();
            $table->string('predicted_over_under')->nullable();
            $table->string('prediction_confidence')->nullable();
        });
    }

    public function down()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn([
                'predicted_winner',
                'predicted_score',
                'predicted_spread',
                'predicted_over_under',
                'prediction_confidence'
            ]);
        });
    }
}