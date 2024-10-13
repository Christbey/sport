<?php

namespace App\Http\Controllers\Nfl;

use App\Http\Controllers\Controller;
use App\Models\Nfl\NflEloRating;
use App\Models\NflEloPrediction;
use Illuminate\Http\Request;

class NflEloRatingController extends Controller
{
    public function prediction(Request $request)
    {
        $week = $request->input('week');

        $eloPredictionsQuery = NflEloPrediction::query();

        if ($week) {
            $eloPredictionsQuery->where('week', $week);
        }

        $eloPredictions = $eloPredictionsQuery->orderBy('team')->get();

        $weeks = NflEloPrediction::select('week')->distinct()->orderBy('week')->pluck('week');

        return view('nfl.elo_predictions', compact('eloPredictions', 'weeks', 'week'));
    }
}
