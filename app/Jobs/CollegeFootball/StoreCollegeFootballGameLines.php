<?php

namespace App\Jobs\CollegeFootball;

use App\Helpers\CollegeFootballCommandHelpers;
use App\Models\CollegeFootball\CollegeFootballGame;
use App\Notifications\DiscordCommandCompletionNotification;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class StoreCollegeFootballGameLines implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const CACHE_PREFIX = 'cfb_lines_job_';
    protected const API_CALLS_PREFIX = 'cfb_lines_api_calls_';

    protected $year;
    protected $week;
    protected $force;
    protected $apiUrl = 'https://apinext.collegefootballdata.com/lines';
    protected $apiKey;

    public function __construct(array $params)
    {
        $this->year = $params['year'];
        $this->week = $params['week'] ?? CollegeFootballCommandHelpers::getCurrentWeek();
        $this->force = $params['force'] ?? false;
        $this->apiKey = config('services.college_football_data.key');
    }

    public static function getApiCallsToday(): int
    {
        return (int)Cache::get(self::API_CALLS_PREFIX . now()->format('Y-m-d'));
    }

    public static function getLastSuccess(): ?array
    {
        return Cache::get(self::CACHE_PREFIX . 'last_success');
    }

    public static function getLastError(): ?array
    {
        return Cache::get(self::CACHE_PREFIX . 'last_error');
    }

    public function handle()
    {
        try {
            $this->incrementApiCalls();

            $client = new Client();
            $response = $client->request('GET', $this->apiUrl, [
                'query' => [
                    'year' => $this->year,
                    'week' => $this->week,
                    'provider' => 'DraftKings',
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            $stats = $this->processGameLines($data);

            // Cache success result
            $this->cacheSuccess($stats);

            // Send success notification with stats
            $this->sendSuccessNotification($stats);

        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    protected function incrementApiCalls(): void
    {
        $key = self::API_CALLS_PREFIX . now()->format('Y-m-d');
        Cache::increment($key, 1);
        Cache::put($key, Cache::get($key), now()->endOfDay());
    }

    protected function processGameLines(array $data): array
    {
        $stats = [
            'updated_teams' => 0,
            'missing_teams' => [],
            'changed_lines' => []
        ];

        foreach ($data as $gameData) {
            $game = CollegeFootballGame::where('id', $gameData['id'])->first();

            if ($game && !empty($gameData['lines'])) {
                $line = $gameData['lines'][0];
                $changes = [];
                $hasChanges = false;

                // Strict comparison for numbers
                if (isset($line['overUnder']) && $game->over_under !== null &&
                    floatval($line['overUnder']) !== floatval($game->over_under)) {
                    $changes['over_under'] = [
                        'old' => $game->over_under,
                        'new' => $line['overUnder']
                    ];
                    $hasChanges = true;
                }

                if (isset($line['homeMoneyline']) && $game->home_moneyline !== null &&
                    intval($line['homeMoneyline']) !== intval($game->home_moneyline)) {
                    $changes['home_moneyline'] = [
                        'old' => $game->home_moneyline,
                        'new' => $line['homeMoneyline']
                    ];
                    $hasChanges = true;
                }

                if (isset($line['awayMoneyline']) && $game->away_moneyline !== null &&
                    intval($line['awayMoneyline']) !== intval($game->away_moneyline)) {
                    $changes['away_moneyline'] = [
                        'old' => $game->away_moneyline,
                        'new' => $line['awayMoneyline']
                    ];
                    $hasChanges = true;
                }

                // Only add to changed_lines if there were actual changes
                if ($hasChanges) {
                    $changes['teams'] = "{$game->home_team} vs {$game->away_team}";
                    $stats['changed_lines'][] = $changes;
                }

                // Update the game record
                $game->update([
                    'spread' => $line['spread'] ?? null,
                    'formatted_spread' => $line['formattedSpread'] ?? null,
                    'provider' => $line['provider'] ?? null,
                    'spread_open' => $line['spreadOpen'] ?? null,
                    'over_under' => $line['overUnder'] ?? null,
                    'over_under_open' => $line['overUnderOpen'] ?? null,
                    'home_moneyline' => $line['homeMoneyline'] ?? null,
                    'away_moneyline' => $line['awayMoneyline'] ?? null,
                ]);

                $stats['updated_teams']++;
            } else {
                $stats['missing_teams'][] = $gameData['id'];
                Log::info('No game found for ID: ' . $gameData['id']);
            }
        }

        // Only take the first 5 changes if there are any actual changes
        $stats['changed_lines'] = array_slice($stats['changed_lines'], 0, 5);

        return $stats;
    }

    protected function cacheSuccess(array $stats): void
    {
        Cache::put(self::CACHE_PREFIX . 'last_success', [
            'time' => now(),
            'year' => $this->year,
            'week' => $this->week,
            'stats' => $stats
        ], now()->addDay());
    }

    protected function sendSuccessNotification(array $stats): void
    {
        try {
            // If no changes, send simple message
            if (empty($stats['changed_lines'])) {
                $message = "**College Football Lines Update**\n";
                $message .= "Week: {$this->week}\n\n";
                $message .= "No changes detected in latest update.\n\n";
                $message .= '_Powered by Picksports Alerts • ' . now()->format('F j, Y g:i A') . '_';

                Notification::route('discord', config('services.discord.channel_id'))
                    ->notify(new DiscordCommandCompletionNotification($message, 'success'));
                return;
            }

            // Build message for actual changes
            $message = "**College Football Lines Update**\n";
            $message .= "Week: {$this->week}\n\n";

            // Take only first 5 changes
            $limitedChanges = array_slice($stats['changed_lines'], 0, 5);

            foreach ($limitedChanges as $change) {
                // Add each game's changes
                $gameMessage = "\n• {$change['teams']}\n";

                if (isset($change['over_under'])) {
                    $gameMessage .= sprintf("  O/U: %.1f → %.1f\n",
                        floatval($change['over_under']['old']),
                        floatval($change['over_under']['new'])
                    );
                }

                if (isset($change['home_moneyline'])) {
                    $gameMessage .= sprintf("  Home ML: %+d → %+d\n",
                        intval($change['home_moneyline']['old']),
                        intval($change['home_moneyline']['new'])
                    );
                }

                if (isset($change['away_moneyline'])) {
                    $gameMessage .= sprintf("  Away ML: %+d → %+d\n",
                        intval($change['away_moneyline']['old']),
                        intval($change['away_moneyline']['new'])
                    );
                }

                // Check if adding this game would exceed Discord's limit
                if (strlen($message . $gameMessage) > 1900) {
                    break;
                }

                $message .= $gameMessage;
            }

            $message .= "\n_Powered by Picksports Alerts • " . now()->format('F j, Y g:i A') . '_';

            // Ensure message isn't too long
            if (strlen($message) > 2000) {
                $message = substr($message, 0, 1900) . "\n... _(message truncated)_";
            }

            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($message, 'success'));

        } catch (Exception $e) {
            Log::error('Failed to send Discord notification', [
                'error' => $e->getMessage(),
                'message_length' => isset($message) ? strlen($message) : 0
            ]);
        }
    }

    protected function handleError(Exception $e): void
    {
        Log::error('Game lines job failed', [
            'error' => $e->getMessage(),
            'year' => $this->year,
            'week' => $this->week
        ]);

        Cache::put(self::CACHE_PREFIX . 'last_error', [
            'time' => now(),
            'message' => $e->getMessage()
        ], now()->addDay());

        Notification::route('discord', config('services.discord.channel_id'))
            ->notify(new DiscordCommandCompletionNotification(
                "Failed to fetch game lines for Year: {$this->year}, Week: {$this->week}. Error: {$e->getMessage()}",
                'error'
            ));

        throw $e;
    }

}