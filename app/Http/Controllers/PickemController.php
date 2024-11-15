<?php

namespace App\Http\Controllers;

use App\Http\Requests\PickWinnerRequest;
use App\Services\GameWeekService;
use App\Services\LeaderboardService;
use App\Services\PickemService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB};

class PickemController extends Controller
{
    protected GameWeekService $gameWeekService;
    protected LeaderboardService $leaderboardService;
    protected PickemService $pickemService;

    public function __construct(
        GameWeekService    $gameWeekService,
        LeaderboardService $leaderboardService,
        PickemService      $pickemService
    )
    {
        $this->gameWeekService = $gameWeekService;
        $this->leaderboardService = $leaderboardService;
        $this->pickemService = $pickemService;
    }

    public function showTeamSchedule(Request $request, $game_week = null)
    {
        $game_week = $this->gameWeekService->determineGameWeek($request, $game_week);
        $weeks = $this->gameWeekService->getGameWeeks();
        $schedules = $this->pickemService->getSchedulesForWeek($game_week);
        $userSubmissions = $this->pickemService->getUserSubmissionsForWeek(
            $schedules->pluck('espn_event_id'),
            Auth::id()
        );

        $data = compact('weeks', 'schedules', 'userSubmissions', 'game_week');

        return $request->expectsJson()
            ? response()->json($data, 200)
            : view('pickem.show', $data);
    }

    public function pickWinner(PickWinnerRequest $request)
    {
        try {
            $userId = Auth::id();
            $now = Carbon::now();
            $gameWeek = $request->input('game_week') ??
                $this->gameWeekService->determineGameWeek($request, null);

            $this->pickemService->processUserPicks(
                $request->validated()['event_ids'],
                $request->validated()['team_ids'],
                $userId,
                $now
            );

            $userPicks = $this->pickemService->getUserPicksForEmail(
                $userId,
                $request->validated()['event_ids']
            );


            return $this->pickemService->generateResponse(
                $request,
                'Your picks have been submitted successfully!',
                200
            );
        } catch (QueryException $e) {
            return $this->pickemService->generateResponse(
                $request,
                'There was an issue submitting your picks. Please try again.',
                500
            );
        }
    }

    public function showLeaderboard(Request $request)
    {
        $this->validateUserTeamAccess($request);

        $game_week_input = $request->input('game_week');
        $game_week = $this->gameWeekService->determineGameWeek($request, $game_week_input);
        $userId = Auth::id();
        $user = Auth::user();
        $team_id = $user->current_team_id;

        $sort = $request->input('sort', 'correct_picks');
        $direction = $request->input('direction', 'desc');

        // Calculate the 3-week period
        $period = floor(($game_week - 1) / 3);
        $period_start_week = $period * 3 + 1;
        $period_end_week = $period_start_week + 2;

        // Get the leaderboard data
        $leaderboard = $this->leaderboardService->getLeaderboard($game_week, $team_id, $sort, $direction);

        // Get all picks for the user for the specified week
        $allPicks = $this->leaderboardService->getUserPicksForWeek($userId, $game_week, $team_id);

        $data = [
            'games' => $this->gameWeekService->getGameWeeks(),
            'leaderboard' => $leaderboard,
            'allPicks' => $allPicks,
            'game_week' => $game_week_input,
            'sort' => $sort,
            'direction' => $direction,
            'period_start_week' => $period_start_week,
            'period_end_week' => $period_end_week,
        ];

        return $request->expectsJson()
            ? response()->json($data, 200)
            : view('pickem.index', $data);
    }

    protected function validateUserTeamAccess(Request $request): void
    {
        $user = Auth::user();

        $isAuthorized = DB::table('team_user')
            ->where('user_id', Auth::id())
            ->where('team_id', $user->current_team_id)
            ->exists();

        if (!$isAuthorized) {
            abort(403, 'Unauthorized access to this teams leaderboard.');
        }
    }


}