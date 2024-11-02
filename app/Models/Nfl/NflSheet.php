<?php

namespace App\Models\Nfl;

use App\Models\NflTeamSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NflSheet extends Model
{
    use HasFactory;

    protected $table = 'nfl_sheet';

    protected $fillable = [
        'team_id',
        'user_id',
        'game_id',  // Add game_id here
        'user_inputted_notes',
        'fezzik_rankings',
        'elo_rankings',
    ];

    // Define relationship with the NflTeam model
    public function nflTeam()
    {
        return $this->belongsTo(NflTeam::class, 'team_id');
    }

    // Define relationship with the NflTeamSchedule model
    public function nflTeamSchedule()
    {
        return $this->belongsTo(NflTeamSchedule::class, 'game_id');
    }

    // Define relationship with the User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
