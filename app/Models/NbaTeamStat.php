<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NbaTeamStat extends Model
{
    use HasFactory;

    protected $table = 'nba_team_stats';

    protected $fillable = [
        'event_id',
        'team_id',
        'splits_json',
        'team_ref',
        'competition_ref',
        'event_date',
        'opponent_id',
    ];

    protected $casts = [
        'splits_json' => 'array',  // Tells Eloquent to handle JSON as a PHP array
    ];
}
