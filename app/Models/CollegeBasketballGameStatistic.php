<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollegeBasketballGameStatistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'team_type',
        'rebounds',
        'assists',
        'field_goals_attempted',
        'field_goals_made',
        'field_goal_percentage',
        'free_throw_percentage',
        'free_throws_attempted',
        'free_throws_made',
        'points',
        'three_point_field_goals_attempted',
        'three_point_field_goals_made',
        'three_point_field_goal_percentage',
    ];

    protected $casts = [
        'field_goal_percentage' => 'float',
        'free_throw_percentage' => 'float',
        'three_point_field_goal_percentage' => 'float',
    ];

    /**
     * Relationship with the CollegeBasketballGame model.
     */
    public function game()
    {
        return $this->belongsTo(CollegeBasketballGame::class);
    }
}
