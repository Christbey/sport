<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NbaEvent extends Model
{
    use HasFactory;

    protected $table = 'nba_events';

    protected $fillable = [
        'espn_id',
        'uid',
        'date',
        'name',
        'short_name',
        'venue_name',
        'venue_city',
        'venue_state',
        'home_team_id',
        'away_team_id',
        'home_score',
        'away_score',
        'home_result',
        'away_result',
        'home_linescores',
        'away_linescores',
        'predictor_json',
    ];

    // Let Eloquent cast JSON columns to arrays automatically
    protected $casts = [
        'date' => 'datetime',
        'home_linescores' => 'array',
        'away_linescores' => 'array',
        'predictor_json' => 'array',
        'home_result' => 'boolean',
        'away_result' => 'boolean',
        'home_score' => 'integer',
        'away_score' => 'integer',
    ];
}
