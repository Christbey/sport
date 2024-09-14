<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpRating extends Model
{
    use HasFactory;

    // The table associated with the model
    protected $table = 'sp_ratings';

    // Mass assignable fields
    protected $fillable = [
        'team',
        'team_id',
        'conference',
        'overall_rating',
        'ranking',
        'offense_ranking',
        'offense_rating',
        'defense_ranking',
        'defense_rating',
        'special_teams_rating',
    ];
}
