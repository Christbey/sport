<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Spatie\SchemaOrg\Schema;

class PostController extends Controller
{
    public function index()
    {
        // Retrieve all posts, ordered by creation date descending, with pagination (10 per page)
        $posts = Post::orderBy('created_at', 'desc')->paginate(10);

        // Pass the posts to the index view
        return view('posts.index', compact('posts'));
    }

    /**
     * Display the specified resource.
     */
    public function show($season, $week, $game_date, $slug)
    {
        $this->validateParameters($season, $week, $game_date, $slug);
        $post = $this->retrievePost($season, $week, $game_date, $slug);
        $sportDetails = $this->getSportFromSlug($slug);
        $schema = $this->generateSchema($post, $sportDetails);

        return view('posts.show', compact('post', 'schema'));
    }

    private function validateParameters($season, $week, $game_date, $slug)
    {
        $validator = Validator::make([
            'season' => $season,
            'week' => $week,
            'game_date' => $game_date,
            'slug' => $slug,
        ], [
            'season' => 'required|digits:4|integer|min:1900|max:2100',
            'week' => 'required|integer|min:1|max:25',
            'game_date' => 'required|date_format:Y-m-d',
            'slug' => ['required', 'regex:/^[A-Za-z0-9\-]+$/'],
        ]);

        if ($validator->fails()) {
            abort(404);
        }
    }

    private function retrievePost($season, $week, $game_date, $slug)
    {
        return Post::where('season', $season)
            ->where('week', $week)
            ->whereDate('game_date', $game_date)
            ->where('slug', $slug)
            ->with('author')
            ->firstOrFail();
    }

    private function getSportFromSlug($slug)
    {
        $sportIdentifier = strtoupper(explode('-', $slug)[0]);

        $sportMap = [
            'NFL' => [
                'name' => 'NFL',
                'fullName' => 'National Football League',
                'sport' => 'American Football'
            ],
            'NBA' => [
                'name' => 'NBA',
                'fullName' => 'National Basketball Association',
                'sport' => 'Basketball'
            ],
            'MLB' => [
                'name' => 'MLB',
                'fullName' => 'Major League Baseball',
                'sport' => 'Baseball'
            ],
            'NHL' => [
                'name' => 'NHL',
                'fullName' => 'National Hockey League',
                'sport' => 'Ice Hockey'
            ],
            'NCAAF' => [
                'name' => 'NCAAF',
                'fullName' => 'NCAA Football',
                'sport' => 'American Football'
            ],
            'NCAAB' => [
                'name' => 'NCAAB',
                'fullName' => 'NCAA Basketball',
                'sport' => 'Basketball'
            ],
        ];

        return $sportMap[$sportIdentifier] ?? [
            'name' => $sportIdentifier,
            'fullName' => $sportIdentifier,
            'sport' => 'Sport'
        ];
    }

    private function generateSchema($post, $sportDetails)
    {
        // Helper function to convert time format
        $formatGameTime = function ($timeStr) {
            // Carbon tries to guess the format
            return Carbon::parse($timeStr)->format('H:i:s');
        };


        // Parse the game date and time together
        $startDateTime = Carbon::parse($post->game_date)
            ->setTimeFromTimeString($formatGameTime($post->game_time));

        return Schema::article()
            ->identifier(url("/{$sportDetails['name']}-{$post->season}-W{$post->week}-{$post->away_team}-{$post->home_team}"))
            ->headline($post->title)
            ->datePublished($post->created_at->toISO8601String())
            ->dateModified($post->updated_at->toISO8601String())
            ->about(
                Schema::sportsEvent()
                    ->identifier(url("/{$sportDetails['name']}-{$post->season}-W{$post->week}-{$post->away_team}-{$post->home_team}"))
                    ->name(sprintf(
                        '%s Week %d: %s at %s (%s)',
                        $sportDetails['name'],
                        $post->week,
                        $post->away_team,
                        $post->home_team,
                        Carbon::parse($post->game_date)->format('F j, Y')
                    ))
                    ->startDate($startDateTime)
                    ->competitor([
                        Schema::sportsTeam()
                            ->name($post->home_team)
                            ->memberOf(Schema::organization()->name($sportDetails['fullName']))
                            ->additionalType('HomeTeam'),
                        Schema::sportsTeam()
                            ->name($post->away_team)
                            ->memberOf(Schema::organization()->name($sportDetails['fullName']))
                            ->additionalType('AwayTeam')
                    ])
                    ->sport($sportDetails['sport'])
            )
            ->author(
                Schema::person()
                    ->identifier(url("/authors/{$post->author->id}"))
                    ->name($post->author->name)
                    ->description($post->author->bio ?? null)
                    ->image($post->author->profile_photo_path
                        ? asset('storage/' . $post->author->profile_photo_path)
                        : null)
            )
            ->publisher(
                Schema::organization()
                    ->identifier(url('/'))
                    ->name(config('app.name'))
                    ->url(url('/'))
            )
            ->description($post->excerpt ?? '')
            ->articleSection("{$sportDetails['name']} Week {$post->week}")
            ->keywords([
                $sportDetails['name'],
                $post->away_team,
                $post->home_team,
                "Week {$post->week}",
                "Season {$post->season}",
                "{$sportDetails['name']} Analysis"
            ])
            ->isAccessibleForFree(true);
    }
}
