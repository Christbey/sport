<?php

namespace App\Models\Nfl;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NflDrive extends Model
{
    protected $fillable = [
        'game_id',
        'team_id',
        'drive_number',
        'start_quarter',
        'start_time',
        'start_yard_line',
        'end_quarter',
        'end_time',
        'end_yard_line',
        'plays',
        'yards',
        'drive_result',
        'scoring_drive',
    ];

    public function plays(): HasMany
    {
        return $this->hasMany(NflPlay::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(NflTeam::class, 'team_id', 'espn_id');
    }
}