<?php

namespace App\Models\Nfl;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NflTeamStat extends Model
{
    use HasFactory;

    // The table associated with the model
    protected $table = 'nfl_team_stats';

    // Fillable fields
    protected $fillable = [
        'game_id',
        'team_id',
        'team_abv',
        'total_yards',
        'rushing_yards',
        'passing_yards',
        'points_allowed',
        'rushing_attempts',
        'fumbles_lost',
        'penalties',
        'total_plays',
        'possession',
        'safeties',
        'pass_completions_and_attempts',
        'passing_first_downs',
        'interceptions_thrown',
        'sacks_and_yards_lost',
        'third_down_efficiency',
        'yards_per_play',
        'red_zone_scored_and_attempted',
        'defensive_interceptions',
        'defensive_or_special_teams_tds',
        'total_drives',
        'rushing_first_downs',
        'first_downs',
        'first_downs_from_penalties',
        'fourth_down_efficiency',
        'yards_per_rush',
        'turnovers',
        'yards_per_pass',
        'result', // Newly added


    ];

    // Relationships

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($stats) {
            if (empty($stats->team_abv) && !empty($stats->team_id)) {
                // Fetch team abbreviation from related team if not provided
                $team = NflTeam::find($stats->team_id);
                if ($team) {
                    $stats->team_abv = $team->team_abv;
                }
            }
        });
    }

    public function game()
    {
        return $this->belongsTo(NflBoxScore::class, 'game_id', 'game_id');
    }
}
