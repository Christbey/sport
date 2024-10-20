<?php

namespace App\Models\Nfl;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NflBettingOdds extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'game_date',
        'away_team',
        'home_team',
        'away_team_id',
        'home_team_id',
        'source',
        'spread_home',
        'spread_away',
        'total_over',
        'total_under',
        'moneyline_home',
        'moneyline_away',
        'implied_total_home',
        'implied_total_away',
    ];
}
