<?php

namespace App\Models\CollegeFootball;

use Illuminate\Database\Eloquent\Model;

class CollegeFootballTeam extends Model
{
    protected $fillable = [
        'school',
        'mascot',
        'abbreviation',
        'conference',
        'classification',
        'color',
        'alt_color',
        'logos',
        'twitter',
        'venue_id',
        'venue_name',
        'city',
        'state',
        'zip',
        'country_code',
        'timezone',
        'latitude',
        'longitude',
        'elevation',
        'capacity',
        'year_constructed',
        'grass',
        'dome',
        'alt_name_1',
        'alt_name_2',
        'alt_name_3',
    ];

    protected $casts = [
        'logos' => 'array',  // Automatically cast the JSON to an array
        'grass' => 'boolean', // Automatically cast to boolean
        'dome' => 'boolean',  // Automatically cast to boolean
        'latitude' => 'float', // Automatically cast to float
        'longitude' => 'float', // Automatically cast to float
    ];

    public function venue()
    {
        return $this->belongsTo(CollegeFootballVenue::class, 'venue_id');
    }

    public function homeGames()
    {
        return $this->hasMany(CollegeFootballGame::class, 'home_id');
    }

    public function awayGames()
    {
        return $this->hasMany(CollegeFootballGame::class, 'away_id');
    }

    public function fpi()
    {
        return $this->hasMany(CollegeFootballFpi::class, 'team_id');
    }

    public function elo()
    {
        return $this->hasMany(CollegeFootballElo::class, 'team_id');
    }

}
