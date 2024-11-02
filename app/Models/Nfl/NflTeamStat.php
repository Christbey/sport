<?php

namespace App\Models\Nfl;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NflTeamStat extends Model
{
    use HasFactory;

    // The table associated with the model
    protected $table = 'nfl_team_stats';

    // Fillable fields
    protected $fillable = [
        'game_id',
        'team_id',
        'team_abv',
        'total_yards',
        'rushing_yards',
        'passing_yards',
        'points_allowed',
    ];

    // Relationships
    public function game()
    {
        return $this->belongsTo(NflBoxScore::class, 'game_id', 'game_id');
    }
}
