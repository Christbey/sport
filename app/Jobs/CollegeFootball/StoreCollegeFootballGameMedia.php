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

    protected $year;
    protected $week;
    protected $seasonType;
    protected $team;
    protected $conference;
    protected $mediaType;
    protected $classification;

    protected $apiUrl = 'https://apinext.collegefootballdata.com/games/media';
    protected $apiKey;

    /**
     * Create a new job instance.
     *
     * @param array $params
     */
    public function __construct(array $params)
    {
        $this->year = $params['year'] ?? null;
        $this->week = $params['week'] ?? null;
        $this->seasonType = $params['seasonType'] ?? null;
        $this->team = $params['team'] ?? null;
        $this->conference = $params['conference'] ?? null;
        $this->mediaType = $params['mediaType'] ?? null;
        $this->classification = $params['classification'] ?? null;
        $this->apiKey = config('services.college_football_data.key');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $client = new Client();
            $response = $client->request('GET', $this->apiUrl, [
                'query' => [
                    'year' => $this->year,
                    'week' => $this->week,
                    'seasonType' => $this->seasonType,
                    'team' => $this->team,
                    'conference' => $this->conference,
                    'mediaType' => $this->mediaType,
                    'classification' => $this->classification,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $mediaData = json_decode($response->getBody(), true);

            foreach ($mediaData as $media) {
                // Assuming 'id' is the game ID in the media data
                $game = CollegeFootballGame::where('id', $media['id'])->first();

                if ($game) {
                    $game->update([
                        'media_type' => $media['mediaType'] ?? null,
                        'outlet' => $media['outlet'] ?? null,
                        'provider' => $media['provider'] ?? null,
                        // Add any other relevant fields you want to store
                    ]);
                } else {
                    Log::info('No game found for ID: ' . $media['id']);
                }
            }

            Log::info('Game media data fetched and stored successfully.');
            // Send success notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification('', 'success'));

        } catch (Exception $e) {
            // Send failure notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($e->getMessage(), 'error'));

        }

    }
}
