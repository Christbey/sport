<?php

namespace App\Http\Controllers;

use App\Models\CollegeBasketballHypothetical;
use Illuminate\Http\Request;

class CollegeBasketballHypotheticalController extends Controller
{
    public function index(Request $request)
    {
        // Get distinct game dates for the dropdown filter
        $dates = CollegeBasketballHypothetical::select('game_date')->distinct()->orderBy('game_date')->get();

        // Filter by the selected game_date if provided
        $selectedDate = $request->input('game_date');
        $query = CollegeBasketballHypothetical::query();

        if ($selectedDate) {
            $query->where('game_date', $selectedDate);
        }

        $hypotheticals = $query->get();

        return view('cbb.index', compact('hypotheticals', 'dates', 'selectedDate'));
    }
}
