<?php

namespace App\Models\CollegeFootball;

use App\Models\CollegeFootball\CollegeFootballTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollegeFootballTeamAlias extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'college_football_team_aliases';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['team_id', 'alias_name'];

    /**
     * Define the relationship between the alias and the corresponding team.
     *
     * @return BelongsTo
     */
    public function team()
    {
        return $this->belongsTo(CollegeFootballTeam::class, 'team_id');
    }
}
