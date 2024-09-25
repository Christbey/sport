<?php

use App\Http\Controllers\CollegeFootballHypotheticalController;
use App\Http\Controllers\CollegeFootballNoteController;
use App\Http\Controllers\NflSheetController;
use App\Http\Controllers\NflStatsViewController;
use App\Http\Controllers\PickemController;
use App\Http\Controllers\TeamRankingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TeamStatsController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Route to show the schedule and matchups using game_week
    Route::get('/pickem/schedule/{game_week?}', [PickemController::class, 'showTeamSchedule'])->name('pickem.schedule');

    // Route to submit picks
    Route::post('/pickem/pick-winner', [PickemController::class, 'pickWinner'])->name('pickem.pickWinner');

    // Route for the leaderboard
    Route::get('/pickem/leaderboard', [PickemController::class, 'showLeaderboard'])->name('picks.leaderboard');

    // Route to show the hypothetical matchups
    Route::get('/cfb/hypotheticals', [CollegeFootballHypotheticalController::class, 'index'])->name('cfb.index');
    Route::get('/cfb/detail/{game_id}', [CollegeFootballHypotheticalController::class, 'show'])->name('cfb.hypothetical.show');
    Route::post('/cfb/notes', [CollegeFootballNoteController::class, 'store'])->name('cfb.notes.store');
    Route::patch('/cfb/hypothetical/{id}/correct', [CollegeFootballHypotheticalController::class, 'updateCorrect'])->name('cfb.hypothetical.correct');

    // Route to show the NFL sheet
    Route::get('/nfl/detail', [NflSheetController::class, 'index'])->name('nfl.detail');
    Route::post('/nfl/sheet/store', [NflSheetController::class, 'store'])->name('nfl.sheet.store');


    Route::get('/nfl/stats', [TeamStatsController::class, 'index'])->name('nfl.stats.index');
    Route::get('/nfl/stats/show', [TeamStatsController::class, 'getStats'])->name('nfl.stats.results');

});


Route::get('/nfl/receivers', [NflStatsViewController::class, 'showReceivers']);
Route::get('/nfl/rushers', [NflStatsViewController::class, 'showRushers']);
Route::get('/team-rankings/points-per-game', [TeamRankingController::class, 'fetchPointsPerGame']);
// Route to load Scoring Offense view
Route::get('/team-rankings/scoring-offense', function () {
    return view('team_rankings.scoring_offense');
})->name('team-rankings.scoring-offense');

// Dynamic route to fetch specific stat data
Route::get('/team-rankings/{category}/{stat}', [TeamRankingController::class, 'fetchStatData'])->name('team-rankings.stat');
