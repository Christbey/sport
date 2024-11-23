<?php

namespace App\Jobs\CollegeFootball;

use App\Models\CollegeFootball\{CollegeFootballGame, CollegeFootballTeam};
use App\Notifications\DiscordCommandCompletionNotification;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class StoreCollegeFootballGames implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const API_URL = 'https://api.collegefootballdata.com/games?year=2024';

    public function __construct(
        private int     $year,
        private ?string $apiKey = null
    )
    {
        $this->apiKey = $apiKey ?? config('services.college_football_data.key');
    }

    public function handle()
    {
        try {
            $games = $this->fetchGamesData();
            $this->processGames($games);
            $this->sendNotification();
        } catch (Exception $e) {
            $this->sendNotification($e->getMessage(), 'error');
        }
    }

    private function fetchGamesData(): array
    {
        $client = new Client();
        $response = $client->request('GET', self::API_URL, [
            'query' => ['year' => $this->year],
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Accept' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    private function processGames(array $games): void
    {
        foreach ($games as $game) {
            $homeTeam = $this->storeTeam($game, 'home');
            $awayTeam = $this->storeTeam($game, 'away');
            $this->storeGame($game, $homeTeam, $awayTeam);
        }
    }

    private function storeTeam(array $game, string $type): CollegeFootballTeam
    {
        return CollegeFootballTeam::updateOrCreate(
            ['school' => $game["{$type}_team"]],
            ['conference' => $game["{$type}_conference"] ?? null]
        );
    }

    private function storeGame(array $game, CollegeFootballTeam $homeTeam, CollegeFootballTeam $awayTeam): void
    {
        CollegeFootballGame::updateOrCreate(
            ['id' => $game['id']],
            [
                'season' => $game['season'] ?? null,
                'week' => $game['week'] ?? null,
                'season_type' => $game['season_type'] ?? null,
                'start_date' => $game['start_date'] ?? null,
                'start_time_tbd' => $game['start_time_tbd'] ?? false,
                'completed' => $game['completed'] ?? false,
                'neutral_site' => $game['neutral_site'] ?? false,
                'conference_game' => $game['conference_game'] ?? false,
                'attendance' => $game['attendance'] ?? null,
                'venue' => $game['venue'] ?? null,
                'home_id' => $homeTeam->id,
                'home_team' => $game['home_team'] ?? null,
                'home_conference' => $game['home_conference'] ?? null,
                'home_division' => $game['home_division'] ?? null,
                'home_points' => $game['home_points'] ?? null,
                'home_line_scores' => json_encode($game['home_line_scores'] ?? []),
                'home_post_win_prob' => $game['home_post_win_prob'] ?? null,
                'home_pregame_elo' => $game['home_pregame_elo'] ?? null,
                'home_postgame_elo' => $game['home_postgame_elo'] ?? null,
                'away_id' => $awayTeam->id,
                'away_team' => $game['away_team'] ?? null,
                'away_conference' => $game['away_conference'] ?? null,
                'away_division' => $game['away_division'] ?? null,
                'away_points' => $game['away_points'] ?? null,
                'away_line_scores' => json_encode($game['away_line_scores'] ?? []),
                'away_post_win_prob' => $game['away_post_win_prob'] ?? null,
                'away_pregame_elo' => $game['away_pregame_elo'] ?? null,
                'away_postgame_elo' => $game['away_postgame_elo'] ?? null,
            ]
        );
    }

    private function sendNotification(string $message = '', string $status = 'success'): void
    {
        Notification::route('discord', config('services.discord.channel_id'))
            ->notify(new DiscordCommandCompletionNotification($message, $status));
    }
}