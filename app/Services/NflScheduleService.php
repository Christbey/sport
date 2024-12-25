<?php

namespace App\Services;

use App\Repositories\Nfl\{NflBoxScoreRepository,
    NflPlayByPlayRepository,
    NflPlayerStatRepository,
    NflTeamStatRepository};
use App\Repositories\NflTeamScheduleRepository;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class NflScheduleService
{
    private const SEASON_TYPES = [
        '1' => 'pre',
        '2' => 'reg',
        '3' => 'post'
    ];

    private string $apiHost = 'tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com';
    private string $apiKey;
    private Client $client;

    public function __construct(
        private NflTeamScheduleRepository $scheduleRepository,
        private NflBoxScoreRepository     $boxScoreRepository,
        private NflPlayerStatRepository   $playerStatRepository,
        private NflTeamStatRepository     $teamStatRepository,
        private NflPlayByPlayRepository   $playByPlayRepository
    )
    {
        $this->apiKey = config('services.rapidapi.key');
        $this->client = new Client();
    }

    public function updateScheduleForWeek(string $season, int $weekNumber, string $seasonType): void
    {
        try {
            $games = $this->fetchTeamSchedule($season, $weekNumber, $seasonType)['body'] ?? [];

            foreach ($games as $game) {
                Log::info("Processing game: {$game['gameID']}");

                $boxScoreData = $this->fetchBoxScore($game['gameID']);
                $mergedData = array_merge($game, $boxScoreData);

                // Check if both teamStats and DST data are present
                if (isset($boxScoreData['teamStats']) && isset($boxScoreData['DST'])) {
                    // Initialize an array to store points_allowed for both teams
                    $pointsAllowedMap = [];

                    foreach (['away', 'home'] as $key) {
                        if (isset($boxScoreData['teamStats'][$key])) {
                            $teamAbv = $boxScoreData['teamStats'][$key]['teamAbv'];
                            $teamID = $boxScoreData['teamStats'][$key]['teamID'];

                            // Extract ptsAllowed from DST
                            if (isset($boxScoreData['DST'][$key]['ptsAllowed'])) {
                                $pointsAllowed = (int)$boxScoreData['DST'][$key]['ptsAllowed'];
                                $boxScoreData['teamStats'][$key]['points_allowed'] = $pointsAllowed;
                                $pointsAllowedMap[$key] = $pointsAllowed;
                                Log::debug("Set points_allowed for team {$teamAbv} (ID: {$teamID}) to {$pointsAllowed}");
                            } else {
                                // Fallback: Use opponent's totalPts, homePts, or awayPts
                                $oppositeKey = $key === 'away' ? 'home' : 'away';
                                $opponentTotalPts = $boxScoreData['teamStats'][$oppositeKey]['totalPts'] ?? null;
                                $opponentHomePts = $game['homePts'] ?? null;
                                $opponentAwayPts = $game['awayPts'] ?? null;

                                if ($opponentTotalPts) {
                                    $pointsAllowed = (int)$opponentTotalPts;
                                    $boxScoreData['teamStats'][$key]['points_allowed'] = $pointsAllowed;
                                    $pointsAllowedMap[$key] = $pointsAllowed;
                                    Log::debug("Set points_allowed for team {$teamAbv} (ID: {$teamID}) from opponent's totalPts ({$opponentTotalPts})");
                                } elseif ($key === 'away' && $opponentHomePts) {
                                    $pointsAllowed = (int)$opponentHomePts;
                                    $boxScoreData['teamStats'][$key]['points_allowed'] = $pointsAllowed;
                                    $pointsAllowedMap[$key] = $pointsAllowed;
                                    Log::debug("Set points_allowed for team {$teamAbv} (ID: {$teamID}) from homePts ({$opponentHomePts})");
                                } elseif ($key === 'home' && $opponentAwayPts) {
                                    $pointsAllowed = (int)$opponentAwayPts;
                                    $boxScoreData['teamStats'][$key]['points_allowed'] = $pointsAllowed;
                                    $pointsAllowedMap[$key] = $pointsAllowed;
                                    Log::debug("Set points_allowed for team {$teamAbv} (ID: {$teamID}) from awayPts ({$opponentAwayPts})");
                                } else {
                                    // If all methods fail, set points_allowed to 0 and log a warning
                                    $boxScoreData['teamStats'][$key]['points_allowed'] = 0;
                                    $pointsAllowedMap[$key] = 0;
                                    Log::warning("points_allowed not found for team {$teamAbv} (ID: {$teamID}) in game {$game['gameID']}. Defaulting to 0.");
                                }
                            }
                        }
                    }

                    // Determine the result based on points_allowed comparison
                    if (count($pointsAllowedMap) == 2) { // Ensure both teams have points_allowed
                        $awayAllowed = $pointsAllowedMap['away'];
                        $homeAllowed = $pointsAllowedMap['home'];

                        if ($awayAllowed < $homeAllowed) {
                            $resultAway = 'W';
                            $resultHome = 'L';
                        } elseif ($awayAllowed > $homeAllowed) {
                            $resultAway = 'L';
                            $resultHome = 'W';
                        } else {
                            $resultAway = 'T';
                            $resultHome = 'T';
                        }

                        // Assign results to teamStats
                        $boxScoreData['teamStats']['away']['result'] = $resultAway;
                        $boxScoreData['teamStats']['home']['result'] = $resultHome;

                        Log::debug("Game {$game['gameID']}: Away team allowed {$awayAllowed} points, Home team allowed {$homeAllowed} points.");
                        Log::debug("Assigned results - Away: {$resultAway}, Home: {$resultHome}");
                    } else {
                        Log::warning("Insufficient points_allowed data for game {$game['gameID']}. Unable to determine result.");
                    }
                } else {
                    Log::warning("Missing teamStats or DST data for game {$game['gameID']}");
                }

                // Update/Create Schedule and Box Score
                $this->scheduleRepository->updateOrCreateFromRapidApi($mergedData, $season);
                $this->boxScoreRepository->updateOrCreateFromRapidApi($mergedData);

                // Update/Create Player Stats
                if (isset($boxScoreData['playerStats'])) {
                    $this->playerStatRepository->updateOrCreateFromApi($boxScoreData['playerStats']);
                }

                // Update/Create Team Stats with points_allowed and result now included
                if (isset($boxScoreData['teamStats'])) {
                    $this->teamStatRepository->updateOrCreateFromApi($game['gameID'], $boxScoreData['teamStats']);
                }
            }
        } catch (Exception $e) {
            Log::error('Error updating schedule for week', [
                'season' => $season,
                'week' => $weekNumber,
                'seasonType' => $seasonType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function fetchTeamSchedule(string $season, int $weekNumber, string $seasonType): array
    {
        $formattedSeasonType = self::SEASON_TYPES[$seasonType] ?? null;

        if (!$formattedSeasonType) {
            throw new Exception("Invalid season type: {$seasonType}");
        }

        try {
            $response = $this->client->request('GET', "https://{$this->apiHost}/getNFLGamesForWeek", [
                'query' => [
                    'week' => $weekNumber,
                    'seasonType' => $formattedSeasonType,
                    'season' => $season
                ],
                'headers' => $this->getHeaders(),
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON decode error: ' . json_last_error_msg());
            }

            Log::debug('API Response:', ['data' => $data]); // Add logging to see the response
            return $data;

        } catch (Exception $e) {
            Log::error('API request failed', [
                'week' => $weekNumber,
                'season' => $season,
                'seasonType' => $seasonType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function getHeaders(): array
    {
        return [
            'x-rapidapi-host' => $this->apiHost,
            'x-rapidapi-key' => $this->apiKey,
        ];
    }

    private function fetchBoxScore(string $gameId): array
    {
        try {
            Log::info('Fetching play-by-play data', ['game_id' => $gameId]);

            $response = $this->client->request('GET', "https://{$this->apiHost}/getNFLBoxScore", [
                'query' => [
                    'gameID' => $gameId,
                    'playByPlay' => 'true',
                    'fantasyPoints' => 'false',
                ],
                'headers' => $this->getHeaders(),
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['body']['allPlayByPlay'])) {
                $this->playByPlayRepository->updateOrCreateFromApi($data['body'], $gameId);
                Log::info('Play-by-play data saved', [
                    'game_id' => $gameId,
                    'plays_count' => count($data['body']['allPlayByPlay'])
                ]);
            }

            return $data['body'] ?? $data;
        } catch (Exception $e) {
            Log::error('Error fetching box score data', [
                'gameId' => $gameId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
