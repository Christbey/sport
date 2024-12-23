<?php

namespace App\Models;

use App\Models\Nfl\NflTeamSchedule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'slug',
        'content',
        'week',           // Updated from 'game_week' to 'week'
        'season',
        'away_team',
        'home_team',
        'game_date',      // New column
        'game_time',      // New column
        'game_id',        // New column
        'prediction',
        'published',
    ];

    /**
     * Set the title and automatically set the slug.
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            $post->slug = Str::slug($post->title);
        });

        static::updating(function ($post) {
            if ($post->isDirty('title')) {
                $post->slug = Str::slug($post->title);
            }
        });
    }

    /**
     * Define relationship with NflTeamSchedule (if applicable).
     */
    public function game()
    {
        return $this->belongsTo(NflTeamSchedule::class, 'game_id');
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
