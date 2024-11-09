<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollegeBasketballRankings extends Model
{
    use HasFactory;

    protected $table = 'college_basketball_rankings';
    protected $fillable = [
        'rank',
        'team',
        'team_id',
        'conference',
        'record',
        'net_rating',
        'offensive_rating',
        'defensive_rating',
        'tempo',
    ];
}
// https://www.espn.com/mens-college-basketball/team/schedule/_/id/57
//https://sonnymoorepowerratings.com/w-basket.htm