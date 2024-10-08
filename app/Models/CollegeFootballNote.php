<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollegeFootballNote extends Model
{
    use HasFactory;

    protected $fillable = ['game_id', 'team_id', 'note'];
}
