<?php

namespace App\Http\Controllers;

use App\Models\CollegeFootballNote;
use Illuminate\Http\Request;

class CollegeFootballNoteController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'game_id' => 'required|exists:college_football_games,id',
            'team_id' => 'required|exists:college_football_teams,id',
            'note' => 'required|string',
        ]);

        CollegeFootballNote::create([
            'game_id' => $request->input('game_id'),
            'team_id' => $request->input('team_id'),
            'note' => $request->input('note'),
        ]);

        return redirect()->back()->with('success', 'Note added successfully.');
    }


}
