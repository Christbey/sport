<?php

// CollegeBasketballTeamAlias.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollegeBasketballTeamAlias extends Model
{
    use HasFactory;

    protected $fillable = ['team_id', 'alias'];

    public function team()
    {
        return $this->belongsTo(CollegeBasketballTeam::class);
    }
}