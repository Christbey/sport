<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollegeBasketballGame extends Model
{
    use HasFactory;

    protected $fillable = [
        'home_team_id',
        'away_team_id',
        'game_date',
        'game_time',
        'location',
        'hotness_score',
        'matchup',
        'home_rank',
        'away_rank',
        'home_team_score',
        'away_team_score',
        'is_completed',
        'home_team',
        'away_team',
    ];

    public function homeTeam()
    {
        return $this->belongsTo(CollegeBasketballTeam::class, 'home_team_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(CollegeBasketballTeam::class, 'away_team_id');
    }
}