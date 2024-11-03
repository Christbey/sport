<?php

namespace App\Http\Controllers;

use App\Models\NflNews;

class NflNewsController extends Controller
{
    public function index()
    {
        $newsItems = NflNews::orderBy('created_at', 'desc')->paginate(10);

        return view('nfl.news.index', compact('newsItems'));
    }
}
