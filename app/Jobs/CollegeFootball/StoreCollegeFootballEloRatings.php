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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class StoreCollegeFootballEloRatings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected const CACHE_PREFIX = 'cfb_elo_';
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

    /**
     * Get info about the last successful fetch
     */
    public static function getLastSuccess(): ?array
    {
        return Cache::get(self::CACHE_PREFIX . 'last_success');
    }

    /**
     * Get the last error if any
     */
    public static function getLastError(): ?array
    {
        return Cache::get(self::CACHE_PREFIX . 'last_error');
    }

    /**
     * Get the number of API calls made today
     */
    public static function getApiCallsToday(): int
    {
        return (int)Cache::get(self::CACHE_PREFIX . 'api_calls_' . now()->format('Y-m-d'));
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

            $updatedTeams = 0;
            $missingTeams = [];
            $significantChanges = [];

            foreach ($eloData as $elo) {
                $team = CollegeFootballTeam::where('school', $elo['team'])->first();

                if ($team) {
                    // Check for significant ELO changes
                    $previousElo = CollegeFootballElo::where('team_id', $team->id)
                        ->where('year', $elo['year'])
                        ->value('elo');

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

                    $updatedTeams++;

                    // Track significant ELO changes (more than 50 points)
                    if ($previousElo && abs($elo['elo'] - $previousElo) > 50) {
                        $significantChanges[] = [
                            'team' => $elo['team'],
                            'previous' => $previousElo,
                            'new' => $elo['elo'],
                            'change' => $elo['elo'] - $previousElo
                        ];
                    }
                } else {
                    $missingTeams[] = $elo['team'];
                }
            }

            // Cache success details
            $this->cacheSuccess([
                'updated_teams' => $updatedTeams,
                'missing_teams' => $missingTeams,
                'significant_changes' => $significantChanges
            ]);

            // Prepare notification message
            $message = $this->prepareSuccessMessage($updatedTeams, $missingTeams, $significantChanges);

            // Send success notification
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

    protected function cacheSuccess(array $details): void
    {
        Cache::put(self::CACHE_PREFIX . 'last_success', [
            'time' => now(),
            'details' => $details
        ], now()->addDay());
    }

    protected function prepareSuccessMessage(int $updatedTeams, array $missingTeams, array $significantChanges): string
    {
        $message = "Updated ELO ratings for {$updatedTeams} teams.";

        if (!empty($missingTeams)) {
            $message .= "\nMissing teams: " . implode(', ', array_slice($missingTeams, 0, 3));
            if (count($missingTeams) > 3) {
                $message .= " and " . (count($missingTeams) - 3) . " more";
            }
        }

        if (!empty($significantChanges)) {
            $message .= "\nSignificant ELO changes:";
            foreach (array_slice($significantChanges, 0, 3) as $change) {
                $direction = $change['change'] > 0 ? '📈' : '📉';
                $message .= "\n{$direction} {$change['team']}: " .
                    round($change['previous']) . " → " .
                    round($change['new']) .
                    " (" . ($change['change'] > 0 ? '+' : '') .
                    round($change['change']) . ")";
            }
            if (count($significantChanges) > 3) {
                $message .= "\n... and " . (count($significantChanges) - 3) . " more changes";
            }
        }

        return $message;
    }

    protected function handleError(Exception $e): void
    {
        Log::error('College Football ELO fetch failed', [
            'error' => $e->getMessage(),
            'year' => $this->year,
            'week' => $this->week
        ]);

        Cache::put(self::CACHE_PREFIX . 'last_error', [
            'time' => now(),
            'message' => $e->getMessage(),
            'context' => [
                'year' => $this->year,
                'week' => $this->week
            ]
        ], now()->addDay());

        Notification::route('discord', config('services.discord.channel_id'))
            ->notify(new DiscordCommandCompletionNotification($e->getMessage(), 'failure'));
    }
}
