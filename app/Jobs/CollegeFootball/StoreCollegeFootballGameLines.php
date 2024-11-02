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

class StoreCollegeFootballGameLines implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $year;
    protected $apiUrl = 'https://apinext.collegefootballdata.com/lines';
    protected $apiKey;

    /**
     * Create a new job instance.
     *
     * @param int $year
     */
    public function __construct(int $year)
    {
        $this->year = $year;
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
                    'provider' => 'DraftKings', // Assuming provider is always DraftKings
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            foreach ($data as $gameData) {
                // Check if game exists
                $game = CollegeFootballGame::where('id', $gameData['id'])->first();

                if ($game) {
                    // Assuming 'lines' is always an array with at least one element
                    $line = $gameData['lines'][0];

                    // Update the game with the line data
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

                    Log::info('Betting line data updated for game ID: ' . $game->id);
                } else {
                    Log::info('No game found for ID: ' . $gameData['id']);
                }
            }
            // Send success notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification(''));

        } catch (Exception $e) {
            // Send failure notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($e->getMessage(), 'error'));

        }

    }
}
