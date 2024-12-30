<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NbaPlayerStat extends Model
{
    use HasFactory;

    protected $table = 'nba_player_stats';

    protected $fillable = [
        'event_id',
        'team_id',
        'player_id',
        'opponent_id',
        'competition_ref',
        'athlete_ref',
        'splits_json',
        'event_date',
    ];

    protected $casts = [
        'splits_json' => 'array', // Laravel will treat this as a PHP array automatically
    ];

    public function player()
    {
        return $this->belongsTo(NbaPlayer::class, 'player_id', 'espn_id');
    }
}
