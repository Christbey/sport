<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'input', 'output'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}