<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollegeBasketballTeam extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $table = 'college_basketball_teams';

    // If your `team_id` should not auto-increment, specify it here.
    protected $fillable = [
        'team_id',
        'uid',
        'slug',
        'abbreviation',
        'display_name',
        'name',
        'nickname',
        'location',
        'color',
        'alternate_color',
        'is_active',
        'is_all_star',
        'logo_url',
    ];
    protected $keyType = 'string';

// CollegeBasketballTeam.php
    public function aliases()
    {
        return $this->hasMany(CollegeBasketballTeamAlias::class, 'team_id');
    }
    // Additional relationships or methods for the model can be defined here.
}