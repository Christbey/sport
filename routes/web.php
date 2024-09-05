<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PickemController;

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


Route::get('/pickem/schedule/{week_id?}', [PickemController::class, 'showTeamSchedule'])->name('pickem.schedule');
Route::get('/pickem/filter', [PickemController::class, 'filter'])->name('pickem.filter');

// Route for pickWinner
Route::post('/pickem/pick-winner', [PickemController::class, 'pickWinner'])->name('pickem.pickWinner');
Route::get('/pickem/leaderboard', [PickemController::class, 'showLeaderboard'])->name('picks.leaderboard');
});
