<?php

namespace App\Jobs;

use App\Notifications\DiscordCommandCompletionNotification;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Log;

class FetchNflNewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $apiKey = config('services.rapidapi.key');
        $apiHost = config('services.rapidapi.host', 'tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com');

        if (!$apiKey) {
            Log::error('RAPIDAPI_KEY is not set in the .env file.');
            return;
        }

        try {
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
                    // Build formatted message as before
                    $formattedMessage = "[FetchNflNewsJob] NFL News fetched:\n\n";
                    foreach ($data['body'] as $newsItem) {
                        $formattedMessage .= '- **' . ($newsItem['title'] ?? 'Unknown') . "**\n";
                        $formattedMessage .= '  ' . ($newsItem['link'] ?? 'No link provided') . "\n\n";
                    }

                    // Log the full message for debugging
                    Log::info("Full message before splitting:\n" . $formattedMessage);

                    // Split if necessary and send
                    $chunkedMessages = str_split($formattedMessage, 2000);
                    foreach ($chunkedMessages as $chunk) {
                        Notification::route('discord', config('services.discord.channel_id'))
                            ->notify(new DiscordCommandCompletionNotification($chunk, 'success'));
                    }
                } else {
                    Log::error('Invalid data format received.');
                }
            } else {
                Log::error('Failed to fetch NFL news. Status Code: ' . $response->status());
                Log::error('Response Body: ' . $response->body());
            }
        } catch (Exception $e) {
            // Send failure notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($e->getMessage(), 'error'));
        }
    }
}
