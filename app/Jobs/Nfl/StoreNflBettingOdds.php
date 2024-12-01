<?php

namespace App\Jobs\Nfl;

use App\Models\Nfl\NflBettingOdds;
use App\Models\Nfl\NflTeamSchedule;
use App\Notifications\DiscordCommandCompletionNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class StoreNflBettingOdds implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const SPORTSBOOK = 'draftkings';
    private const API_ENDPOINT = 'https://tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com/getNFLBettingOdds';

    protected $gameDate;

    public function __construct($gameDate)
    {
        $this->gameDate = $gameDate;
    }

    public function handle()
    {
        try {
            $response = $this->fetchOddsData();

            if (!$response->successful()) {
                throw new Exception("API request failed with status: {$response->status()}");
            }

            $data = $response->json();
            $eventIds = array_column($data['body'] ?? [], 'gameID');
            $schedules = NflTeamSchedule::whereIn('game_id', $eventIds)->get()->keyBy('event_id');

            $changes = $this->processOddsData($data, $schedules);
            $this->sendNotification($changes);

            Log::info('NFL betting odds updated successfully for date: ' . $this->gameDate);
        } catch (Exception $e) {
            Log::error('Failed to process NFL betting odds', [
                'date' => $this->gameDate,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function fetchOddsData()
    {
        return Http::withHeaders([
            'x-rapidapi-host' => config('services.rapidapi.host'),
            'x-rapidapi-key' => config('services.rapidapi.key'),
        ])->get(self::API_ENDPOINT, [
            'gameDate' => $this->gameDate,
            'itemFormat' => 'list',
            'impliedTotals' => 'true',
        ]);
    }

    private function processOddsData(array $data, $schedules): array
    {
        $changes = [];

        foreach ($data['body'] ?? [] as $game) {
            $odds = $this->getSportsbookOdds($game['sportsBooks'] ?? []);
            if (!$odds) continue;

            $existingOdds = NflBettingOdds::where('event_id', $game['gameID'])->where('source', self::SPORTSBOOK)->first();
            $newOdds = [
                'spread_home' => $this->parseFloat($odds['homeTeamSpread']),
                'total_over' => $this->parseFloat($odds['totalOver'] ?? null),
                'moneyline_home' => $this->parseFloat($odds['homeTeamMLOdds']),
                'moneyline_away' => $this->parseFloat($odds['awayTeamMLOdds']),
            ];

            $changes = array_merge($changes, $this->detectChanges($existingOdds, $newOdds));
            $this->updateOdds($game, $odds);
        }

        return $changes;
    }

    private function getSportsbookOdds(array $sportsBooks)
    {
        foreach ($sportsBooks as $sportsBook) {
            if (strtolower($sportsBook['sportsBook'] ?? '') === self::SPORTSBOOK) {
                return $sportsBook['odds'] ?? null;
            }
        }
        return null;
    }

    private function parseFloat($value): ?float
    {
        return is_numeric($value) ? (float)$value : null;
    }

    private function detectChanges($existingOdds, array $newOdds): array
    {
        if (!$existingOdds) {
            return ['initial_odds' => $newOdds];
        }

        $changes = [];
        foreach ($newOdds as $key => $newValue) {
            if (abs($newValue - ($existingOdds->$key ?? 0)) >= 0.5) {
                $changes[$key] = ['old' => $existingOdds->$key, 'new' => $newValue];
            }
        }

        return $changes;
    }

    private function updateOdds(array $game, array $odds): void
    {
        NflBettingOdds::updateOrCreate(
            [
                'event_id' => $game['gameID'],
                'source' => self::SPORTSBOOK,
            ],
            [
                'game_date' => Carbon::createFromFormat('Ymd', $game['gameDate'] ?? '')->format('Y-m-d'),
                'away_team' => $game['awayTeam'],
                'home_team' => $game['homeTeam'],
                'away_team_id' => $game['teamIDAway'],
                'home_team_id' => $game['teamIDHome'],
                'spread_home' => $this->parseFloat($odds['homeTeamSpread']),
                'spread_away' => $this->parseFloat($odds['awayTeamSpread']),
                'total_under' => $this->parseFloat($odds['totalUnder'] ?? null),
                'implied_total_home' => $this->parseFloat($odds['impliedTotals']['homeTotal']),
                'implied_total_away' => $this->parseFloat($odds['impliedTotals']['awayTotal']),
                'total_over' => $this->parseFloat($odds['totalOver'] ?? null),
                'moneyline_home' => $this->parseFloat($odds['homeTeamMLOdds']),
                'moneyline_away' => $this->parseFloat($odds['awayTeamMLOdds']),
            ]
        );
    }

    private function sendNotification(array $changes): void
    {
        $message = empty($changes)
            ? 'ℹ️ No odds updates available.'
            : "📊 Betting odds updated for {$this->gameDate}: " . json_encode($changes);

        Notification::route('discord', config('services.discord.channel_id'))
            ->notify(new DiscordCommandCompletionNotification($message, 'success'));
    }
}
