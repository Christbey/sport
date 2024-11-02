<?php

namespace App\Jobs\CollegeFootball;

use App\Models\CollegeFootball\CollegeFootballTeam;
use App\Models\CollegeFootball\CollegeFootballVenue;
use App\Notifications\DiscordCommandCompletionNotification;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class StoreCollegeFootballTeams implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $apiUrl;
    protected $apiKey;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->apiUrl = 'https://api.collegefootballdata.com/teams';
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
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $teams = json_decode($response->getBody()->getContents(), true);

            foreach ($teams as $team) {
                // Store or update the venue first, using data under the location key
                $venue = null;
                if (isset($team['location']) && isset($team['location']['venue_id'])) {
                    $venue = CollegeFootballVenue::updateOrCreate(
                        ['id' => $team['location']['venue_id']],
                        [
                            'name' => $team['location']['name'] ?? null,
                            'city' => $team['location']['city'] ?? null,
                            'state' => $team['location']['state'] ?? null,
                            'zip' => $team['location']['zip'] ?? null,
                            'country_code' => $team['location']['country_code'] ?? null,
                            'timezone' => $team['location']['timezone'] ?? null,
                            'latitude' => $team['location']['latitude'] ?? null,
                            'longitude' => $team['location']['longitude'] ?? null,
                            'elevation' => $team['location']['elevation'] ?? null,
                            'capacity' => $team['location']['capacity'] ?? null,
                            'year_constructed' => $team['location']['year_constructed'] ?? null,
                            'grass' => isset($team['location']['grass']) ? (bool)$team['location']['grass'] : false, // Store as false if null
                            'dome' => isset($team['location']['dome']) ? (bool)$team['location']['dome'] : false,   // Store as false if null
                        ]
                    );
                }

                // Store or update the team
                CollegeFootballTeam::updateOrCreate(
                    ['school' => $team['school']],
                    [
                        'mascot' => $team['mascot'] ?? null,
                        'abbreviation' => $team['abbreviation'] ?? null,
                        'alt_name_1' => $team['alt_name_1'] ?? null,
                        'alt_name_2' => $team['alt_name_2'] ?? null,
                        'alt_name_3' => $team['alt_name_3'] ?? null,
                        'conference' => $team['conference'] ?? null,
                        'color' => $team['color'] ?? null,
                        'alt_color' => $team['alt_color'] ?? null,
                        'logos' => $team['logos'] ?? null,
                        'twitter' => $team['twitter'] ?? null,
                        'venue_id' => $venue ? $venue->id : null,
                        'venue_name' => $venue ? $venue->name : null,
                        'city' => $team['location']['city'] ?? null,
                        'state' => $team['location']['state'] ?? null,
                        'zip' => $team['location']['zip'] ?? null,
                        'country_code' => $team['location']['country_code'] ?? null,
                        'timezone' => $team['location']['timezone'] ?? null,
                        'latitude' => $team['location']['latitude'] ?? null,
                        'longitude' => $team['location']['longitude'] ?? null,
                        'elevation' => $team['location']['elevation'] ?? null,
                        'capacity' => $team['location']['capacity'] ?? null,
                        'year_constructed' => $team['location']['year_constructed'] ?? null,
                        'grass' => isset($team['location']['grass']) ? (bool)$team['location']['grass'] : false, // Store as false if null
                        'dome' => isset($team['location']['dome']) ? (bool)$team['location']['dome'] : false,   // Store as false if null
                    ]
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
