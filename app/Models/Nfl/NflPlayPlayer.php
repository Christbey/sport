<?php

namespace App\Models\Nfl;

use Illuminate\Database\Eloquent\Model;

class NflPlayPlayer extends Model
{
    protected $table = 'nfl_play_players';

    protected $fillable = [
        'play_id',
        'player_name',
        'player_id',
        'role',
        'team_id'
    ];

    // Relationship with NflPlay
    public function play()
    {
        return $this->belongsTo(NflPlay::class, 'play_id');
    }

    // Relationship with Team
    public function team()
    {
        return $this->belongsTo(NflTeam::class, 'team_id');
    }
}