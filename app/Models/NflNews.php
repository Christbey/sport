<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NflNews extends Model
{
    protected $table = 'nfl_news';

    protected $fillable = [
        'link',
        'title',
    ];
}
