<?php

namespace App\Jobs\CollegeFootball;

use App\Models\CollegeFootball\CollegeFootballGame;
use App\Notifications\DiscordCommandCompletionNotification;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class StoreCollegeFootballGameMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $params;
    protected $apiUrl = 'https://apinext.collegefootballdata.com/games/media';
    protected $apiKey;

    public function __construct(array $params)
    {
        $this->params = $params;
        $this->apiKey = config('services.college_football_data.key');
    }

    public function handle()
    {
        try {
            $client = new Client();
            $response = $client->get($this->apiUrl, [
                'query' => $this->params,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $mediaData = json_decode($response->getBody(), true);

            foreach ($mediaData as $media) {
                $game = CollegeFootballGame::find($media['id']);

                if ($game) {
                    $game->update([
                        'media_type' => $media['mediaType'] ?? null,
                        'outlet' => $media['outlet'] ?? null,
                        'provider' => $media['provider'] ?? null,
                    ]);
                } else {
                    Log::info('No game found for ID: ' . $media['id']);
                }
            }

            Log::info('Game media data fetched and stored successfully.');
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification('', 'success'));

        } catch (Exception $e) {
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($e->getMessage(), 'error'));
        }
    }
}