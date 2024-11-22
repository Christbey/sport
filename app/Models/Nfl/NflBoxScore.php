<?php

namespace App\Models\Nfl;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NflBoxScore extends Model
{
    use HasFactory;

    // The table associated with the model
    protected $table = 'nfl_box_scores';

    // Fillable fields
    protected $fillable = [
        'game_id',
        'home_team',
        'away_team',
        'home_points',
        'away_points',
        'game_date',
        'location',
        'home_line_score',
        'away_line_score',
        'away_result',
        'home_result',           // Added for home result
        'game_status',           // Added for game status
    ];

    // Cast fields to appropriate types
    protected $casts = [
        'home_line_score' => 'array',   // Cast as array since line score might be stored in JSON format
        'away_line_score' => 'array',   // Same for away line score
    ];

    // Relationships
    public function playerStats()
    {
        return $this->hasMany(NflPlayerStat::class, 'game_id', 'game_id');
    }

    public function teamStats()
    {
        return $this->hasMany(NflTeamStat::class, 'game_id', 'game_id');
    }

    public function bettingOdds()
    {
        return $this->hasOne(NflBettingOdds::class, 'event_id', 'game_id');
    }
}
