<?php

namespace App\Models;

use App\Models\Nfl\NflTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NflTrend extends Model
{
    use HasFactory;

    // Table associated with the model
    protected $table = 'nfl_trends';

    // Mass assignable attributes
    protected $fillable = [
        'team_id',
        'opponent_id',
        'team_abbr',
        'week',
        'game_date',
        'trend_type',
        'trend_text',
        'occurred',
        'total_games',
        'percentage',
    ];

    /**
     * Relationship: Team
     * Defines the relationship between the trend and the team it belongs to.
     */
    public function team()
    {
        return $this->belongsTo(NflTeam::class, 'team_id', 'id');
    }

    /**
     * Relationship: Opponent
     * Defines the relationship between the trend and the opposing team.
     */
    public function opponent()
    {
        return $this->belongsTo(NflTeam::class, 'opponent_id', 'id');
    }

}
