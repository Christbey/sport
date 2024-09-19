<?php

use App\Http\Controllers\CollegeFootballHypotheticalController;
use App\Http\Controllers\CollegeFootballNoteController;
use App\Http\Controllers\NflSheetController;
use App\Http\Controllers\PickemController;
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

use App\Http\Controllers\PlayerVsOpponentController;

Route::get('/nfl/player-vs-opponent', [PlayerVsOpponentController::class, 'showForm'])->name('playerVsOpponent.form');
Route::post('/nfl/player-vs-opponent', [PlayerVsOpponentController::class, 'index'])->name('playerVsOpponent.results');
