<?php

namespace App\Models\Nfl;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NflPlayerStat extends Model
{
    use HasFactory;

    // The table associated with the model
    protected $table = 'nfl_player_stats';

    // Fillable fields
    protected $fillable = [
        'game_id',
        'player_id',
        'team_id',
        'team_abv',
        'receiving',
        'long_name',
        'rushing',
        'kicking',
        'punting',
        'defense',
    ];

    // Cast JSON columns
    protected $casts = [
        'receiving' => 'array',
        'rushing' => 'array',
        'kicking' => 'array',
        'punting' => 'array',
        'defense' => 'array',
    ];

    // Relationships
    public function game()
    {
        return $this->belongsTo(NflBoxScore::class, 'game_id', 'game_id');
    }
}
