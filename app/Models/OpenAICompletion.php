<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpenAICompletion extends Model


{
    protected $table = 'openai_completions';

    protected $fillable = [
        'user_id',
        'completion_id',
        'request_id',
        'model',
        'openai_created_at',
        'metadata',
        'tools',
        'messages',
        'response_messages',
        'system_fingerprint',
        'metadata',
        'object',
        'choices',
        'usage',
        'metadata',

    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}