<?php

namespace App\Jobs\CollegeFootball;

use App\Models\CollegeFootball\CollegeFootballGame;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StoreCollegeFootballWeatherData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $year;

    /**
     * Create a new job instance.
     *
     * @param int $year
     */
    public function __construct($year)
    {
        $this->year = $year;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $client = new Client(['base_uri' => 'https://apinext.collegefootballdata.com/']);
        $response = $client->request('GET', 'games/weather', [
            'query' => [
                'year' => $this->year,
            ],
            'headers' => [
                'Authorization' => 'Bearer ' . env('COLLEGE_FOOTBALL_DATA_API_KEY'),
                'Accept' => 'application/json',
            ],
        ]);

        $weatherData = json_decode($response->getBody()->getContents(), true);

        try {
            foreach ($weatherData as $weather) {
                $game = CollegeFootballGame::where('id', $weather['id'])->first();

                if ($game) {
                    $game->update([
                        'temperature' => $weather['temperature'] ?? null,
                        'dew_point' => $weather['dewPoint'] ?? null,
                        'humidity' => $weather['humidity'] ?? null,
                        'precipitation' => $weather['precipitation'] ?? null,
                        'snowfall' => $weather['snowfall'] ?? null,
                        'wind_direction' => $weather['windDirection'] ?? null,
                        'wind_speed' => $weather['windSpeed'] ?? null,
                        'pressure' => $weather['pressure'] ?? null,
                        'weather_condition_code' => $weather['weatherConditionCode'] ?? null,
                        'weather_condition' => $weather['weatherCondition'] ?? null,
                    ]);
                } else {
                    Log::info('No game found for ID: ' . $weather['id']);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error updating weather data: ' . $e->getMessage());
        }
    }
}
