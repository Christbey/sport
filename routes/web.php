<?php

use App\Http\Controllers\CollegeFootballHypotheticalController;
use App\Http\Controllers\PickemController;
use Illuminate\Support\Facades\Route;

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
});

