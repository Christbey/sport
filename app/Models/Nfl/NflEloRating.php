<?php

namespace App\Models\Nfl;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NflEloRating extends Model
{
    use HasFactory;

    protected $table = 'nfl_elo_ratings';

    protected $fillable = [
        'team',
        'year',
        'final_elo',
        'expected_wins',
        'predicted_spread',
    ];
}
