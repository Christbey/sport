<?php

namespace App\Jobs\Nfl;

use AllowDynamicProperties;
use App\Models\Nfl\NflBettingOdds;
use App\Models\Nfl\NflTeamSchedule;
use App\Notifications\DiscordCommandCompletionNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

#[AllowDynamicProperties] class StoreNflBettingOdds implements ShouldQueue
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

            $data = $response->json();
            $eventIds = array_column($data['body'], 'gameID');

            // Preload schedules for the event_ids
            $this->schedules = NflTeamSchedule::whereIn('game_id', $eventIds)->get()->keyBy('event_id');

            $changes = $this->processResponse($data);

            Log::info('NFL betting odds updated successfully for date: ' . $this->gameDate, [
                'changes_detected' => count($changes)
            ]);

            // Send notification if there are changes
            if ($changes) {
                $this->sendNotification($changes);
            }

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
            $gameData = $this->processGame($game);
            if ($gameData) {
                $changes[] = $gameData;
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

        // Store the odds regardless of whether there are changes
        $this->updateOdds($game, $draftkingsData);

        return [
            'matchup' => "{$game['awayTeam']} @ {$game['homeTeam']}",
            'changes' => $changes
        ];
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
        $changes = [];

        if (!$existingOdds) {
            // If there are no existing odds, consider all new odds as changes
            $changes['initial_odds'] = $newOdds;
            return $changes;
        }
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
        $gameDateString = $game['gameDate'] ?? null;

        if ($gameDateString) {
            try {
                // Parse 'gameDate' from 'YYYYMMDD' to Carbon instance
                $gameDate = Carbon::createFromFormat('Ymd', $gameDateString);
            } catch (Exception $e) {
                Log::error('Failed to parse gameDate', [
                    'gameDate' => $gameDateString,
                    'error' => $e->getMessage()
                ]);
                $gameDate = null;
            }
        } else {
            $gameDate = null;
        }

        NflBettingOdds::updateOrCreate(
            [
                'event_id' => $game['gameID'],
                'source' => self::SPORTSBOOK,
            ],
            [
                'game_date' => $gameDate ? $gameDate->format('Y-m-d') : null,
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

    // Add this method to your StoreNflBettingOdds job

    private function sendNotification(array $changes): void
    {
        $date = Carbon::createFromFormat('Ymd', $this->gameDate);

        $message = "**NFL Betting Odds Update**\n";
        $message .= 'Date: ' . $date->format('Y-m-d') . "\n\n";

        if (empty($changes)) {
            $message .= 'No significant line changes detected.';
        } else {
            $hasInitialOdds = false;
            $hasChanges = false;

            foreach ($changes as $change) {
                if (isset($change['changes']['initial_odds'])) {
                    $hasInitialOdds = true;
                } elseif (!empty($change['changes'])) {
                    $hasChanges = true;
                }
            }

            if ($hasInitialOdds) {
                $message .= "**Initial Odds Stored:**\n";
                foreach ($changes as $change) {
                    if (isset($change['changes']['initial_odds'])) {
                        $matchup = $change['matchup'];
                        $odds = $change['changes']['initial_odds'];

                        $message .= "\n• {$matchup}\n";
                        $message .= "  Spread Home: {$odds['spread_home']}\n";
                        $message .= "  Total Over: {$odds['total_over']}\n";
                        $message .= "  Moneyline Home: {$odds['moneyline_home']}\n";
                        $message .= "  Moneyline Away: {$odds['moneyline_away']}\n";
                    }
                }
            }

            if ($hasChanges) {
                $message .= "\n**Notable Line Changes:**\n";
                foreach ($changes as $change) {
                    if (!isset($change['changes']['initial_odds']) && !empty($change['changes'])) {
                        $message .= "\n• {$change['matchup']}\n";

                        foreach ($change['changes'] as $type => $values) {
                            switch ($type) {
                                case 'spread':
                                    $message .= "  Spread: {$values['old']} → {$values['new']} ({$values['change']})\n";
                                    break;
                                case 'total':
                                    $message .= "  Total: {$values['old']} → {$values['new']} ({$values['change']})\n";
                                    break;
                                case 'home_ml':
                                    $message .= "  Home ML: {$values['old']} → {$values['new']} ({$values['change']})\n";
                                    break;
                                case 'away_ml':
                                    $message .= "  Away ML: {$values['old']} → {$values['new']} ({$values['change']})\n";
                                    break;
                            }
                        }
                    }
                }
            }
        }

        $message .= "\n_Powered by Picksports Alerts • " . now()->format('F j, Y g:i A') . '_';

        Notification::route('discord', config('services.discord.channel_id'))
            ->notify(new DiscordCommandCompletionNotification($message, 'success'));
    }
}