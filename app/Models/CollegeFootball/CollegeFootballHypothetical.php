<?php

namespace App\Models\CollegeFootball;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollegeFootballHypothetical extends Model
{
    use HasFactory;

    protected $table = 'college_football_hypotheticals';

    protected $fillable = [
        'game_id',
        'week',
        'home_team_id',
        'away_team_id',
        'home_team_school',
        'away_team_school',
        'home_elo',
        'away_elo',
        'home_fpi',
        'away_fpi',
        'hypothetical_spread',
        'correct',
    ];

    protected $casts = [
        'is_prediction_correct' => 'boolean',
    ];

    /**
     * Get the game associated with the hypothetical spread.
     */
    public function game()
    {
        return $this->belongsTo(CollegeFootballGame::class, 'game_id');
    }

    /**
     * Scope a query to only include FBS games.
     */
    public function scopeFbsGames($query)
    {
        return $query->where('home_division', 'fbs')
            ->where('away_division', 'fbs');
    }

    
}