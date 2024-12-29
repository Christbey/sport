<?php

namespace App\Models\Nfl;

use Illuminate\Database\Eloquent\Model;

class PlayerTrend extends Model
{
    protected $fillable = [
        'player',
        'point',
        'over_count',
        'under_count',
        'game_id',
        'odds_api_id',
        'season',
        'market',
        'week'
    ];

    protected $casts = [
        'point' => 'float',
        'over_count' => 'integer',
        'under_count' => 'integer',
        'week' => 'integer'
    ];

    public function oddsApi()
    {
        return $this->belongsTo(OddsApiNfl::class, 'odds_api_id', 'event_id');
    }

    public function scopeSeason($query, $season)
    {
        return $query->where('season', $season);
    }

    public function scopeWeek($query, $week)
    {
        return $query->where('week', $week);
    }
}