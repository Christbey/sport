<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\NflTeamRepositoryInterface;
use Illuminate\Http\Request;

class NflTeamController extends Controller
{
    protected $nflTeamRepository;

    public function __construct(NflTeamRepositoryInterface $nflTeamRepository)
    {
        $this->nflTeamRepository = $nflTeamRepository;
    }

    public function index()
    {
        $teams = $this->nflTeamRepository->all();
        return response()->json($teams);
    }

    public function show($id)
    {
        $team = $this->nflTeamRepository->findById($id);

        if (!$team) {
            return response()->json(['error' => 'Team not found'], 404);
        }

        return response()->json($team);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'team_abv' => 'required|string',
            'team_city' => 'required|string',
            'team_name' => 'required|string',
            // Add other validation rules as needed
        ]);

        $team = $this->nflTeamRepository->create($data);
        return response()->json($team, 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'team_abv' => 'nullable|string',
            'team_city' => 'nullable|string',
            'team_name' => 'nullable|string',
            // Add other validation rules as needed
        ]);

        $updated = $this->nflTeamRepository->update($id, $data);

        if (!$updated) {
            return response()->json(['error' => 'Team not found or not updated'], 404);
        }

        return response()->json(['message' => 'Team updated successfully']);
    }

    public function destroy($id)
    {
        $deleted = $this->nflTeamRepository->delete($id);

        if (!$deleted) {
            return response()->json(['error' => 'Team not found or not deleted'], 404);
        }

        return response()->json(['message' => 'Team deleted successfully']);
    }
}
