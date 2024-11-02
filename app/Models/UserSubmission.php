<?php

namespace App\Models;

use App\Models\Nfl\NflTeam;
use App\Models\Nfl\NflTeamSchedule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'espn_event_id',
        'week_id',
        'team_id',
        'is_correct',
    ];

    /**
     * Get the user that made the submission.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the event associated with the submission.
     */


    /**
     * Get the team that the user selected.
     */
    public function team()
    {
        return $this->belongsTo(NflTeam::class, 'team_id');
    }

    // In UserSubmission model
    public function event()
    {
        return $this->belongsTo(NflTeamSchedule::class, 'espn_event_id', 'espn_event_id');
    }


}
