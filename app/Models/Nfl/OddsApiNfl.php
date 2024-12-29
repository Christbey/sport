<?php

namespace App\Models\Nfl;

use Illuminate\Database\Eloquent\Model;

class OddsApiNfl extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    protected $table = 'odds_api_nfl';
    protected $primaryKey = 'event_id';
    protected $keyType = 'string';
    protected $fillable = [
        'event_id',
        'sport',
        'datetime',
        'home_team',
        'away_team',
        'source'
    ];


    protected $casts = [
        'datetime' => 'datetime'
    ];

    public function playerTrends()
    {
        return $this->hasMany(PlayerTrend::class, 'odds_api_id', 'event_id');
    }
}

