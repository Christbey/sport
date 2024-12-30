<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NbaOdds extends Model
{
    use HasFactory;

    protected $table = 'nba_odds';

    protected $fillable = [
        'event_id',
        'opponent_id',
        'event_date',
        'odds_ref',
        'provider_name',
        'details',
        'over_under',
        'spread',
        'away_money_line',
        'away_spread_odds',
        'home_money_line',
        'home_spread_odds',
    ];

    // If you want to cast numeric columns
    protected $casts = [
        'over_under' => 'float',
        'spread' => 'float',
    ];
}
