<?php

use App\Http\Controllers\Api\CollegeBasketballHypotheticalController;
use App\Http\Controllers\Api\CollegeFootballNoteController;
use App\Http\Controllers\Api\Espn\EspnQbrController;
use App\Http\Controllers\Api\Espn\EspnTeamProjectionController;
use App\Http\Controllers\Api\EspnAthleteController;
use App\Http\Controllers\Api\EspnAthleteEventLogController;
use App\Http\Controllers\Api\NflTeamController;
use App\Http\Controllers\Api\NflTeamScheduleController;
use App\Http\Controllers\Api\TeamRankingController;
use App\Http\Controllers\Api\TeamStatsController;
use App\Http\Controllers\Cfb\CollegeFootballDataController;
use App\Http\Controllers\ForgeApiController;
use App\Http\Controllers\Nfl\NflRapidApiController;
use App\Http\Controllers\PickemController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/games', [CollegeFootballDataController::class, 'getGames']);
Route::get('/calendar', [CollegeFootballDataController::class, 'getCalendar']);
Route::get('/games/media', [CollegeFootballDataController::class, 'getGameMedia']);
Route::get('/games/weather', [CollegeFootballDataController::class, 'getGameWeather']);
Route::get('/games/players', [CollegeFootballDataController::class, 'getPlayerGameStats']);
Route::get('/games/teams', [CollegeFootballDataController::class, 'getTeamGameStats']);
Route::get('/game/box/advanced', [CollegeFootballDataController::class, 'getAdvancedBoxScore']);
Route::get('/drives', [CollegeFootballDataController::class, 'getDrives']);
Route::get('/plays', [CollegeFootballDataController::class, 'getPlays']);
Route::get('/live/plays', [CollegeFootballDataController::class, 'getLivePlays']);
Route::get('/play/types', [CollegeFootballDataController::class, 'getPlayTypes']);
Route::get('/play/stats', [CollegeFootballDataController::class, 'getPlayStats']);
Route::get('/conferences', [CollegeFootballDataController::class, 'getConferences']);
Route::get('/teams', [CollegeFootballDataController::class, 'getTeams']);
Route::get('/teams/fbs', [CollegeFootballDataController::class, 'getFbsTeams']);
Route::get('/roster', [CollegeFootballDataController::class, 'getRoster']);
Route::get('/talent', [CollegeFootballDataController::class, 'getTalent']);
Route::get('/teams/matchup', [CollegeFootballDataController::class, 'getTeamMatchup']);
Route::get('/venues', [CollegeFootballDataController::class, 'getVenues']);
Route::get('/coaches', [CollegeFootballDataController::class, 'getCoaches']);
Route::get('/rankings', [CollegeFootballDataController::class, 'getRankings']);
Route::get('/lines', [CollegeFootballDataController::class, 'getLines']);
Route::get('/ratings/sp', [CollegeFootballDataController::class, 'getSPRatings']);
Route::get('/ratings/srs', [CollegeFootballDataController::class, 'getSRSRatings']);
Route::get('/ratings/sp/conferences', [CollegeFootballDataController::class, 'getConferenceSPRatings']);
Route::get('/ratings/elo', [CollegeFootballDataController::class, 'getEloRatings']);
Route::get('/ratings/fpi', [CollegeFootballDataController::class, 'getFPIRatings']);
Route::get('/ppa/predicted', [CollegeFootballDataController::class, 'getPredictedPoints']);
Route::get('/ppa/teams', [CollegeFootballDataController::class, 'getTeamPPA']);
Route::get('/ppa/games', [CollegeFootballDataController::class, 'getGamePPA']);
Route::get('/ppa/players/games', [CollegeFootballDataController::class, 'getPlayerGamePPA']);
Route::get('/ppa/players/season', [CollegeFootballDataController::class, 'getPlayerSeasonPPA']);
Route::get('/metrics/fg/ep', [CollegeFootballDataController::class, 'getFGEP']);
Route::get('/metrics/wp', [CollegeFootballDataController::class, 'getWinProbabilityData']);
Route::get('/metrics/wp/pregame', [CollegeFootballDataController::class, 'getPregameWinProbabilities']);
Route::get('/stats/season', [CollegeFootballDataController::class, 'getTeamSeasonStats']);
Route::get('/stats/season/advanced', [CollegeFootballDataController::class, 'getAdvancedTeamSeasonStats']);
Route::get('/stats/game/advanced', [CollegeFootballDataController::class, 'getAdvancedTeamGameStats']);
Route::get('/stats/categories', [CollegeFootballDataController::class, 'getStatCategories']);
Route::get('/player/search', [CollegeFootballDataController::class, 'playerSearch']);
Route::get('/player/usage', [CollegeFootballDataController::class, 'getPlayerUsage']);
Route::get('/player/returning', [CollegeFootballDataController::class, 'getReturningProduction']);
Route::get('/stats/player/season', [CollegeFootballDataController::class, 'getPlayerSeasonStats']);
Route::get('/player/portal', [CollegeFootballDataController::class, 'getTransferPortal']);

