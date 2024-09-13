<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNflBettingOddsTable extends Migration
{
    public function up()
    {
        Schema::create('nfl_betting_odds', function (Blueprint $table) {
            $table->id();
            $table->string('event_id');              // ID for the game
            $table->date('game_date');              // Date of the game
            $table->string('away_team');            // Away team
            $table->string('home_team');            // Home team
            $table->integer('away_team_id');        // Away team ID
            $table->integer('home_team_id');        // Home team ID
            $table->string('source');  // Odds source
            $table->decimal('spread_home', 8, 2);   // Home team spread
            $table->decimal('spread_away', 8, 2);   // Away team spread
            $table->decimal('total_over', 8, 2);    // Over total points
            $table->decimal('total_under', 8, 2);   // Under total points
            $table->decimal('moneyline_home', 8, 2); // Home team moneyline
            $table->decimal('moneyline_away', 8, 2); // Away team moneyline
            $table->decimal('implied_total_home', 8, 2); // Home implied total
            $table->decimal('implied_total_away', 8, 2); // Away implied total
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('nfl_betting_odds');
    }
}
