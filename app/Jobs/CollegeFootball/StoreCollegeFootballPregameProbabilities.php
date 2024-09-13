<?php

namespace App\Jobs\CollegeFootball;

use App\Models\CollegeFootball\CollegeFootballPregame;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StoreCollegeFootballPregameProbabilities implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $year;
    protected $week;
    protected $team;
    protected $seasonType;

    protected $apiUrl = 'https://apinext.collegefootballdata.com/metrics/wp/pregame';
    protected $apiKey;

    /**
     * Create a new job instance.
     *
     * @param int $year
     * @param int|null $week
     * @param string|null $team
     * @param string|null $seasonType
     */
    public function __construct($year, $week = null, $team = null, $seasonType = null)
    {
        $this->year = $year;
        $this->week = $week;
        $this->team = $team;
        $this->seasonType = $seasonType;
        $this->apiKey =config('services.college_football_data.key');
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
                    'team' => $this->team,
                    'seasonType' => $this->seasonType,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $pregameData = json_decode($response->getBody(), true);

            foreach ($pregameData as $data) {
                // Check if 'homeWinProb' exists and set to null if not present

                CollegeFootballPregame::updateOrCreate(
                    [
                        'game_id' => $data['gameId'],
                        'season' => $data['season'],
                        'week' => $data['week'],
                    ], // Fields to match for update
                    [
                        'season_type' => $data['seasonType'],
                        'home_team' => $data['homeTeam'],
                        'away_team' => $data['awayTeam'],
                        'spread' => $data['spread'],
                        'home_win_prob' => $data['homeWinProbability'],
                    ] // Fields to update or insert
                );
            }

            Log::info('Pregame win probabilities data fetched and stored successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to fetch and store pregame win probabilities data: ' . $e->getMessage());
        }
    }
}
