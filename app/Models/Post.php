<?php

namespace App\Models;

use App\Models\Nfl\NflTeamSchedule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    // Define fillable fields as needed
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
        'user_id', // Foreign key to users table
    ];
    /**
     * Cast attributes to specific types.
     */
    protected $casts = [
        'published' => 'boolean',
        'game_date' => 'date',
    ];

    /**
     * Set the title and automatically set the slug.
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            $post->slug = Str::slug($post->title);
            $post->away_team = strtoupper($post->away_team);
            $post->home_team = strtoupper($post->home_team);
        });

        static::updating(function ($post) {
            if ($post->isDirty('title')) {
                $post->slug = Str::slug($post->title);
            }
            if ($post->isDirty('away_team')) {
                $post->away_team = strtoupper($post->away_team);
            }
            if ($post->isDirty('home_team')) {
                $post->home_team = strtoupper($post->home_team);
            }
        });
    }

    /**
     * Get the author (user) that owns the post.
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Define relationship with NflTeamSchedule (if applicable).
     */
    public function game()
    {
        return $this->belongsTo(NflTeamSchedule::class, 'game_id');
    }

    /**
     * Override the route key name to use 'slug' for existing routes.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Generate the custom URL for the post based on season, week, game date, and slug.
     *
     * @return string
     */
    public function getCustomUrlAttribute()
    {
        return route('posts.show', [
            'season' => $this->season,
            'week' => $this->week,
            'game_date' => $this->game_date->format('Y-m-d'),
            'slug' => $this->slug
        ]);
    }
}
