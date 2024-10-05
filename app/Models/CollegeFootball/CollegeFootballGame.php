<?php

namespace App\Models\CollegeFootball;

use Illuminate\Database\Eloquent\Model;

class CollegeFootballGame extends Model
{
    public $incrementing = false; // Disable auto-incrementing for the id field
    protected $keyType = 'unsignedBigInteger'; // Ensure the key type is unsigned big integer
    protected $fillable = [
        'id',
        'season',
        'week',
        'season_type',
        'start_date',
        'start_time_tbd',
        'completed',
        'neutral_site',

        'conference_game',
        'attendance',
        'venue_id',
        'venue',
        'home_id',
        'home_team',
        'home_conference',
        'home_division',
        'home_points',
        'home_line_scores',
        'home_post_win_prob',
        'home_pregame_elo',
        'home_postgame_elo',
        'away_id',
        'away_team',
        'away_conference',
        'away_division',
        'away_points',
        'away_line_scores',
        'away_post_win_prob',
        'away_pregame_elo',
        'away_postgame_elo',
        'excitement_index',
        'highlights',
        'notes',
        'provider',
        'spread',
        'formatted_spread',
        'spread_open',
        'over_under',
        'over_under_open',
        'home_moneyline',
        'away_moneyline',
        'media_type',
        'outlet',
        'start_time',
        'temperature',
        'dew_point',
        'humidity',
        'precipitation',
        'snowfall',
        'wind_direction',
        'wind_speed',
        'pressure',
        'weather_condition_code',
        'weather_condition'
    ];

    public function venue()
    {
        return $this->belongsTo(CollegeFootballVenue::class, 'venue_id');
    }

    public function homeTeam()
    {
        return $this->belongsTo(CollegeFootballTeam::class, 'home_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(CollegeFootballTeam::class, 'away_id');
    }


    public function pregame()
    {
        return $this->hasOne(CollegeFootballPregame::class, 'game_id');
    }
}
