<?php

namespace App\Models\CollegeFootball;

use Illuminate\Database\Eloquent\Model;

class CollegeFootballPregame extends Model
{
    protected $fillable = [
        'season',
        'season_type',
        'week',
        'game_id',
        'home_team',
        'away_team',
        'spread',
        'home_win_prob'
    ];

    public function game()
    {
        return $this->belongsTo(CollegeFootballGame::class, 'game_id');
    }
}
