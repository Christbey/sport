<?php

namespace App\Models\CollegeFootball;

use Illuminate\Database\Eloquent\Model;

class CollegeFootballVenue extends Model
{
    protected $fillable = [
        'name',
        'capacity',
        'grass',
        'city',
        'state',
        'zip',
        'country_code',
        'location',
        'elevation',
        'year_constructed',
        'dome',
        'timezone'
    ];

    public function teams()
    {
        return $this->hasMany(CollegeFootballTeam::class, 'venue_id');
    }

    public function games()
    {
        return $this->hasMany(CollegeFootballGame::class, 'venue_id');
    }
}
