<?php

use App\Http\Controllers\AccessRequestController;
use App\Http\Controllers\Api\CollegeBasketballHypotheticalController;
use App\Http\Controllers\Api\CollegeFootballHypotheticalController;
use App\Http\Controllers\Api\CollegeFootballNoteController;
use App\Http\Controllers\Api\CoversController;
use App\Http\Controllers\Api\Espn\EspnQbrController;
use App\Http\Controllers\Api\TeamRankingController;
use App\Http\Controllers\Api\TeamStatsController;
use App\Http\Controllers\ForgeApiController;
use App\Http\Controllers\Nfl\NflEloRatingController;
use App\Http\Controllers\Nfl\NflSheetController;
use App\Http\Controllers\Nfl\NflStatsViewController;
use App\Http\Controllers\NflNewsController;
use App\Http\Controllers\NflTrendsController;
use App\Http\Controllers\PickemController;
use Illuminate\Support\Facades\Route;

// Basic Routes
Route::get('/', function () {
    return view('welcome');
});

Route::get('/test/form', function () {
    return view('curbbliss');
});

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::post('/request-access', [AccessRequestController::class, 'store'])
        ->name('request-access');
});

// Protected Routes
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Pick'em Routes
    Route::prefix('pickem')->name('pickem.')->group(function () {
        Route::get('/schedule/{game_week?}', [PickemController::class, 'showTeamSchedule'])->name('schedule');
        Route::post('/pick-winner', [PickemController::class, 'pickWinner'])->name('pickWinner');
        Route::get('/leaderboard', [PickemController::class, 'showLeaderboard'])->name('leaderboard');
    });

    // NFL Routes
    Route::prefix('nfl')->name('nfl.')->group(function () {
        Route::get('/detail', [NflSheetController::class, 'index'])->name('detail');
        Route::post('/sheet/store', [NflSheetController::class, 'store'])->name('sheet.store');

        // NFL Stats
        Route::prefix('stats')->name('stats.')->group(function () {
            Route::get('/', [TeamStatsController::class, 'index'])->name('index');
            Route::get('/analysis/{queryType}', [TeamStatsController::class, 'showAnalysis'])->name('show');
        });
    });

    // Admin Routes
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::prefix('access-requests')->name('access-requests.')->group(function () {
            Route::get('/', [AccessRequestController::class, 'index'])->name('index');
            Route::post('/{accessRequest}/approve', [AccessRequestController::class, 'approve'])->name('approve');
            Route::post('/{accessRequest}/deny', [AccessRequestController::class, 'deny'])->name('deny');
        });
    });

    // College Football Routes
    Route::prefix('cfb')->name('cfb.')->group(function () {
        Route::post('/notes', [CollegeFootballNoteController::class, 'store'])->name('notes.store');
        Route::get('/notes', [CollegeFootballNoteController::class, 'index'])->name('notes.index');
        Route::patch('/hypothetical/{id}/correct', [CollegeFootballHypotheticalController::class, 'updateCorrect'])
            ->name('hypothetical.correct');
    });

    // Forge Routes
    Route::prefix('forge')->name('forge.')->group(function () {
        Route::get('/servers', [ForgeApiController::class, 'listServers'])->name('servers.index');
        Route::prefix('servers/{serverId}')->group(function () {
            Route::get('/sites', [ForgeApiController::class, 'listSites'])->name('sites.index');
            Route::prefix('sites/{siteId}')->group(function () {
                Route::post('/commands', [ForgeApiController::class, 'runSiteCommand'])->name('commands.run');
                Route::get('/commands', [ForgeApiController::class, 'listCommands'])->name('commands.index');
                Route::post('/deploy', [ForgeApiController::class, 'deploySite'])->name('sites.deploy');
            });
        });
    });
});

// Public NFL Routes
Route::prefix('nfl')->name('nfl.')->group(function () {
    Route::get('/receivers', [NflStatsViewController::class, 'showReceivers']);
    Route::get('/rushers', [NflStatsViewController::class, 'showRushers']);
    Route::get('/qbr/{week}', [EspnQbrController::class, 'fetchQbrData'])->name('qbr');
    Route::get('/news', [NflNewsController::class, 'index'])->name('news.index');
    Route::get('/trends', [NflTrendsController::class, 'show'])->name('trends.config');

    // NFL Elo Routes
    Route::prefix('elo')->name('elo.')->group(function () {
        Route::get('/', [NflEloRatingController::class, 'index'])->name('index');
        Route::get('/predictions', [NflEloRatingController::class, 'prediction'])->name('predictions');
        Route::get('/show/{gameId}', [NflEloRatingController::class, 'show'])->name('show');
    });
});

// Public Team Rankings Routes
Route::prefix('team-rankings')->name('team_rankings.')->group(function () {
    Route::get('/scoring', [TeamRankingController::class, 'showScoring'])->name('scoring');
    Route::get('/rankings', [TeamRankingController::class, 'showRankings'])->name('rankings');
});

// Covers Routes
Route::prefix('covers')->name('covers.')->group(function () {
    Route::get('/games', [CoversController::class, 'showGames'])->name('games');
    Route::get('/game/{covers_game_id}', [CoversController::class, 'getGameData'])->name('game');
});

// College Sports Routes
Route::prefix('college-basketball')->name('cbb.')->group(function () {
    Route::get('/', [CollegeBasketballHypotheticalController::class, 'index'])->name('index');
    Route::get('/{id}', [CollegeBasketballHypotheticalController::class, 'show'])->name('show');
});

Route::prefix('cfb')->name('cfb.')->group(function () {
    Route::get('/', [CollegeFootballHypotheticalController::class, 'index'])->name('index');
    Route::get('/{game_id}', [CollegeFootballHypotheticalController::class, 'show'])->name('hypothetical.show');
});