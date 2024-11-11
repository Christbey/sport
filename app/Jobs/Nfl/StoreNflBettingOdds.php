<?php

namespace App\Jobs\Nfl;

use App\Models\Nfl\NflBettingOdds;
use Cache;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StoreNflBettingOdds implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const SPORTSBOOK = 'draftkings';
    private const API_HOST = 'tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com';
    private const API_ENDPOINT = 'https://tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com/getNFLBettingOdds';
    public const CACHE_KEY = 'nfl_odds_changes_';

    protected $gameDate;
    protected array $changedOdds = [];

    public function __construct($gameDate)
    {
        $this->gameDate = $gameDate;
    }

    public function handle()
    {
        try {
            $response = $this->fetchOddsData();

            if (!$response->successful()) {
                throw new Exception("API request failed with status: {$response->status()}");
            }

            $changes = $this->processResponse($response->json());

            if (!empty($changes)) {
                Cache::put(self::CACHE_KEY . $this->gameDate, $changes, now()->addMinutes(2));
            }

            Log::info('NFL betting odds updated successfully for date: ' . $this->gameDate, [
                'changes_detected' => count($changes)
            ]);

        } catch (Exception $e) {
            Log::error('Failed to process NFL betting odds', [
                'date' => $this->gameDate,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function fetchOddsData()
    {
        $response = Http::withHeaders([
            'x-rapidapi-host' => self::API_HOST,
            'x-rapidapi-key' => config('services.rapidapi.key'),
        ])->get(self::API_ENDPOINT, [
            'gameDate' => $this->gameDate,
            'itemFormat' => 'list',
            'impliedTotals' => 'true',
        ]);

        Log::info('NFL API Response', [
            'date' => $this->gameDate,
            'status' => $response->status(),
            'data' => $response->json()
        ]);

        return $response;
    }

    private function processResponse(array $data): array
    {
        if (empty($data['body']) || !is_array($data['body'])) {
            throw new Exception('Invalid response format: missing or invalid body');
        }

        $changes = [];
        foreach ($data['body'] as $game) {
            $gameChanges = $this->processGame($game);
            if ($gameChanges) {
                $changes[] = $gameChanges;
            }
        }

        return array_slice($changes, 0, 5); // Limit to top 5 changes
    }

    private function processGame(array $game): ?array
    {
        if (empty($game['sportsBooks']) || !is_array($game['sportsBooks'])) {
            return null;
        }

        $draftkingsData = $this->getDraftkingsData($game['sportsBooks']);
        if (!$draftkingsData) {
            return null;
        }

        $odds = $draftkingsData['odds'] ?? [];
        $existingOdds = NflBettingOdds::where('event_id', $game['gameID'])
            ->where('source', self::SPORTSBOOK)
            ->first();

        $newOdds = [
            'spread_home' => $this->parseFloat($odds['homeTeamSpread']),
            'total_over' => $this->parseFloat($odds['totalOver']),
            'moneyline_home' => $this->parseFloat($odds['homeTeamMLOdds']),
            'moneyline_away' => $this->parseFloat($odds['awayTeamMLOdds']),
        ];

        $changes = $this->detectChanges($existingOdds, $newOdds);

        if (!empty($changes)) {
            // Store the updated odds
            $this->updateOdds($game, $draftkingsData);

            return [
                'matchup' => "{$game['awayTeam']} @ {$game['homeTeam']}",
                'changes' => $changes
            ];
        }

        return null;
    }

    private function getDraftkingsData(array $sportsBooks)
    {
        foreach ($sportsBooks as $sportsBook) {
            if (strtolower($sportsBook['sportsBook'] ?? '') === self::SPORTSBOOK) {
                return $sportsBook;
            }
        }
        return null;
    }

    private function parseFloat($value): ?float
    {
        return isset($value) ? floatval($value) : null;
    }

    private function detectChanges($existingOdds, array $newOdds): array
    {
        if (!$existingOdds) {
            return [];
        }

        $changes = [];
        $thresholds = [
            'spread' => 0.5,
            'total' => 0.5,
            'moneyline' => 10
        ];

        // Format and check each type of change
        if (abs(($newOdds['spread_home'] - $existingOdds->spread_home)) >= $thresholds['spread']) {
            $changes['spread'] = [
                'old' => number_format($existingOdds->spread_home, 1),
                'new' => number_format($newOdds['spread_home'], 1),
                'change' => number_format($newOdds['spread_home'] - $existingOdds->spread_home, 1)
            ];
        }

        if (abs(($newOdds['total_over'] - $existingOdds->total_over)) >= $thresholds['total']) {
            $changes['total'] = [
                'old' => number_format($existingOdds->total_over, 1),
                'new' => number_format($newOdds['total_over'], 1),
                'change' => number_format($newOdds['total_over'] - $existingOdds->total_over, 1)
            ];
        }

        foreach (['home_ml' => 'moneyline_home', 'away_ml' => 'moneyline_away'] as $key => $field) {
            if (abs(($newOdds[$field] - $existingOdds->$field)) >= $thresholds['moneyline']) {
                $changes[$key] = [
                    'old' => $existingOdds->$field,
                    'new' => $newOdds[$field],
                    'change' => $newOdds[$field] - $existingOdds->$field
                ];
            }
        }

        return $changes;
    }

    private function updateOdds(array $game, array $sportsBook): void
    {
        $odds = $sportsBook['odds'] ?? [];

        NflBettingOdds::updateOrCreate(
            [
                'event_id' => $game['gameID'],
                'source' => self::SPORTSBOOK,
            ],
            [
                'game_date' => $game['gameDate'],
                'away_team' => $game['awayTeam'],
                'home_team' => $game['homeTeam'],
                'away_team_id' => $game['teamIDAway'],
                'home_team_id' => $game['teamIDHome'],
                'spread_home' => $this->parseFloat($odds['homeTeamSpread']),
                'spread_away' => $this->parseFloat($odds['awayTeamSpread']),
                'total_over' => $this->parseFloat($odds['totalOver']),
                'total_under' => $this->parseFloat($odds['totalUnder']),
                'moneyline_home' => $this->parseFloat($odds['homeTeamMLOdds']),
                'moneyline_away' => $this->parseFloat($odds['awayTeamMLOdds']),
                'implied_total_home' => $odds['impliedTotals']['homeTotal'] ?? null,
                'implied_total_away' => $odds['impliedTotals']['awayTotal'] ?? null,
            ]
        );
    }
}