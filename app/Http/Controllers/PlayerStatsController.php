<?php

namespace App\Http\Controllers;

use App\Repositories\Nfl\NflPlayerStatRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlayerStatsController extends Controller
{
    protected $repository;

    public function __construct(NflPlayerStatRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a list of receiving stats.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request)
    {
        $longName = $request->input('long_name');
        $teamAbv = $request->input('team_abv');
        $year = $request->input('year'); // Optional year filter

        $receivingStats = $this->repository->getReceivingStats($longName, $teamAbv, $year);

        return view('nfl.player-stats', compact('receivingStats', 'longName', 'teamAbv', 'year'));
    }
}
