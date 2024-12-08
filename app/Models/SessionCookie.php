<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionCookie extends Model
{
    use HasFactory;

    protected $table = 'session_cookies';

    protected $fillable = [
        'unique_id',
        'user_id',
        'ip_v4',
        'ip_v6',
        'user_agent',
    ];

    // Optionally define relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