Route::get('/nfl/betting-odds', [NflRapidApiController::class, 'getNFLBettingOdds']);
Route::get('/nfl/news', [NflRapidApiController::class, 'getNFLNews']);
Route::get('/nfl/scores-only', [NflRapidApiController::class, 'getNFLScoresOnly']);
Route::get('/nfl/team-schedule', [NflRapidApiController::class, 'getNFLTeamSchedule']);
Route::get('/nfl/teams', [NflRapidApiController::class, 'getNFLTeams']);
Route::get('/nfl/player-info', [NflRapidApiController::class, 'getNFLPlayerInfo']);
Route::get('/nfl/games-for-player', [NflRapidApiController::class, 'getNFLGamesForPlayer']);
Route::get('/nfl/player-list', [NflRapidApiController::class, 'getNFLPlayerList']);
Route::get('/nfl/team-roster', [NflRapidApiController::class, 'getNFLTeamRoster'])->name('nfl.teamRoster');
Route::get('/nfl/boxscore', [NflRapidApiController::class, 'getNFLBoxScore'])->name('nfl.boxscore');
Route::get('/nfl-player-stats', [TeamStatsController::class, 'index']);

Route::prefix('team-rankings')->group(function () {
    // Route for fetching stat data
    Route::get('/stat/{category}/{stat}', [TeamRankingController::class, 'getStat'])->name('api.team-rankings.stat');

    // Route for fetching rankings
    Route::get('/ranking/{rankingType}', [TeamRankingController::class, 'getRanking'])->name('api.team-rankings.fetch');
});

Route::get('/pickem/team-schedule', [PickemController::class, 'showTeamSchedule']);
Route::post('pickem/pick', [PickemController::class, 'pickWinner']);
Route::get('pickem/leaderboard', [PickemController::class, 'showLeaderboard']);
Route::get('/nfl/qbr/{week}', [EspnQbrController::class, 'fetchQbrData']);
Route::get('/nfl/team/{teamId}/projection', [EspnTeamProjectionController::class, 'fetchTeamProjection']);
Route::get('/nfl/athletes', [EspnAthleteController::class, 'fetchAthletes']);
Route::get('/espn/athletes/{athleteId}/seasons/{season}/eventlog', [EspnAthleteEventLogController::class, 'fetchAthleteEventLog']);
Route::get('/espn/athletes/{athleteId}/events/{eventId}/teams/{teamId}/statistics', [EspnAthleteEventLogController::class, 'fetchAthleteEventStatistics']);

Route::get('/forge/servers', [ForgeApiController::class, 'listServers']);
Route::get('/forge/servers/{serverId}/sites', [ForgeApiController::class, 'listSites']);
Route::post('/forge/servers/{serverId}/sites/{siteId}/commands', [ForgeApiController::class, 'runSiteCommand']);
Route::post('/forge/servers/{serverId}/sites/{siteId}/deploy', [ForgeApiController::class, 'deploySite']);
Route::get('/forge/servers/{serverId}/sites/{siteId}/commands', [ForgeApiController::class, 'listCommandHistory']);
Route::get('/forge/servers/{serverId}/events/{eventId}', [ForgeApiController::class, 'getCommandOutput']);
Route::get('/forge/servers/{serverId}/sites/{siteId}/commands/{commandId}', [ForgeApiController::class, 'getCommandStatus']);

//College Basketball
// routes/api.php

Route::prefix('v1')->group(function () {
    Route::apiResource('college-basketball', CollegeBasketballHypotheticalController::class)
        ->only(['index', 'show']);
});
// routes/api.php
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::post('/cfb/notes', [CollegeFootballNoteController::class, 'store']);
    Route::get('/cfb/notes', [CollegeFootballNoteController::class, 'index']);
});

Route::get('/team-stats/recent-games', [TeamStatsController::class, 'getRecentGames']);
Route::apiResource('nfl/teams', NflTeamController::class)->names([
    'index' => 'api.nfl.teams.index',
    'show' => 'api.nfl.teams.show',
    //'store' => 'api.nfl.teams.store',
    //'update' => 'api.nfl.teams.update',
    //'destroy' => 'api.nfl.teams.destroy',
]);
Route::get('/nfl/schedules', [NflTeamScheduleController::class, 'index']);
Route::get('/nfl/schedules/{teamId}', [NflTeamScheduleController::class, 'show']);
Route::get('/nfl/schedules/{teamId}/date-range', [NflTeamScheduleController::class, 'byDateRange']);
Route::get('/nfl/schedules/{teamId}/recent-games', [NflTeamScheduleController::class, 'recentGames']);

Route::prefix('nfl/stats')->group(function () {
    Route::get('/{queryType}/data', [TeamStatsController::class, 'getAnalysisData']);
    Route::get('/recent-games', [TeamStatsController::class, 'getRecentGames']);
});