<?php

namespace App\Models\CollegeFootball;

use Illuminate\Database\Eloquent\Model;

class CollegeFootballCoach extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'hire_date',
        'seasons'
    ];
}
