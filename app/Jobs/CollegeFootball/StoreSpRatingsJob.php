<?php

namespace App\Jobs\CollegeFootball;

use App\Models\CollegeFootball\CollegeFootballTeam;
use App\Models\CollegeFootball\SpRating;
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

class StoreSpRatingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $year;
    protected $apiUrl;
    protected $apiKey;

    public function __construct(int $year)
    {
        $this->year = $year;
        $this->apiUrl = 'ratings/sp?year=' . $this->year;
        $this->apiKey = config('services.college_football_data.key');
    }

    public function handle()
    {
        $client = new Client(['base_uri' => 'https://api.collegefootballdata.com/']);

        try {
            $response = $client->request('GET', $this->apiUrl, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);
                foreach ($data as $rating) {
                    $this->storeSpRating($rating);
                }
                Log::info("SP+ ratings for year {$this->year} fetched and stored successfully.");
            } else {
                Log::error("Failed to fetch SP+ ratings for year {$this->year}. Status code: {$response->getStatusCode()}");
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

    private function storeSpRating($rating)
    {
        $team = CollegeFootballTeam::where('school', $rating['team'])->first();
        $teamId = $team?->id;

        SpRating::updateOrCreate(
            ['team' => $rating['team']],
            [
                'team_id' => $teamId,
                'conference' => $rating['conference'] ?? 'National Average',
                'overall_rating' => $rating['rating'],
                'ranking' => $rating['ranking'] ?? null,
                'offense_ranking' => $rating['offense']['ranking'] ?? null,
                'offense_rating' => $rating['offense']['rating'] ?? null,
                'defense_ranking' => $rating['defense']['ranking'] ?? null,
                'defense_rating' => $rating['defense']['rating'] ?? null,
                'special_teams_rating' => $rating['specialTeams']['rating'] ?? null,
            ]
        );
    }
}
