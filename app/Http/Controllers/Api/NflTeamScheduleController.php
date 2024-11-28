<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\NflTeamScheduleRepositoryInterface;
use Illuminate\Http\Request;

class NflTeamScheduleController extends Controller
{
    protected $scheduleRepository;

    public function __construct(NflTeamScheduleRepositoryInterface $scheduleRepository)
    {
        $this->scheduleRepository = $scheduleRepository;
    }

    public function index()
    {
        $schedules = $this->scheduleRepository->getAllSchedules();
        return response()->json($schedules);
    }

    public function show(string $teamId)
    {
        $schedule = $this->scheduleRepository->getScheduleByTeam($teamId);

        if (empty($schedule)) {
            return response()->json(['error' => 'Schedule not found for this team'], 404);
        }

        return response()->json($schedule);
    }

    public function byDateRange(Request $request, string $teamId)
    {
        $range = $request->query('range', []);

        if (count($range) !== 2) {
            return response()->json(['error' => 'Invalid date range'], 400);
        }

        $schedule = $this->scheduleRepository->getScheduleByDateRange($teamId, $range);

        return response()->json($schedule);
    }

    public function recentGames(Request $request, string $teamId)
    {
        $limit = $request->query('limit', 5);
        $schedule = $this->scheduleRepository->getRecentGames($teamId, $limit);

        return response()->json($schedule);
    }


}
