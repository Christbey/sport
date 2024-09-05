<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('college_football_venues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('capacity')->nullable();
            $table->boolean('grass')->default(false);
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('country_code')->nullable();
            $table->string('location')->nullable();
            $table->float('elevation')->nullable();
            $table->integer('year_constructed')->nullable();
            $table->boolean('dome')->default(false);
            $table->string('timezone')->nullable();
            $table->timestamps();
        });

        Schema::create('college_football_teams', function (Blueprint $table) {
            $table->id();
            $table->string('school');
            $table->string('mascot')->nullable();
            $table->string('abbreviation')->nullable();
            $table->text('conference')->nullable();
            $table->text('classification')->nullable();
            $table->text('division')->nullable();

            $table->string('color')->nullable();
            $table->string('alt_color')->nullable();
            $table->json('logos')->nullable();
            $table->string('twitter')->nullable();
            // Location details
            $table->unsignedBigInteger('venue_id')->nullable();
            $table->string('venue_name')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('country_code')->nullable();
            $table->string('timezone')->nullable();
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->string('elevation')->nullable();
            $table->integer('capacity')->nullable();
            $table->integer('year_constructed')->nullable();
            $table->boolean('grass')->nullable();
            $table->boolean('dome')->nullable();
            $table->string('alt_name_1')->nullable();
            $table->string('alt_name_2')->nullable();
            $table->string('alt_name_3')->nullable();
            $table->timestamps();
            $table->foreign('venue_id')->references('id')->on('college_football_venues')->onDelete('set null');
        });

        Schema::create('college_football_coaches', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('hire_date')->nullable();
            $table->integer('seasons')->nullable();
            $table->timestamps();
        });

        Schema::create('college_football_elos', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->unsignedBigInteger('team_id'); // Corrected team_id reference
            $table->foreign('team_id')->references('id')->on('college_football_teams')->onDelete('cascade'); // Foreign key
            $table->string('conference')->nullable();
            $table->float('elo');
            $table->timestamps();
        });

        Schema::create('college_football_fpis', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->unsignedBigInteger('team_id'); // Corrected team_id reference
            $table->foreign('team_id')->references('id')->on('college_football_teams')->onDelete('cascade'); // Foreign key
            $table->string('conference')->nullable();
            $table->float('fpi');
            $table->integer('strength_of_record')->nullable();
            $table->integer('average_win_probability')->nullable();
            $table->integer('strength_of_schedule')->nullable();
            $table->integer('remaining_strength_of_schedule')->nullable();
            $table->integer('game_control')->nullable();
            $table->float('overall')->nullable();
            $table->float('offense')->nullable();
            $table->float('defense')->nullable();
            $table->float('special_teams')->nullable();
            $table->timestamps();
        });

        Schema::create('college_football_games', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary(); // Set the id as the primary key without auto-increment
            $table->integer('season');
            $table->integer('week');
            $table->string('season_type');
            $table->string('start_date');
            $table->boolean('start_time_tbd')->default(false);
            $table->boolean('completed')->default(false);
            $table->boolean('neutral_site')->default(false);
            $table->boolean('conference_game')->default(false);
            $table->integer('attendance')->nullable();
            $table->unsignedBigInteger('venue_id')->nullable();
            $table->string('venue')->nullable();
            $table->unsignedBigInteger('home_id');
            $table->string('home_team');
            $table->string('home_conference')->nullable();
            $table->string('home_division')->nullable();
            $table->integer('home_points')->nullable();
            $table->json('home_line_scores')->nullable();
            $table->float('home_post_win_prob')->nullable();
            $table->integer('home_pregame_elo')->nullable();
            $table->integer('home_postgame_elo')->nullable();
            $table->unsignedBigInteger('away_id');
            $table->string('away_team');
            $table->string('away_conference')->nullable();
            $table->string('away_division')->nullable();
            $table->integer('away_points')->nullable();
            $table->json('away_line_scores')->nullable();
            $table->float('away_post_win_prob')->nullable();
            $table->integer('away_pregame_elo')->nullable();
            $table->integer('away_postgame_elo')->nullable();
            $table->float('excitement_index')->nullable();
            $table->string('highlights')->nullable();
            $table->text('notes')->nullable();
            $table->string('provider')->nullable();
            $table->float('spread')->nullable();
            $table->string('formatted_spread')->nullable();
            $table->float('spread_open')->nullable();
            $table->float('over_under')->nullable();
            $table->float('over_under_open')->nullable();
            $table->float('home_moneyline')->nullable();
            $table->float('away_moneyline')->nullable();
            $table->string('media_type')->nullable();
            $table->string('outlet')->nullable();
            $table->string('start_time')->nullable();
            $table->float('temperature')->nullable();
            $table->float('dew_point')->nullable();
            $table->float('humidity')->nullable();
            $table->float('precipitation')->nullable();
            $table->float('snowfall')->nullable();
            $table->float('wind_direction')->nullable();
            $table->float('wind_speed')->nullable();
            $table->float('pressure')->nullable();
            $table->integer('weather_condition_code')->nullable();
            $table->string('weather_condition')->nullable();
            $table->timestamps();

            $table->foreign('venue_id')->references('id')->on('college_football_venues')->onDelete('set null');
            $table->foreign('home_id')->references('id')->on('college_football_teams')->onDelete('cascade');
            $table->foreign('away_id')->references('id')->on('college_football_teams')->onDelete('cascade');
        });

        Schema::create('college_football_talent', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->string('school');
            $table->float('talent');
            $table->timestamps();
        });

        Schema::create('college_football_pregame', function (Blueprint $table) {
            $table->id();
            $table->integer('season');
            $table->string('season_type');
            $table->integer('week');
            $table->unsignedBigInteger('game_id');
            $table->string('home_team');
            $table->string('away_team');
            $table->float('spread')->nullable();
            $table->float('home_win_prob')->nullable();
            $table->timestamps();

            $table->foreign('game_id')->references('id')->on('college_football_games')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('college_football_pregame');
        Schema::dropIfExists('college_football_talents');
        Schema::dropIfExists('college_football_games');
        Schema::dropIfExists('college_football_fpi');
        Schema::dropIfExists('college_football_elo');
        Schema::dropIfExists('college_football_coaches');
        Schema::dropIfExists('college_football_teams');
        Schema::dropIfExists('college_football_venues');
    }
};
