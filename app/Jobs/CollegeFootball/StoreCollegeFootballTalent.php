<?php

namespace App\Jobs\CollegeFootball;

use App\Models\CollegeFootball\CollegeFootballTalent;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
        $this->apiKey = env('COLLEGE_FOOTBALL_DATA_API_KEY'); // Store your API key in the .env file
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
        } catch (\Exception $e) {
            Log::error('Failed to store college football talent data: ' . $e->getMessage());
        }
    }
}
