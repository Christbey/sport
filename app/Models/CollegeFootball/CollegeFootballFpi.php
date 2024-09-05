<?php

namespace App\Models\CollegeFootball;

use Illuminate\Database\Eloquent\Model;

class CollegeFootballFpi extends Model
{
    protected $fillable = [
        'year',
        'team',
        'team_id',
        'conference',
        'fpi',
        'strength_of_record',
        'average_win_probability',
        'strength_of_schedule',
        'remaining_strength_of_schedule',
        'game_control',
        'overall',
        'offense',
        'defense',
        'special_teams'
    ];

    public function team()
    {
        return $this->belongsTo(CollegeFootballTeam::class, 'team_id');
    }
}
