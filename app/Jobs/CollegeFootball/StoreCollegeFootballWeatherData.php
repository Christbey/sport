<?php
// And here's the improved job:

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
use Illuminate\Support\Facades\{Log, Notification};

class StoreCollegeFootballWeatherData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const API_URL = 'https://apinext.collegefootballdata.com/games/weather';

    private int $year;
    private bool $force;

    public function __construct(int $year, bool $force = false)
    {
        $this->year = $year;
        $this->force = $force;
    }

    public function handle(): void
    {
        try {
            $weatherData = $this->fetchWeatherData();
            $stats = $this->processWeatherData($weatherData);
            $this->sendSuccessNotification($stats);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    private function fetchWeatherData(): array
    {
        $client = new Client();
        $response = $client->request('GET', self::API_URL, [
            'query' => ['year' => $this->year],
            'headers' => [
                'Authorization' => 'Bearer ' . config('services.college_football_data.key'),
                'Accept' => 'application/json',
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        if (empty($data)) {
            throw new Exception("No weather data found for year {$this->year}");
        }

        return $data;
    }

    private function processWeatherData(array $weatherData): array
    {
        $stats = [
            'total' => count($weatherData),
            'updated' => 0,
            'missing' => 0,
            'conditions' => []
        ];

        foreach ($weatherData as $weather) {
            $game = CollegeFootballGame::find($weather['id']);

            if (!$game) {
                $stats['missing']++;
                Log::info("No game found for ID: {$weather['id']}");
                continue;
            }

            $this->updateGameWeather($game, $weather);
            $stats['updated']++;

            // Track weather conditions
            $condition = $weather['weatherCondition'] ?? 'Unknown';
            $stats['conditions'][$condition] = ($stats['conditions'][$condition] ?? 0) + 1;
        }

        return $stats;
    }

    private function updateGameWeather(CollegeFootballGame $game, array $weather): void
    {
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
    }

    private function sendSuccessNotification(array $stats): void
    {
        $message = "**Weather Data Update**\n";
        $message .= "Year: {$this->year}\n\n";
        $message .= "Updated {$stats['updated']} of {$stats['total']} games\n";

        if ($stats['missing'] > 0) {
            $message .= "Missing games: {$stats['missing']}\n";
        }

        // Add top 3 weather conditions if any exist
        if (!empty($stats['conditions'])) {
            arsort($stats['conditions']);
            $topConditions = array_slice($stats['conditions'], 0, 3);
            $message .= "\nTop weather conditions:\n";
            foreach ($topConditions as $condition => $count) {
                $message .= "• {$condition}: {$count} games\n";
            }
        }

        $message .= "\n_Powered by Picksports Alerts • " . now()->format('F j, Y g:i A') . '_';

        Notification::route('discord', config('services.discord.channel_id'))
            ->notify(new DiscordCommandCompletionNotification($message, 'success'));
    }

    private function handleError(Exception $e): void
    {
        Log::error('Failed to fetch weather data', [
            'error' => $e->getMessage(),
            'year' => $this->year
        ]);

        Notification::route('discord', config('services.discord.channel_id'))
            ->notify(new DiscordCommandCompletionNotification(
                "Failed to fetch weather data for year {$this->year}: {$e->getMessage()}",
                'error'
            ));

        throw $e;
    }
}