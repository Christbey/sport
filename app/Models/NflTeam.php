<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NflTeam extends Model
{
    protected $fillable = [
        'team_abv',
        'team_city',
        'team_name',
        'team_id',
        'division',
        'conference_abv',
        'conference',
        'nfl_com_logo1',
        'espn_logo1',
        'espn_id',
        'uid',
        'slug',
        'color',
        'alternate_color',
        'is_active',
        'wins',
        'loss',
        'tie',
        'pf',
        'pa',
        'current_streak',
    ];

    protected $casts = [
        'current_streak' => 'array',
    ];
}
