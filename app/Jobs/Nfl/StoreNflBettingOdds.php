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

// Replace just the relevant methods in your existing file:

    public function handle()
    {
        try {
            $response = $this->fetchOddsData();

            if (!$response->successful()) {
                throw new Exception("API request failed with status: {$response->status()}");
            }

            $data = $response->json();
            $eventIds = array_column($data['body'] ?? [], 'gameID');

            // Preload schedules for the event_ids
            $this->schedules = NflTeamSchedule::whereIn('game_id', $eventIds)->get()->keyBy('event_id');

            $changes = $this->processResponse($data);

            // Always send notification with the current state
            $this->sendNotification($changes);

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
            Log::warning('No odds available or invalid response format for the specified date.', [
                'data' => $data,
            ]);
            return [];
        }

        $changes = [];
        foreach ($data['body'] as $index => $game) {
            Log::info("Processing game {$index}", ['game_id' => $game['gameID'] ?? 'unknown']);
            $gameData = $this->processGame($game);
            if ($gameData) {
                $changes[] = $gameData;
                Log::info('Added game changes', ['game_data' => $gameData]);
            }
        }

        Log::info('Processed all games', ['total_changes' => count($changes)]);
        return $changes;
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
            'total_over' => $this->parseFloat($odds['totalOver'] ?? null) ? $odds['totalUnder'] : null,
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
        if ($value !== null && $value !== '') {
            // Normalize the minus sign
            $value = str_replace('−', '-', $value);
            // Remove any non-numeric characters except decimal point and minus sign
            $numericValue = preg_replace('/[^0-9.\-]/', '', $value);
            return is_numeric($numericValue) ? (float)$numericValue : null;
        }
        return null;
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
                'game_date' => $gameDate?->format('Y-m-d'),
                'away_team' => $game['awayTeam'],
                'home_team' => $game['homeTeam'],
                'away_team_id' => $game['teamIDAway'],
                'home_team_id' => $game['teamIDHome'],
                'spread_home' => $this->parseFloat($odds['homeTeamSpread']),
                'spread_away' => $this->parseFloat($odds['awayTeamSpread']),
                'total_over' => $this->parseFloat($odds['totalOver'] ?? null) ? $odds['totalUnder'] : null,
                'total_under' => $this->parseFloat($odds['totalUnder']),
                'moneyline_home' => $this->parseFloat($odds['homeTeamMLOdds']),
                'moneyline_away' => $this->parseFloat($odds['awayTeamMLOdds']),
                'implied_total_home' => $odds['impliedTotals']['homeTotal'] ?? null,
                'implied_total_away' => $odds['impliedTotals']['awayTotal'] ?? null,
            ]
        );
    }
    
    private function sendNotification(array $changes): void
    {
        try {
            $date = Carbon::createFromFormat('Ymd', $this->gameDate);
            $message = "🏈 **NFL Betting Odds Update**\n";
            $message .= '📅 Date: ' . $date->format('Y-m-d') . "\n\n";

            // Debug log the changes array
            Log::info('Changes array:', ['changes' => $changes]);

            if (empty($changes)) {
                $message .= 'ℹ️ No odds updates available for this date.';
            } else {
                $initialOdds = [];
                $lineChanges = [];

                // Debug log each change
                foreach ($changes as $index => $change) {
                    Log::info("Processing change {$index}", ['change' => $change]);

                    if (isset($change['changes']['initial_odds'])) {
                        $initialOdds[] = $change;
                        Log::info('Added to initial odds', ['matchup' => $change['matchup']]);
                    } elseif (!empty($change['changes'])) {
                        $lineChanges[] = $change;
                        Log::info('Added to line changes', ['matchup' => $change['matchup']]);
                    }
                }

                // Format initial odds
                if (!empty($initialOdds)) {
                    $message .= "🆕 **Initial Odds:**\n";
                    foreach ($initialOdds as $change) {
                        $odds = $change['changes']['initial_odds'];
                        $message .= "\n🎯 {$change['matchup']}\n"
                            . '  📊 Spread: ' . number_format($odds['spread_home'], 1) . "\n"
                            . '  📈 Total: ' . number_format($odds['total_over'], 1) . "\n"
                            . '  🏠 Home ML: ' . $odds['moneyline_home'] . "\n"
                            . '  🏃 Away ML: ' . $odds['moneyline_away'] . "\n";
                    }
                }

                // Format line changes
                if (!empty($lineChanges)) {
                    $message .= "\n📊 **Line Changes:**\n";
                    foreach ($lineChanges as $change) {
                        $message .= "\n🎯 {$change['matchup']}\n";
                        foreach ($change['changes'] as $type => $values) {
                            $label = match ($type) {
                                'spread' => '📊 Spread',
                                'total' => '📈 Total',
                                'home_ml' => '🏠 Home ML',
                                'away_ml' => '🏃 Away ML',
                                default => ucfirst($type)
                            };
                            $message .= "  {$label}: {$values['old']} → {$values['new']} ({$values['change']})\n";
                        }
                    }
                }

                if (empty($initialOdds) && empty($lineChanges)) {
                    $message .= 'ℹ️ No significant changes detected.';
                }
            }

            $message .= "\n⚡ _Powered by Picksports Alerts • " . now()->format('F j, Y g:i A') . '_';

            // Log the final message
            Log::info('Final notification message:', ['message' => $message]);

            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($message, 'success'));

        } catch (Exception $e) {
            Log::error('Failed to send NFL odds notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }    // Add this method to your StoreNflBettingOdds job

}