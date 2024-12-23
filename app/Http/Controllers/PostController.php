<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Retrieve all posts, ordered by creation date descending, with pagination (10 per page)
        $posts = Post::orderBy('created_at', 'desc')->paginate(10);

        // Pass the posts to the index view
        return view('posts.index', compact('posts'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show($season, $week, $game_date, $slug)
    {
        // Validate the parameters
        $validator = Validator::make([
            'season' => $season,
            'week' => $week,
            'game_date' => $game_date,
            'slug' => $slug,
        ], [
            'season' => 'required|digits:4|integer|min:1900|max:2100',
            'week' => 'required|integer|min:1|max:25', // Adjust max based on NFL weeks
            'game_date' => 'required|date_format:Y-m-d',
            'slug' => ['required', 'regex:/^[A-Za-z0-9\-]+$/'],
        ]);

        if ($validator->fails()) {
            abort(404); // Or handle the error as desired
        }

        // Retrieve the post based on the provided parameters
        $post = Post::where('season', $season)
            ->where('week', $week)
            ->whereDate('game_date', $game_date)
            ->where('slug', $slug)
            ->firstOrFail();

        return view('posts.show', compact('post'));
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
