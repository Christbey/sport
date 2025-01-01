<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NbaPlayerStat extends Model
{
    use HasFactory;

    protected $table = 'nba_player_stats';

    protected $fillable = [
        'event_id',
        'team_id',
        'player_id',
        'opponent_id',
        'event_date',
        'competition_ref',
        'athlete_ref',
        'splits_json',
        // Defensive Stats
        'blocks',
        'defensive_rebounds',
        'steals',
        'avg_defensive_rebounds',
        'avg_blocks',
        'avg_steals',
        'avg_48_defensive_rebounds',
        'avg_48_blocks',
        'avg_48_steals',
        // General Stats
        'largest_lead',
        'disqualifications',
        'flagrant_fouls',
        'fouls',
        'ejections',
        'technical_fouls',
        'rebounds',
        'minutes',
        'avg_minutes',
        'nba_rating',
        'plus_minus',
        'avg_rebounds',
        'avg_fouls',
        'avg_flagrant_fouls',
        'avg_technical_fouls',
        'avg_ejections',
        'avg_disqualifications',
        'assist_turnover_ratio',
        'steal_foul_ratio',
        'block_foul_ratio',
        'avg_team_rebounds',
        'total_rebounds',
        'total_technical_fouls',
        'team_assist_turnover_ratio',
        'steal_turnover_ratio',
        'avg_48_rebounds',
        'avg_48_fouls',
        'avg_48_flagrant_fouls',
        'avg_48_technical_fouls',
        'avg_48_ejections',
        'avg_48_disqualifications',
        'r40',
        'games_played',
        'games_started',
        'double_double',
        'triple_double',
        // Offensive Stats
        'assists',
        'field_goals',
        'field_goals_attempted',
        'field_goals_made',
        'field_goal_pct',
        'free_throws',
        'free_throw_pct',
        'free_throws_attempted',
        'free_throws_made',
        'offensive_rebounds',
        'points',
        'turnovers',
        'three_point_pct',
        'three_point_field_goals_attempted',
        'three_point_field_goals_made',
        'total_turnovers',
        'points_in_paint',
        'brick_index',
        'avg_field_goals_made',
        'avg_field_goals_attempted',
        'avg_three_point_field_goals_made',
        'avg_three_point_field_goals_attempted',
        'avg_free_throws_made',
        'avg_free_throws_attempted',
        'avg_points',
        'avg_offensive_rebounds',
        'avg_assists',
        'avg_turnovers',
        'offensive_rebound_pct',
        'estimated_possessions',
        'avg_estimated_possessions',
        'points_per_estimated_possession',
        'avg_team_turnovers',
        'avg_total_turnovers',
        'three_point_field_goal_pct',
        'two_point_field_goals_made',
        'two_point_field_goals_attempted',
        'avg_two_point_field_goals_made',
        'avg_two_point_field_goals_attempted',
        'two_point_field_goal_pct',
        'shooting_efficiency',
        'scoring_efficiency',
        'avg_48_field_goals_made',
        'avg_48_field_goals_attempted',
        'avg_48_three_point_field_goals_made',
        'avg_48_three_point_field_goals_attempted',
        'avg_48_free_throws_made',
        'avg_48_free_throws_attempted',
        'avg_48_points',
        'avg_48_offensive_rebounds',
        'avg_48_assists',
        'avg_48_turnovers',
        'p40',
        'a40',
    ];

    protected $casts = [
        'splits_json' => 'array',
        // Cast other fields as necessary
    ];

    // Define relationships if any
    // For example, if NbaPlayerStat belongs to a player:
    public function player()
    {
        return $this->belongsTo(NbaPlayer::class, 'player_id', 'espn_id');
    }

    public function event()
    {
        return $this->belongsTo(NbaEvent::class, 'event_id', 'espn_id');
    }
}
