<?php

namespace App\Jobs\CollegeFootball;

use App\Models\CollegeFootball\CollegeFootballFpi;
use App\Models\CollegeFootball\CollegeFootballTeam;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StoreCollegeFootballFpiRatings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $year;
    protected $apiUrl = 'https://apinext.collegefootballdata.com/ratings/fpi';
    protected $apiKey;

    /**
     * Create a new job instance.
     *
     * @param int $year
     */
    public function __construct(int $year)
    {
        $this->year = $year;
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
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            foreach ($data as $fpiData) {
                // Lookup team_id based on the team name
                $team = CollegeFootballTeam::where('school', $fpiData['team'])->first();

                if ($team) {
                    CollegeFootballFpi::updateOrCreate(
                        [
                            'team_id' => $team->id,
                            'year' => $this->year,
                        ],
                        [
                            'team' => $fpiData['team'],  // Keeping team name as per your request
                            'conference' => $fpiData['conference'] ?? null,
                            'fpi' => $fpiData['fpi'] ?? null,
                            'strength_of_record' => $fpiData['resumeRanks']['strengthOfRecord'] ?? null,
                            'average_win_probability' => $fpiData['resumeRanks']['averageWinProbability'] ?? null,
                            'strength_of_schedule' => $fpiData['resumeRanks']['strengthOfSchedule'] ?? null,
                            'remaining_strength_of_schedule' => $fpiData['resumeRanks']['remainingStrengthOfSchedule'] ?? null,
                            'game_control' => $fpiData['resumeRanks']['gameControl'] ?? null,
                            'overall' => $fpiData['efficiencies']['overall'] ?? null,
                            'offense' => $fpiData['efficiencies']['offense'] ?? null,
                            'defense' => $fpiData['efficiencies']['defense'] ?? null,
                            'special_teams' => $fpiData['efficiencies']['specialTeams'] ?? null,
                        ]
                    );
                } else {
                    Log::warning('Team not found for FPI rating: ' . $fpiData['team']);
                }
            }

            Log::info('College Football FPI data fetched and stored successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to fetch and store College Football FPI data: ' . $e->getMessage());
        }
    }
}
