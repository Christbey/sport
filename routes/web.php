<?php

use App\Http\Controllers\{AccessRequestController,
    BillingPortalController,
    ChatGPTController,
    ForgeApiController,
    NflNewsController,
    NflTrendsController,
    PickemController,
    StripeWebhookController,
    SubscriptionController,
    UserRoleController};
use App\Http\Controllers\Api\{CollegeBasketballHypotheticalController,
    CollegeFootballHypotheticalController,
    CollegeFootballNoteController,
    CoversController,
    TeamRankingController,
    TeamStatsController};
use App\Http\Controllers\Api\Espn\EspnQbrController;
use App\Http\Controllers\Nfl\{NflEloRatingController, NflSheetController,};
use App\Http\Middleware\{TrackUserSession};
use Illuminate\Support\Facades\Route;
use Laravel\Cashier\Http\Middleware\VerifyWebhookSignature;

// Public Routes
Route::get('/', function () {
    if (auth()->check()) {
        return view('dashboard'); // Uses app.blade.php
    }
    return view('welcome'); // Uses guest.blade.php
})->name('home');
Route::view('/test/form', 'curbbliss');

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::post('/request-access', [AccessRequestController::class, 'store'])->name('request-access');
});

// Authentication Required Routes
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {
    // Dashboard
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    // Pick'em Routes
    Route::prefix('pickem')->name('pickem.')->group(function () {
        Route::controller(PickemController::class)->group(function () {
            Route::get('/schedule/{game_week?}', 'showTeamSchedule')->name('schedule');
            Route::post('/pick-winner', 'pickWinner')->name('pickWinner');
            Route::get('/leaderboard', 'showLeaderboard')->name('leaderboard');
        });
    });

    // NFL Routes
    Route::prefix('nfl')->name('nfl.')->group(function () {
        Route::controller(NflSheetController::class)->group(function () {
            Route::get('/detail', 'index')->name('detail');
            Route::post('/sheet/store', 'store')->name('sheet.store');
        });

        // NFL Stats
        Route::prefix('stats')->name('stats.')->controller(TeamStatsController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/analysis/{queryType}', 'showAnalysis')->name('show');
        });
    });

    // Admin Routes
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::prefix('access-requests')->name('access-requests.')->controller(AccessRequestController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/{accessRequest}/approve', 'approve')->name('approve');
            Route::post('/{accessRequest}/deny', 'deny')->name('deny');
        });
    });

    // College Football Routes
    Route::prefix('cfb')->name('cfb.')->group(function () {
        Route::controller(CollegeFootballNoteController::class)->group(function () {
            Route::post('/notes', 'store')->name('notes.store');
            Route::get('/notes', 'index')->name('notes.index');
        });
        Route::patch('/hypothetical/{id}/correct', [CollegeFootballHypotheticalController::class, 'updateCorrect'])
            ->name('hypothetical.correct');
    });

    // Forge Routes
    Route::prefix('forge')->name('forge.')->controller(ForgeApiController::class)->group(function () {
        Route::get('/servers', 'listServers')->name('servers.index');
        Route::prefix('servers/{serverId}')->group(function () {
            Route::get('/sites', 'listSites')->name('sites.index');
            Route::prefix('sites/{siteId}')->group(function () {
                Route::post('/commands', 'runSiteCommand')->name('commands.run');
                Route::get('/commands', 'listCommands')->name('commands.index');
                Route::post('/deploy', 'deploySite')->name('sites.deploy');
            });
        });
    });
});

// Admin Only Routes
Route::middleware(['role:admin'])->prefix('nfl')->name('nfl.')->group(function () {


    Route::get('/qbr/{week}', [EspnQbrController::class, 'fetchQbrData'])->name('qbr');
    Route::get('/news', [NflNewsController::class, 'index'])->name('news.index');
    Route::get('/trends', [NflTrendsController::class, 'show'])->name('trends.config');
    Route::get('/trends/compare', [NflTrendsController::class, 'compare'])->name('trends.compare');

    // NFL Elo Routes
    Route::prefix('elo')->name('elo.')->controller(NflEloRatingController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/predictions', 'prediction')->name('predictions');
        Route::get('/show/{gameId}', 'show')->name('show');
    });
});

// Public Sports Data Routes
Route::prefix('team-rankings')->name('team_rankings.')->controller(TeamRankingController::class)->group(function () {
    Route::get('/scoring', 'showScoring')->name('scoring');
    Route::get('/rankings', 'showRankings')->name('rankings');
});

Route::prefix('covers')->name('covers.')->controller(CoversController::class)->group(function () {
    Route::get('/games', 'showGames')->name('games');
    Route::get('/game/{covers_game_id}', 'getGameData')->name('game');
});

// College Sports Routes
Route::prefix('college-basketball')->name('cbb.')->controller(CollegeBasketballHypotheticalController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{id}', 'show')->name('show');
});

Route::prefix('cfb')->name('cfb.')->controller(CollegeFootballHypotheticalController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{game_id}', 'show')->name('hypothetical.show');
});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/roles', function () {
        return view('roles.index');
    })->name('roles.index');
});

Route::get('permissions', function () {
    return view('permissions.index');
})->name('permissions.index');


Route::controller(UserRoleController::class)->group(function () {
    Route::get('user-roles', 'index')->name('user-roles.index');
    Route::get('user-roles/{user}/edit', 'edit')->name('user-roles.edit');
    Route::put('user-roles/{user}', 'update')->name('user-roles.update');
});

// ChatGPT Routes
// ChatGPT Routes
Route::controller(ChatGPTController::class)->group(function () {
    Route::get('/ask-chatgpt', 'showChat')
        ->name('show-chatgpt')
        ->middleware('auth');

    Route::post('/ask-chatgpt', 'ask')
        ->name('ask-chatgpt')
        ->middleware('auth');

    Route::get('/load-chat', 'loadChat')
        ->name('load-chat')
        ->middleware('auth');

    Route::post('/clear-conversations', 'clearConversations')
        ->name('clear-conversations')
        ->middleware('auth');
});

// Subscription and Billing Routes
Route::middleware(['auth'])->group(function () {
    Route::controller(SubscriptionController::class)->prefix('subscriptions')->name('subscription.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/checkout', 'checkout')->name('checkout');
        Route::get('/success', 'success')->name('success');
        Route::get('/cancel', 'cancel')->name('cancel');
    });

    Route::get('/billing-portal', [BillingPortalController::class, 'redirectToPortal'])
        ->name('billing.portal');
});

// Stripe Webhook
Route::post('stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook')
    ->middleware(VerifyWebhookSignature::class)
    ->withoutMiddleware([TrackUserSession::class]);

Route::post('broadcasting/auth', function () {
    return auth()->check() ? auth()->user() : abort(403);
})->name('broadcasting.auth');