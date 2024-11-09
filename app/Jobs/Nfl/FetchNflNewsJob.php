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
use Illuminate\Support\Facades\Notification;
use Log;

class FetchNflNewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected const CACHE_PREFIX = 'nfl_news_';
    protected string $runId;

    public function __construct(string $runId)
    {
        $this->runId = $runId;
    }

    /**
     * Get info about the last successful fetch
     */
    public static function getLastSuccess(): ?array
    {
        return Cache::get(self::CACHE_PREFIX . 'last_success');
    }

    /**
     * Get the last error if any
     */
    public static function getLastError(): ?array
    {
        return Cache::get(self::CACHE_PREFIX . 'last_error');
    }

    /**
     * Get the number of API calls made today
     */
    public static function getApiCallsToday(): int
    {
        return (int)Cache::get(self::CACHE_PREFIX . 'api_calls_' . now()->format('Y-m-d'));
    }

    public function handle()
    {
        // Store current fetch time to track freshness
        Cache::put(self::CACHE_PREFIX . 'last_attempt', now(), now()->addDay());

        $apiKey = config('services.rapidapi.key');
        $apiHost = config('services.rapidapi.host', 'tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com');

        if (!$apiKey) {
            Log::error('RAPIDAPI_KEY is not set in the .env file.');
            return;
        }

        try {
            // Track API calls
            $this->incrementApiCalls();

            $response = Http::withHeaders([
                'x-rapidapi-host' => $apiHost,
                'x-rapidapi-key' => $apiKey,
            ])->get("https://{$apiHost}/getNFLNews", [
                'fantasyNews' => 'true',
                'maxItems' => 20,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['body']) && is_array($data['body'])) {
                    $this->processNewsItems($data['body']);
                } else {
                    Log::error('Invalid data format received.');
                    $this->cacheError('Invalid data format received.');
                }
            } else {
                $errorMessage = 'Failed to fetch NFL news. Status Code: ' . $response->status();
                Log::error($errorMessage);
                Log::error('Response Body: ' . $response->body());
                $this->cacheError($errorMessage);
            }
        } catch (Exception $e) {
            $this->cacheError($e->getMessage());
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($e->getMessage(), 'failure'));
        }
    }

    protected function incrementApiCalls(): void
    {
        $key = self::CACHE_PREFIX . 'api_calls_' . now()->format('Y-m-d');
        Cache::increment($key, 1);
        Cache::put($key, Cache::get($key), now()->endOfDay());
    }

    protected function processNewsItems(array $newsItems): void
    {
        $newArticles = 0;
        $newArticleTitles = [];

        foreach ($newsItems as $newsItem) {
            // Check if the news item already exists based on both link and title
            $existingNews = NflNews::where('link', $newsItem['link'])
                ->orWhere('title', $newsItem['title'])
                ->first();

            if (!$existingNews) {
                NflNews::create([
                    'title' => $newsItem['title'] ?? 'Unknown',
                    'link' => $newsItem['link'] ?? null,
                ]);
                $newArticles++;
                $newArticleTitles[] = $newsItem['title'] ?? 'Unknown';
            }
        }

        // Cache the results
        Cache::put(self::CACHE_PREFIX . 'last_success', [
            'time' => now(),
            'new_articles' => $newArticles
        ], now()->addDay());

        // Send notification if there are new articles
        if ($newArticles > 0) {
            $message = "$newArticles new NFL news articles fetched and stored successfully.";

            // Add up to 3 article titles
            if (!empty($newArticleTitles)) {
                $message .= "\nNew articles:";
                foreach (array_slice($newArticleTitles, 0, 3) as $title) {
                    $message .= "\n- " . $title;
                }
                if (count($newArticleTitles) > 3) {
                    $message .= "\n... and " . (count($newArticleTitles) - 3) . ' more';
                }
            }

            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($message, 'success'));
        }
    }

    protected function cacheError(string $message): void
    {
        Cache::put(self::CACHE_PREFIX . 'last_error', [
            'time' => now(),
            'message' => $message
        ], now()->addDay());
    }
}