<?php

namespace App\Models\Nfl;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NflEloPrediction extends Model
{
    use HasFactory;

    protected $table = 'nfl_elo_predictions';

    protected $fillable = [
        'team',
        'opponent',
        'year',
        'week',
        'team_elo',
        'opponent_elo',
        'expected_outcome',
        'predicted_spread',
        'game_id'
    ];
}
