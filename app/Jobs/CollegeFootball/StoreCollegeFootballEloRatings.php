<?php

namespace App\Jobs\CollegeFootball;

use App\Models\CollegeFootball\CollegeFootballElo;
use App\Models\CollegeFootball\CollegeFootballTeam;
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

class StoreCollegeFootballEloRatings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $year;
    protected $week;
    protected $seasonType;
    protected $team;
    protected $conference;

    protected $apiUrl = 'https://apinext.collegefootballdata.com/ratings/elo';
    protected $apiKey;

    public function __construct(array $params)
    {
        $this->year = $params['year'] ?? null;
        $this->week = $params['week'] ?? null;
        $this->seasonType = $params['seasonType'] ?? null;
        $this->team = $params['team'] ?? null;
        $this->conference = $params['conference'] ?? null;
        $this->apiKey = config('services.college_football_data.key');
    }

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
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $eloData = json_decode($response->getBody(), true);

            foreach ($eloData as $elo) {
                $team = CollegeFootballTeam::where('school', $elo['team'])->first();

                if ($team) {
                    CollegeFootballElo::updateOrCreate(
                        [
                            'team_id' => $team->id,
                            'year' => $elo['year'],
                        ],
                        [
                            'team' => $elo['team'],
                            'conference' => $elo['conference'],
                            'elo' => $elo['elo'],
                        ]
                    );
                } else {
                    Log::warning('Team not found for ELO rating: ' . $elo['team']);
                }
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
