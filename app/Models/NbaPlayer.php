<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NbaPlayer extends Model
{
    use HasFactory;

    protected $table = 'nba_players';

    protected $fillable = [
        'espn_id',
        'team_espn_id',
        'first_name',
        'last_name',
        'full_name',
        'display_name',
        'slug',
        'position',
        'jersey',
        'height',
        'weight',
        'birth_city',
        'birth_state',
        'birth_country',
        'salary',
        'salary_remaining',
        'years_remaining',
        'contract_active',
        'draft_year',
        'draft_round',
        'draft_selection',
        'is_active',
    ];

    // Optional: If you want a relationship to NbaTeam,
    // you can define a custom foreign key using 'team_espn_id'.
    // public function team()
    // {
    //     return $this->belongsTo(NbaTeam::class, 'team_espn_id', 'espn_id');
    // }

    // Define relationship with prop bets
    public function propBets()
    {
        return $this->hasMany(NbaPropBet::class, 'athlete_id', 'espn_id');
    }

    // Define relationship with player stats
    public function stats()
    {
        return $this->hasMany(NbaPlayerStat::class, 'player_id', 'espn_id');
    }
}
