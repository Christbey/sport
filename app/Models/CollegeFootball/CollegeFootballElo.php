<?php

namespace App\Models\CollegeFootball;

use Illuminate\Database\Eloquent\Model;

class CollegeFootballElo extends Model
{
    protected $fillable = [
        'team_id',
        'year',
        'week',
        'season_type',
        'team',
        'conference',
        'elo'
    ];

    public function team()
    {
        return $this->belongsTo(CollegeFootballTeam::class, 'team_id');
    }
}
