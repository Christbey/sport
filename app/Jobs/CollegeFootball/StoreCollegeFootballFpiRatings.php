<?php

namespace App\Jobs\CollegeFootball;

use App\Models\CollegeFootball\CollegeFootballFpi;
use App\Models\CollegeFootball\CollegeFootballTeam;
use App\Notifications\DiscordCommandCompletionNotification;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class StoreCollegeFootballFpiRatings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const CACHE_PREFIX = 'cfb_fpi_';
    protected $year;
    protected $apiUrl = 'https://apinext.collegefootballdata.com/ratings/fpi';
    protected $apiKey;

    public function __construct(int $year)
    {
        $this->year = $year;
        $this->apiKey = config('services.college_football_data.key');
    }

    public static function getLastSuccess(): ?array
    {
        return Cache::get(self::CACHE_PREFIX . 'last_success');
    }

    public static function getLastError(): ?array
    {
        return Cache::get(self::CACHE_PREFIX . 'last_error');
    }

    public static function getApiCallsToday(): int
    {
        return (int)Cache::get(self::CACHE_PREFIX . 'api_calls_' . now()->format('Y-m-d'), 0);
    }

    public function handle()
    {
        // Store attempt time
        Cache::put(self::CACHE_PREFIX . 'last_attempt', now(), now()->addDay());

        try {
            $client = new Client();

            // Track API call
            $this->incrementApiCalls();

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

            $stats = $this->processFpiData($data);

            // Cache success details
            $this->cacheSuccess($stats);

            // Send success notification with stats
            $message = $this->prepareSuccessMessage($stats);
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($message, 'success'));

        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    protected function incrementApiCalls(): void
    {
        $key = self::CACHE_PREFIX . 'api_calls_' . now()->format('Y-m-d');
        Cache::increment($key, 1);
        Cache::put($key, Cache::get($key), now()->endOfDay());
    }

    protected function processFpiData(array $data): array
    {
        $updatedTeams = 0;
        $missingTeams = [];
        $significantChanges = [];

        foreach ($data as $fpiData) {
            $team = CollegeFootballTeam::where('school', $fpiData['team'])->first();

            if ($team) {
                // Get previous FPI for comparison
                $previousFpi = CollegeFootballFpi::where('team_id', $team->id)
                    ->where('year', $this->year)
                    ->first();

                CollegeFootballFpi::updateOrCreate(
                    [
                        'team_id' => $team->id,
                        'year' => $this->year,
                    ],
                    [
                        'team' => $fpiData['team'],
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

                $updatedTeams++;

                // Check for significant FPI changes (more than 3 points)
                if ($previousFpi &&
                    $previousFpi->fpi !== null &&
                    $fpiData['fpi'] !== null &&
                    abs($fpiData['fpi'] - $previousFpi->fpi) > 3) {
                    $significantChanges[] = [
                        'team' => $fpiData['team'],
                        'previous' => $previousFpi->fpi,
                        'new' => $fpiData['fpi'],
                        'change' => $fpiData['fpi'] - $previousFpi->fpi
                    ];
                }
            } else {
                $missingTeams[] = $fpiData['team'];
            }
        }

        return [
            'updated_teams' => $updatedTeams,
            'missing_teams' => $missingTeams,
            'significant_changes' => $significantChanges
        ];
    }

    protected function cacheSuccess(array $stats): void
    {
        Cache::put(self::CACHE_PREFIX . 'last_success', [
            'time' => now(),
            'year' => $this->year,
            'stats' => $stats
        ], now()->addDay());
    }

    protected function prepareSuccessMessage(array $stats): string
    {
        $message = "Updated FPI ratings for {$stats['updated_teams']} teams (Year: {$this->year}).";

        if (!empty($stats['missing_teams'])) {
            $message .= "\nMissing teams: " . implode(', ', array_slice($stats['missing_teams'], 0, 3));
            if (count($stats['missing_teams']) > 3) {
                $message .= " and " . (count($stats['missing_teams']) - 3) . " more";
            }
        }

        if (!empty($stats['significant_changes'])) {
            $message .= "\nSignificant FPI changes:";
            foreach (array_slice($stats['significant_changes'], 0, 3) as $change) {
                $direction = $change['change'] > 0 ? '📈' : '📉';
                $message .= sprintf(
                    "\n%s %s: %.2f → %.2f (%+.2f)",
                    $direction,
                    $change['team'],
                    $change['previous'],
                    $change['new'],
                    $change['change']
                );
            }
            if (count($stats['significant_changes']) > 3) {
                $message .= "\n... and " . (count($stats['significant_changes']) - 3) . " more changes";
            }
        }

        return $message;
    }

    protected function handleError(Exception $e): void
    {
        Log::error('College Football FPI fetch failed', [
            'error' => $e->getMessage(),
            'year' => $this->year
        ]);

        Cache::put(self::CACHE_PREFIX . 'last_error', [
            'time' => now(),
            'message' => $e->getMessage(),
            'context' => ['year' => $this->year]
        ], now()->addDay());

        Notification::route('discord', config('services.discord.channel_id'))
            ->notify(new DiscordCommandCompletionNotification($e->getMessage(), 'failure'));
    }
}
