<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollegeBasketballHypothetical extends Model
{
    use HasFactory;

    protected $table = 'college_basketball_hypotheticals';

    protected $fillable = [
        'game_id',
        'home_id',
        'away_id',
        'game_date',
        'home_team',
        'away_team',
        'hypothetical_spread',
        'offense_difference',
        'defense_difference',
    ];
}