<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NbaPropBet extends Model
{
    use HasFactory;

    protected $table = 'nba_prop_bets';

    protected $fillable = [
        'event_id',
        'opponent_id',
        'event_date',
        'athlete_id',
        'athlete_name',
        'prop_type',
        'total',
        'current_over',
        'current_target',
    ];

    protected $casts = [
        'total' => 'float',
        'current_target' => 'float',
    ];

    // Define relationship with player
    public function player()
    {
        return $this->belongsTo(NbaPlayer::class, 'athlete_id', 'espn_id');
    }
}
