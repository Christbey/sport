<?php

namespace App\Models\Nfl;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NflPlay extends Model
{
    protected $fillable = [
        'game_id',
        'team_id',
        'drive_id',
        'play_id',
        'quarter',
        'time',
        'down',
        'distance',
        'yard_line',
        'description',
        'play_type',
        'yards_gained',
        'first_down',
        'touchdown',
        'turnover',
        'epa',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(NflTeamSchedule::class);
    }

    public function drive(): BelongsTo
    {
        return $this->belongsTo(NflDrive::class);
    }

    public function players()
    {
        return $this->belongsToMany(NflPlayerData::class, 'nfl_play_players', 'play_id', 'player_id')
            ->withPivot('role', 'team_id')
            ->withTimestamps();
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(NflTeam::class, 'team_id', 'team_id');
    }
}