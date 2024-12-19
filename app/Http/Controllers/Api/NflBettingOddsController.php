<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NflBettingOddsResource;
use App\Models\Nfl\NflBettingOdds;
use Illuminate\Http\Request;

class NflBettingOddsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * GET /api/nfl-betting-odds
     */
    public function index()
    {
        $odds = NflBettingOdds::all();
        return NflBettingOddsResource::collection($odds);
    }

    /**
     * Store a newly created resource in storage.
     *
     * POST /api/nfl-betting-odds
     */
    public function store(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'event_id' => 'required|integer',
            'game_date' => 'required|date',
            'away_team' => 'required|string|max:255',
            'home_team' => 'required|string|max:255',
            'away_team_id' => 'required|integer',
            'home_team_id' => 'required|integer',
            'source' => 'required|string|max:255',
            'spread_home' => 'required|numeric',
            'spread_away' => 'required|numeric',
            'total_over' => 'required|numeric',
            'total_under' => 'required|numeric',
            'moneyline_home' => 'required|numeric',
            'moneyline_away' => 'required|numeric',
            'implied_total_home' => 'required|numeric',
            'implied_total_away' => 'required|numeric',
        ]);

        // Create the new NflBettingOdds record
        $odds = NflBettingOdds::create($validated);

        // Return the newly created resource with a 201 status code
        return (new NflBettingOddsResource($odds))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * GET /api/nfl-betting-odds/{id}
     */
    public function show($id)
    {
        $odds = NflBettingOdds::findOrFail($id);
        return new NflBettingOddsResource($odds);
    }

    /**
     * Update the specified resource in storage.
     *
     * PUT/PATCH /api/nfl-betting-odds/{id}
     */
    public function update(Request $request, $id)
    {
        $odds = NflBettingOdds::findOrFail($id);

        // Validate the incoming request data
        $validated = $request->validate([
            'event_id' => 'sometimes|required|integer',
            'game_date' => 'sometimes|required|date',
            'away_team' => 'sometimes|required|string|max:255',
            'home_team' => 'sometimes|required|string|max:255',
            'away_team_id' => 'sometimes|required|integer',
            'home_team_id' => 'sometimes|required|integer',
            'source' => 'sometimes|required|string|max:255',
            'spread_home' => 'sometimes|required|numeric',
            'spread_away' => 'sometimes|required|numeric',
            'total_over' => 'sometimes|required|numeric',
            'total_under' => 'sometimes|required|numeric',
            'moneyline_home' => 'sometimes|required|numeric',
            'moneyline_away' => 'sometimes|required|numeric',
            'implied_total_home' => 'sometimes|required|numeric',
            'implied_total_away' => 'sometimes|required|numeric',
        ]);

        // Update the NflBettingOdds record
        $odds->update($validated);

        // Return the updated resource
        return new NflBettingOddsResource($odds);
    }

    /**
     * Remove the specified resource from storage.
     *
     * DELETE /api/nfl-betting-odds/{id}
     */
    public function destroy($id)
    {
        $odds = NflBettingOdds::findOrFail($id);
        $odds->delete();

        return response()->json(null, 204);
    }
}
