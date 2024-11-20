<?php

namespace App\Jobs\Nfl;

use App\Models\NflNews;
use App\Notifications\DiscordCommandCompletionNotification;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class FetchNflNewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected const CACHE_PREFIX = 'nfl_news_';
    protected const API_CALLS_PER_DAY_LIMIT = 100;

    public function __construct()
    {
    }

    public function handle()
    {
        $apiKey = config('services.rapidapi.key');
        $apiHost = config('services.rapidapi.host', 'tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com');

        $this->validateApiKey($apiKey);

        try {
            if ($this->isApiCallLimitReached()) {
                Log::warning('API call limit reached. Skipping NFL news fetch.');
                return;
            }

            $newsData = $this->fetchNflNews($apiHost, $apiKey);
            $newArticleCount = $this->processNewsData($newsData);

            $this->cacheSuccess($newArticleCount);
            $this->sendDiscordNotification($newArticleCount, $newsData);
        } catch (Exception $e) {
            $this->cacheError($e->getMessage());
            $this->sendDiscordNotification(0, [], $e->getMessage());
            Log::error('Failed to fetch NFL news: ' . $e->getMessage());
        } finally {
            $this->incrementApiCalls();
        }
    }

    private function validateApiKey(string $apiKey): void
    {
        if (!$apiKey) {
            throw new Exception('RAPIDAPI_KEY is not set in the .env file.');
        }
    }

    private function isApiCallLimitReached(): bool
    {
        return $this->getApiCallsToday() >= self::API_CALLS_PER_DAY_LIMIT;
    }

    private function getApiCallsToday(): int
    {
        return (int)Cache::get(self::CACHE_PREFIX . 'api_calls_' . now()->format('Y-m-d'), 0);
    }

    private function fetchNflNews(string $apiHost, string $apiKey): array
    {
        $response = Http::withHeaders([
            'x-rapidapi-host' => $apiHost,
            'x-rapidapi-key' => $apiKey,
        ])->get("https://{$apiHost}/getNFLNews", [
            'fantasyNews' => 'true',
            'maxItems' => 20,
        ]);

        if ($response->successful()) {
            return $response->json('body') ?? [];
        } else {
            throw new Exception('Failed to fetch NFL news. Status Code: ' . $response->status());
        }
    }

    private function processNewsData(array $newsData): int
    {
        $newArticleCount = 0;

        foreach ($newsData as $newsItem) {
            if (!$this->newsItemExists($newsItem)) {
                NflNews::create([
                    'title' => $newsItem['title'] ?? 'Unknown',
                    'link' => $newsItem['link'] ?? null,
                ]);
                $newArticleCount++;
            }
        }

        return $newArticleCount;
    }

    private function newsItemExists(array $newsItem): bool
    {
        return NflNews::where('link', $newsItem['link'])
            ->orWhere('title', $newsItem['title'])
            ->exists();
    }

    private function cacheSuccess(int $newArticleCount): void
    {
        Cache::put(self::CACHE_PREFIX . 'last_success', [
            'time' => now(),
            'new_articles' => $newArticleCount
        ], now()->addDay());
    }

    private function sendDiscordNotification(int $newArticleCount, array $newsData, ?string $errorMessage = null): void
    {
        if ($errorMessage) {
            $message = "Error fetching NFL news: $errorMessage";
        } else {
            $message = "$newArticleCount new NFL news articles fetched and stored successfully.";
            if (!empty($newsData)) {
                $message .= "\nNew articles:";
                foreach (array_slice(array_column($newsData, 'title'), 0, 3) as $title) {
                    $message .= "\n- $title";
                }
                if (count($newsData) > 3) {
                    $message .= "\n... and " . (count($newsData) - 3) . ' more';
                }
            }
        }

        Notification::route('discord', config('services.discord.channel_id'))
            ->notify(new DiscordCommandCompletionNotification($message, $errorMessage ? 'failure' : 'success'));
    }

    private function cacheError(string $message): void
    {
        Cache::put(self::CACHE_PREFIX . 'last_error', [
            'time' => now(),
            'message' => $message
        ], now()->addDay());
    }

    private function incrementApiCalls(): void
    {
        $key = self::CACHE_PREFIX . 'api_calls_' . now()->format('Y-m-d');
        Cache::increment($key, 1);
        Cache::put($key, Cache::get($key), now()->endOfDay());
    }
}