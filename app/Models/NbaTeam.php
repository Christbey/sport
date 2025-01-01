<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NbaTeam extends Model
{
    use HasFactory;

    protected $table = 'nba_teams'; // Table name

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'espn_id',
        'guid',
        'uid',
        'slug',
        'location',
        'name',
        'abbreviation',
        'display_name',
        'short_display_name',
        'color',
        'alternate_color',
        'is_active',
    ];

    public function trendsAsTeam()
    {
        return $this->hasMany(NflTrend::class, 'team_id');
    }

    /**
     * Relationship: Trends as an opponent
     */
    public function trendsAsOpponent()
    {
        return $this->hasMany(NflTrend::class, 'opponent_id');
    }
}
