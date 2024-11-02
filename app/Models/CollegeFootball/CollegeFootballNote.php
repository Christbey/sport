<?php

namespace App\Models\CollegeFootball;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollegeFootballNote extends Model
{
    use HasFactory;

    protected $fillable = ['game_id', 'team_id', 'note', 'user_id'];

    public function team()
    {
        return $this->belongsTo(CollegeFootballTeam::class, 'team_id');
    }

    public function game()
    {
        return $this->belongsTo(CollegeFootballGame::class, 'game_id');
    }
}
