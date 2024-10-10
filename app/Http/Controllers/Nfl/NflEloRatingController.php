<?php

namespace App\Http\Controllers\Nfl;

use App\Http\Controllers\Controller;
use App\Models\Nfl\NflEloRating;
use App\Models\NflEloPrediction;
use Illuminate\View\View;

class NflEloRatingController extends Controller
{
    /**
     * Display a listing of the Elo ratings.
     *
     * @return View
     */
    public function index()
    {
        // Fetch all Elo ratings
        $eloRatings = NflEloRating::all();

        // Return the renamed view with the Elo ratings
        return view('nfl.elo', compact('eloRatings'));
    }

    public function prediction()
    {
        $eloPredictions = NflEloPrediction::all(); // Get all predictions from the table
        return view('nfl.elo_predictions', compact('eloPredictions'));

    }
}
