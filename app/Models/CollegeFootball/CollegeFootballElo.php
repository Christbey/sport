<?php

namespace App\Models\CollegeFootball;

use Illuminate\Database\Eloquent\Model;

class CollegeFootballElo extends Model
{
    protected $fillable = [
        'year',
        'team',
        'team_id',
        'conference',
        'elo'
    ];

    public function team()
    {
        return $this->belongsTo(CollegeFootballTeam::class, 'team_id');
    }
}
