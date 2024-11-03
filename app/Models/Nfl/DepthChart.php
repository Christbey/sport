<?php

namespace App\Models\Nfl;

use Illuminate\Database\Eloquent\Model;

class DepthChart extends Model
{
    protected $table = 'nfl_depth_charts';

    protected $fillable = [
        'team_id',
        'team_name',
        'position',
        'player_id',
        'player_name',
        'depth_order',
    ];
}
