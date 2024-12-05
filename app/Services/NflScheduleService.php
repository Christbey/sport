<?php

namespace App\Services;

use App\Repositories\Nfl\{NflBoxScoreRepository,
    NflPlayByPlayRepository,
    NflPlayerStatRepository,
    NflTeamStatRepository};
use App\Repositories\Nfl\Interfaces\NflTeamScheduleRepositoryInterface;
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
        private NflTeamScheduleRepositoryInterface $scheduleRepository,
        private NflBoxScoreRepository              $boxScoreRepository,
        private NflPlayerStatRepository            $playerStatRepository,
        private NflTeamStatRepository              $teamStatRepository,
        private NflPlayByPlayRepository            $playByPlayRepository
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

                $this->scheduleRepository->updateOrCreateFromRapidApi($mergedData, $season);
                $this->boxScoreRepository->updateOrCreateFromRapidApi($mergedData);

                if (isset($boxScoreData['playerStats'])) {
                    $this->playerStatRepository->updateOrCreateFromApi($boxScoreData['playerStats']);
                }
                // Save team stats
                if (isset($boxScoreData['teamStats'])) {
                    $this->teamStatRepository->updateOrCreateFromApi($game['gameID'], $boxScoreData['teamStats']);
                }
            }
        } catch (Exception $e) {
            Log::error("Error processing Week {$weekNumber}, Season {$season}: {$e->getMessage()}");
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
