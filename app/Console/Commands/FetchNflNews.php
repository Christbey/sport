<?php

namespace App\Console\Commands;

use App\Models\NflNews;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchNflNews extends Command
{
    protected $signature = 'nfl:fetch-news';
    protected $description = 'Fetch NFL news and store the response in the database';

    public function handle()
    {
        $apiKey = config('services.rapidapi.key');
        $apiHost = config('services.rapidapi.host', 'tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com');

        if (!$apiKey) {
            $this->error('RAPIDAPI_KEY is not set in the .env file.');
            return 1;
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
                    foreach ($data['body'] as $newsItem) {
                        NflNews::updateOrCreate(
                            ['link' => $newsItem['link']],
                            ['title' => $newsItem['title']]
                        );
                    }
                    $this->info('NFL News fetched and stored successfully.');
                } else {
                    $this->error('Invalid data format received.');
                }
            } else {
                $this->error('Failed to fetch NFL news.');
                $this->error('Status Code: ' . $response->status());
                $this->error('Response Body: ' . $response->body());
            }
        } catch (Exception $e) {
            $this->error('An error occurred while fetching NFL news: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
