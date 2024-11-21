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
    private const API_CALLS_PREFIX = 'cfb_lines_api_calls_';
    private const API_URL = 'https://apinext.collegefootballdata.com/lines';
    private const DISCORD_MESSAGE_LIMIT = 2000;
    private const MAX_CHANGES_TO_DISPLAY = 5;

    private int $year;
    private int $week;
    private bool $force;
    private string $apiKey;
    private array $stats;

    public function __construct(array $params)
    {
        $this->year = $params['year'];
        $this->week = $params['week'] ?? CollegeFootballCommandHelpers::getCurrentWeek();
        $this->force = $params['force'] ?? false;
        $this->apiKey = config('services.college_football_data.key');
        $this->stats = [
            'updated_teams' => 0,
            'missing_teams' => [],
            'changed_lines' => []
        ];
    }

    public static function getApiCallsToday(): int
    {
        return (int)Cache::get(self::API_CALLS_PREFIX . now()->format('Y-m-d'), 0);
    }

    public static function getLastSuccess(): ?array
    {
        return Cache::get(self::CACHE_PREFIX . 'last_success');
    }

    public static function getLastError(): ?array
    {
        return Cache::get(self::CACHE_PREFIX . 'last_error');
    }

    public function handle(): void
    {
        try {
            $this->incrementApiCalls();
            $gameData = $this->fetchGameData();
            $this->processGameLines($gameData);
            $this->cacheSuccess();
            $this->sendNotification();
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    private function incrementApiCalls(): void
    {
        $key = self::API_CALLS_PREFIX . now()->format('Y-m-d');
        Cache::increment($key, 1);
        Cache::put($key, Cache::get($key), now()->endOfDay());
    }

    private function fetchGameData(): array
    {
        $client = new Client();
        $response = $client->request('GET', self::API_URL, [
            'query' => [
                'year' => $this->year,
                'week' => $this->week,
                'provider' => 'DraftKings',
            ],
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Accept' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    private function processGameLines(array $data): void
    {
        foreach ($data as $gameData) {
            $game = CollegeFootballGame::find($gameData['id']);

            if (!$game || empty($gameData['lines'])) {
                $this->stats['missing_teams'][] = $gameData['id'];
                Log::info("No game found for ID: {$gameData['id']}");
                continue;
            }

            $this->processGameLine($game, $gameData['lines'][0]);
        }

        $this->stats['changed_lines'] = array_slice($this->stats['changed_lines'], 0, self::MAX_CHANGES_TO_DISPLAY);
    }

    private function processGameLine(CollegeFootballGame $game, array $line): void
    {
        $changes = $this->detectChanges($game, $line);

        if (!empty($changes)) {
            $changes['teams'] = "{$game->home_team} vs {$game->away_team}";
            $this->stats['changed_lines'][] = $changes;
        }

        $this->updateGameLine($game, $line);
        $this->stats['updated_teams']++;
    }

    private function detectChanges(CollegeFootballGame $game, array $line): array
    {
        $changes = [];

        if ($this->hasValueChanged($line['overUnder'], $game->over_under)) {
            $changes['over_under'] = [
                'old' => $game->over_under,
                'new' => $line['overUnder']
            ];
        }

        if ($this->hasValueChanged($line['homeMoneyline'], $game->home_moneyline)) {
            $changes['home_moneyline'] = [
                'old' => $game->home_moneyline,
                'new' => $line['homeMoneyline']
            ];
        }

        if ($this->hasValueChanged($line['awayMoneyline'], $game->away_moneyline)) {
            $changes['away_moneyline'] = [
                'old' => $game->away_moneyline,
                'new' => $line['awayMoneyline']
            ];
        }

        return $changes;
    }

    private function hasValueChanged($new, $old): bool
    {
        if ($new === null || $old === null) {
            return false;
        }

        return is_numeric($new) && is_numeric($old) && floatval($new) !== floatval($old);
    }

    private function updateGameLine(CollegeFootballGame $game, array $line): void
    {
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
    }

    private function cacheSuccess(): void
    {
        Cache::put(self::CACHE_PREFIX . 'last_success', [
            'time' => now(),
            'year' => $this->year,
            'week' => $this->week,
            'stats' => $this->stats
        ], now()->addDay());
    }

    private function sendNotification(): void
    {
        $message = $this->buildNotificationMessage();

        if (strlen($message) > self::DISCORD_MESSAGE_LIMIT) {
            $message = substr($message, 0, self::DISCORD_MESSAGE_LIMIT - 100) . "\n... _(message truncated)_";
        }

        try {
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($message, 'success'));
        } catch (Exception $e) {
            Log::error('Failed to send Discord notification', [
                'error' => $e->getMessage(),
                'message_length' => strlen($message)
            ]);
        }
    }

    private function buildNotificationMessage(): string
    {
        $message = "**College Football Lines Update**\nWeek: {$this->week}\n\n";

        if (empty($this->stats['changed_lines'])) {
            return $message . "No changes detected in latest update.\n\n" .
                $this->getMessageFooter();
        }

        foreach ($this->stats['changed_lines'] as $change) {
            $message .= $this->formatGameChanges($change);
        }

        return $message . $this->getMessageFooter();
    }

    private function getMessageFooter(): string
    {
        return '_Powered by Picksports Alerts • ' . now()->format('F j, Y g:i A') . '_';
    }

    private function formatGameChanges(array $change): string
    {
        $message = "\n• {$change['teams']}\n";

        if (isset($change['over_under'])) {
            $message .= sprintf("  O/U: %.1f → %.1f\n",
                floatval($change['over_under']['old']),
                floatval($change['over_under']['new'])
            );
        }

        if (isset($change['home_moneyline'])) {
            $message .= sprintf("  Home ML: %+d → %+d\n",
                intval($change['home_moneyline']['old']),
                intval($change['home_moneyline']['new'])
            );
        }

        if (isset($change['away_moneyline'])) {
            $message .= sprintf("  Away ML: %+d → %+d\n",
                intval($change['away_moneyline']['old']),
                intval($change['away_moneyline']['new'])
            );
        }

        return $message;
    }

    private function handleError(Exception $e): void
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