<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sagarin extends Model
{
    protected $table = 'sagarin';

    protected $fillable = [
        'id',
        'team_name',
        'rating',
    ];
}
