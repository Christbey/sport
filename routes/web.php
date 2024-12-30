<?php

use App\Http\Controllers\{AccessRequestController,
    AnalyzeNflTrendsController,
    ChatGPTController,
    ForgeApiController,
    NflNewsController,
    NflTrendsController,
    PaymentController,
    PaymentMethodController,
    PickemController,
    PlayerPropBetController,
    PlayerTrendsController,
    PostController,
    SitemapController,
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
use App\Http\Controllers\PlayerStatsController;
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


    // NFL Elo Routes
    Route::prefix('elo')->name('elo.')->controller(NflEloRatingController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/predictions', 'prediction')->name('predictions');
        Route::get('/show/{gameId}', 'show')->name('show');
    });
});

Route::get('/nfl/trends', [NflTrendsController::class, 'show'])->name('nfl.trends.config');
Route::get('/trends/compare', [NflTrendsController::class, 'compare'])->name('trends.compare');

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

    Route::post('/clear-conversations', [ChatGPTController::class, 'clearConversations'])
        ->name('clear-conversations')
        ->middleware('auth');
});


// Stripe Webhook
Route::post('stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook')
    ->middleware(VerifyWebhookSignature::class)
    ->withoutMiddleware([TrackUserSession::class]);

Route::post('broadcasting/auth', function () {
    return auth()->check() ? auth()->user() : abort(403);
})->name('broadcasting.auth');

Route::middleware(['auth'])->group(function () {
    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])->name('subscription.index');
        Route::post('checkout', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
        Route::get('success', [SubscriptionController::class, 'success'])->name('subscription.success');
        Route::get('cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
        Route::get('manage', [SubscriptionController::class, 'manage'])->name('subscription.manage');
        Route::post('cancel', [SubscriptionController::class, 'cancelSubscription'])->name('subscription.cancel-subscription');
        //Route::post('resume', [SubscriptionController::class, 'resumeSubscription'])->name('subscription.resume');
        Route::post('update-plan', [SubscriptionController::class, 'updatePlan'])->name('subscription.update-plan');
        Route::post('add-item', [SubscriptionController::class, 'addItem'])->name('subscription.add-item');
        Route::post('remove-item', [SubscriptionController::class, 'removeItem'])->name('subscription.remove-item');
        Route::post('update-quantity', [SubscriptionController::class, 'updateItemQuantity'])->name('subscription.update-quantity');
        Route::get('billing-portal', [SubscriptionController::class, 'billingPortal'])->name('subscription.billing-portal');
    });
});

// In routes/web.php
Route::middleware(['auth'])->group(function () {
    // ... your other routes ...

    Route::middleware(['auth'])->group(function () {
        Route::post('/subscription/change-plan', [SubscriptionController::class, 'changePlan'])
            ->name('subscription.change-plan');

        Route::get('/subscription/change-plan/show', [SubscriptionController::class, 'showChangePlan'])
            ->name('subscription.change-plan.show');
    });
});

Route::middleware(['auth'])->group(function () {
    Route::get('stripe/payment/{id}', [PaymentController::class, 'show'])->name('cashier.payment');
    Route::post('stripe/payment/process', [PaymentController::class, 'processPayment'])->name('cashier.payment.process');
    Route::get('payment/success', [PaymentController::class, 'success'])->name('payment.success');
    Route::get('payment/failure', [PaymentController::class, 'failure'])->name('payment.failure');
});

Route::post('subscriptions/preview-change', [SubscriptionController::class, 'previewPlanChange'])
    ->name('subscription.preview-change');

Route::post('subscriptions/add-payment-method', [SubscriptionController::class, 'addPaymentMethodAndChangePlan'])
    ->name('subscription.add-payment-method');

Route::middleware(['auth'])->group(function () {
    Route::post('/subscription/add-payment-and-change-plan', [SubscriptionController::class, 'addPaymentMethodAndChangePlan'])
        ->name('subscription.add-payment-and-change-plan');
});

Route::post('/subscription/confirm-payment', [SubscriptionController::class, 'confirmPayment'])
    ->name('subscription.confirm-payment');

Route::middleware(['auth'])->group(function () {
    Route::prefix('payment')->name('payment.')->group(function () {
        Route::get('methods', [PaymentController::class, 'paymentMethods'])->name('methods');
        Route::post('methods/add', [PaymentController::class, 'addPaymentMethod'])->name('add-method');
        Route::put('methods/{paymentMethodId}/default', [PaymentController::class, 'setDefaultPaymentMethod'])->name('set-default');
        Route::delete('methods/{paymentMethodId}', [PaymentController::class, 'removePaymentMethod'])->name('remove-method');
    });
});

Route::middleware(['auth'])->group(function () {
    Route::get('/payment-methods/create', [PaymentMethodController::class, 'create'])->name('payment.create');
    Route::post('/payment-methods', [PaymentMethodController::class, 'store'])->name('payment.store');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/subscription/resume', [SubscriptionController::class, 'showResume'])
        ->name('subscription.resume.show');
    Route::post('/subscription/resume', [SubscriptionController::class, 'resumeSubscription'])
        ->name('subscription.resume');
});

Route::post('/chat', [ChatGPTController::class, 'chat'])
    ->middleware(['auth', 'web']);

// A quick test route just to see if we can get a completion in a non-streaming way

Route::get('/nfl/predictions/table', [NflEloRatingController::class, 'showTable'])
    ->name('nfl.elo.table');


Route::get('/posts', [PostController::class, 'index'])
    ->name('posts.index');

Route::get('/posts/season={season}/week={week}/game-date={game_date}/{slug}', [PostController::class, 'show'])
    ->name('posts.show')
    ->where([
        'season' => '\d{4}',                   // Four-digit year
        'week' => '\d+',                      // One or more digits
        'game_date' => '\d{4}-\d{2}-\d{2}',        // Date format YYYY-MM-DD
        'slug' => '[A-Za-z0-9\-]+'           // Slug containing letters, numbers, and dashes
    ]);

Route::get('/sitemap.xml', [App\Http\Controllers\SitemapController::class, 'index']);
Route::get('/generate-sitemap', [SitemapController::class, 'generate']);

Route::get('/analyze-nfl-trends', [AnalyzeNflTrendsController::class, 'analyze']);
Route::get('/nfl-trends/filter', [AnalyzeNflTrendsController::class, 'filter'])->name('nfl.trends.filter');
Route::get('/nfl-trends/compare', [AnalyzeNflTrendsController::class, 'compareTeams'])->name('nfl.trends.compare');
Route::get('/nfl/team-prediction', [NflEloRatingController::class, 'getTeamPrediction'])->name('nfl.team_prediction');

Route::get('/player-stats', [PlayerStatsController::class, 'index'])->name('nfl.player-stats');

Route::get('/player-trends', [PlayerTrendsController::class, 'index'])->name('player.trends.index');


Route::post('/player-trends/fetch-odds', [PlayerTrendsController::class, 'fetchPlayerOdds'])
    ->name('player-trends.fetch-odds');

Route::get('/nba/player-prop-bets', [PlayerPropBetController::class, 'index'])->name('player-prop-bets.index');
