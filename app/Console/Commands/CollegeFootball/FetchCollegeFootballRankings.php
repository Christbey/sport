<?php

namespace App\Jobs\CollegeFootball;

use App\Models\CollegeFootball\CollegeFootballPregame;
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

class StoreCollegeFootballPregameProbabilities implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const API_URL = 'https://apinext.collegefootballdata.com/metrics/wp/pregame';

    private int $year;
    private ?int $week;
    private ?string $team;
    private ?string $seasonType;
    private string $apiKey;

    public function __construct(int $year, ?int $week = null, ?string $team = null, ?string $seasonType = null)
    {
        $this->year = $year;
        $this->week = $week;
        $this->team = $team;
        $this->seasonType = $seasonType;
        $this->apiKey = config('services.college_football_data.key');
    }

    public function handle(): void
    {
        try {
            $pregameData = $this->fetchPregameData();
            $stats = $this->processPregameData($pregameData);
            $this->sendSuccessNotification($stats);
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    private function fetchPregameData(): array
    {
        $client = new Client();
        $response = $client->request('GET', self::API_URL, [
            'query' => array_filter([
                'year' => $this->year,
                'week' => $this->week,
                'team' => $this->team,
                'seasonType' => $this->seasonType,
            ]),
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Accept' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    private function processPregameData(array $pregameData): array
    {
        $stats = ['updated' => 0, 'total' => count($pregameData)];

        foreach ($pregameData as $data) {
            CollegeFootballPregame::updateOrCreate(
                [
                    'game_id' => $data['gameId'],
                    'season' => $data['season'],
                    'week' => $data['week'],
                ],
                [
                    'season_type' => $data['seasonType'],
                    'home_team' => $data['homeTeam'],
                    'away_team' => $data['awayTeam'],
                    'spread' => $data['spread'] ?? null,
                    'home_win_prob' => $data['homeWinProbability'] ?? null,
                ]
            );
            $stats['updated']++;
        }

        return $stats;
    }

    private function sendSuccessNotification(array $stats): void
    {
        $message = "**Pregame Probabilities Update**\n";
        $message .= "Year: {$this->year}";
        if ($this->week) $message .= ", Week: {$this->week}";
        if ($this->team) $message .= ", Team: {$this->team}";
        $message .= "\nUpdated {$stats['updated']} of {$stats['total']} games";

        Notification::route('discord', config('services.discord.channel_id'))
            ->notify(new DiscordCommandCompletionNotification($message, 'success'));
    }

    private function handleError(Exception $e): void
    {
        Log::error('Failed to fetch pregame probabilities', [
            'error' => $e->getMessage(),
            'year' => $this->year,
            'week' => $this->week,
            'team' => $this->team
        ]);

        Notification::route('discord', config('services.discord.channel_id'))
            ->notify(new DiscordCommandCompletionNotification($e->getMessage(), 'error'));

        throw $e;
    }
}