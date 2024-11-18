<?php

use App\Http\Controllers\AccessRequestController;
use App\Http\Controllers\Api\CoversController;
use App\Http\Controllers\Api\Espn\EspnQbrController;
use App\Http\Controllers\Api\TeamRankingController;
use App\Http\Controllers\Cfb\CollegeFootballHypotheticalController;
use App\Http\Controllers\Cfb\CollegeFootballNoteController;
use App\Http\Controllers\CollegeBasketballHypotheticalController;
use App\Http\Controllers\ForgeApiController;
use App\Http\Controllers\InvitedUserRegistrationController;
use App\Http\Controllers\Nfl\NflEloRatingController;
use App\Http\Controllers\Nfl\NflSheetController;
use App\Http\Controllers\Nfl\NflStatsViewController;
use App\Http\Controllers\Nfl\TeamStatsController;
use App\Http\Controllers\NflNewsController;
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
// Route to load the scoring stats view
Route::get('/scoring', [TeamRankingController::class, 'showScoring'])->name('team_rankings.scoring');

// Route to load the rankings stats view
Route::get('/rankings', [TeamRankingController::class, 'showRankings'])->name('team_rankings.rankings');
// Fetch all games
Route::get('/covers/games', [CoversController::class, 'showGames'])->name('covers.games');

// Fetch a single game's details by its covers_game_id
Route::get('/api/covers/game/{covers_game_id}', [CoversController::class, 'getGameData']);
// In routes/web.php
Route::get('/nfl/qbr/{week}', [EspnQbrController::class, 'fetchQbrData'])->name('espn.qbr');
Route::get('/nfl-elo-ratings', [NflEloRatingController::class, 'index'])->name('nfl.elo');
Route::get('/nfl-elo-predictions', [NflEloRatingController::class, 'prediction'])->name('nfl.elo.predictions');

Route::get('/forge/servers', [ForgeApiController::class, 'listServers'])->name('forge.servers.index');
Route::get('/forge/servers/{serverId}/sites', [ForgeApiController::class, 'listSites'])->name('forge.sites.index');
Route::post('/forge/servers/{serverId}/sites/{siteId}/commands', [ForgeApiController::class, 'runSiteCommand'])->name('forge.commands.run');
Route::post('/forge/servers/{serverId}/sites/{siteId}/deploy', [ForgeApiController::class, 'deploySite'])->name('forge.sites.deploy');
Route::get('/forge/servers/{serverId}/sites/{siteId}/commands', [ForgeApiController::class, 'listCommands'])->name('forge.commands.index');

Route::get('/nfl/news', [NflNewsController::class, 'index'])->name('nfl.news.index');

Route::get('/nfl/elo/show/{gameId}', [NflEloRatingController::class, 'show'])->name('nfl.elo.show');

Route::get('/college-basketball-hypotheticals', [CollegeBasketballHypotheticalController::class, 'index'])->name('cbb.index');

Route::post('/request-access', [AccessRequestController::class, 'store'])
    ->name('request-access')
    ->middleware('guest');

// Guest routes
Route::post('/request-access', [AccessRequestController::class, 'store'])
    ->name('request-access')
    ->middleware('guest');


// Authenticated routes
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/admin/access-requests', [App\Http\Controllers\AccessRequestController::class, 'index'])
        ->name('admin.access-requests.index');

    Route::post('/admin/access-requests/{accessRequest}/approve', [App\Http\Controllers\AccessRequestController::class, 'approve'])
        ->name('admin.access-requests.approve');

    Route::post('/admin/access-requests/{accessRequest}/deny', [App\Http\Controllers\AccessRequestController::class, 'deny'])
        ->name('admin.access-requests.deny');
});
