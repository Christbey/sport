<?php

namespace App\Models\Nfl;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NflTeamSchedule extends Model
{
    protected $table = 'nfl_team_schedules';
    protected $fillable = [
        'team_abv',
        'game_id',
        'name',
        'season_type',
        'season',
        'away_team',
        'home_team_id',
        'game_date',
        'game_status',
        'game_week',
        'away_team_id',
        'home_team',
        'away_result',
        'home_result',
        'home_pts',
        'away_pts',
        'game_time',
        'game_time_epoch',
        'game_status_code',

        // New columns added based on the job and migration
        'espn_event_id', // ESPN event ID
        'uid', // ESPN UID
        'status_type_detail', // Status detail from ESPN
        'home_team_record', // Home team record
        'away_team_record', // Away team record
        'neutral_site', // Neutral site flag
        'conference_competition', // Conference competition flag
        'attendance', // Attendance count
        'short_name', // Event short name
        'referees', // Referees for the game
    ];

    protected $casts = [
        'game_date' => 'date',
        'home_pts' => 'integer',
        'away_pts' => 'integer',
        'game_time' => 'string',
        'neutral_site' => 'boolean',
        'status_type_detail' => 'string', // Automatically JSON encode/decode
        'referees' => 'array', // Automatically JSON encode/decode
        'conference_competition' => 'boolean', // If applicable

    ];

    public function homeTeam()
    {
        return $this->belongsTo(NflTeam::class, 'home_team_id');
    }

    // Define the relationship for the home team

    public function awayTeam()
    {
        return $this->belongsTo(NflTeam::class, 'away_team_id');
    }

    // Define the away team relationship

    public function stats()
    {
        return $this->hasMany(NflTeamStat::class, 'game_id', 'game_id');
    }

    public function scopeForTeam(Builder $query, int $teamId)
    {
        return $query->where('home_team_id', $teamId)
            ->orWhere('away_team_id', $teamId);
    }

    public function getOpponentAttribute()
    {
        if ($this->team_abv === $this->home_team) {
            return $this->awayTeam;
        } else {
            return $this->homeTeam;
        }
    }


}
