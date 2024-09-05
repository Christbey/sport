<?php

namespace App\Jobs\CollegeFootball;

use App\Models\CollegeFootball\CollegeFootballElo;
use App\Models\CollegeFootball\CollegeFootballTeam;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
        $this->apiKey = env('COLLEGE_FOOTBALL_DATA_API_KEY');
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
                // Lookup the team_id based on the team name
                $team = CollegeFootballTeam::where('school', $elo['team'])->first();

                if ($team) {
                    // Use team_id as the primary key for the elo table
                    CollegeFootballElo::updateOrCreate(
                        [
                            'team_id' => $team->id,  // Use team_id here instead of team name
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

            Log::info('ELO ratings fetched and stored successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to fetch and store ELO ratings: ' . $e->getMessage());
        }
    }
}
