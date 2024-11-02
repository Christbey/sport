<?php

namespace App\Jobs\CollegeFootball;

use App\Models\CollegeFootball\CollegeFootballTalent;
use App\Notifications\DiscordCommandCompletionNotification;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class StoreCollegeFootballTalent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $year;
    protected $apiUrl;
    protected $apiKey;

    /**
     * Create a new job instance.
     *
     * @param int $year
     */
    public function __construct(int $year)
    {
        $this->year = $year;
        $this->apiUrl = 'https://api.collegefootballdata.com/talent';
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
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $talentData = json_decode($response->getBody()->getContents(), true);

            foreach ($talentData as $talent) {
                CollegeFootballTalent::updateOrCreate(
                    ['year' => $talent['year'], 'school' => $talent['school']],
                    ['talent' => $talent['talent']]
                );
            }
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
