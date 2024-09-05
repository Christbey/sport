<?php

namespace App\Models\CollegeFootball;

use Illuminate\Database\Eloquent\Model;

class CollegeFootballTalent extends Model
{
    protected $fillable = [
        'year',
        'school',
        'talent'
    ];
}
