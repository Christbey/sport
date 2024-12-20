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
    protected $casts = [
        'game_date' => 'date', // Ensures game_date is treated as a Carbon instance
        'is_completed' => 'boolean',
        'hotness_score' => 'float',
        'home_rank' => 'integer',
        'away_rank' => 'integer',
        'home_team_score' => 'integer',
        'away_team_score' => 'integer',
        'rebounds' => 'integer',
        'assists' => 'integer',
        'turnovers' => 'integer',
        'steals' => 'integer',
        'blocks' => 'integer',
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