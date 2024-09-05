<?php
use App\Http\Controllers\NflRapidApiController;

use App\Http\Controllers\CollegeFootballDataController;
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
