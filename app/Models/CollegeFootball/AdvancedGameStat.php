<?php

namespace App\Models\CollegeFootball;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvancedGameStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id', 'season', 'week', 'team', 'team_id', 'opponent',
        'offense_plays', 'offense_drives', 'offense_ppa', 'offense_total_ppa', 'offense_success_rate',
        'offense_explosiveness', 'offense_power_success', 'offense_stuff_rate', 'offense_line_yards',
        'offense_line_yards_total', 'offense_second_level_yards', 'offense_second_level_yards_total',
        'offense_open_field_yards', 'offense_open_field_yards_total', 'offense_standard_downs_ppa',
        'offense_standard_downs_success_rate', 'offense_standard_downs_explosiveness',
        'offense_passing_downs_ppa', 'offense_passing_downs_success_rate', 'offense_passing_downs_explosiveness',
        'offense_rushing_ppa', 'offense_rushing_total_ppa', 'offense_rushing_success_rate',
        'offense_rushing_explosiveness', 'offense_passing_ppa', 'offense_passing_total_ppa',
        'offense_passing_success_rate', 'offense_passing_explosiveness', 'defense_plays',
        'defense_drives', 'defense_ppa', 'defense_total_ppa', 'defense_success_rate',
        'defense_explosiveness', 'defense_power_success', 'defense_stuff_rate', 'defense_line_yards',
        'defense_line_yards_total', 'defense_second_level_yards', 'defense_second_level_yards_total',
        'defense_open_field_yards', 'defense_open_field_yards_total', 'defense_standard_downs_ppa',
        'defense_standard_downs_success_rate', 'defense_standard_downs_explosiveness',
        'defense_passing_downs_ppa', 'defense_passing_downs_success_rate', 'defense_passing_downs_explosiveness',
        'defense_rushing_ppa', 'defense_rushing_total_ppa', 'defense_rushing_success_rate',
        'defense_rushing_explosiveness', 'defense_passing_ppa', 'defense_passing_total_ppa',
        'defense_passing_success_rate', 'defense_passing_explosiveness'
    ];
}
