<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\Discord\DiscordChannel;
use NotificationChannels\Discord\DiscordMessage;

class GameCalculationsNotification extends Notification
{
    public function __construct(
        protected array $games,
        protected array $stats
    )
    {
    }

    public function via($notifiable): array
    {
        return [DiscordChannel::class];
    }

    public function toDiscord($notifiable): DiscordMessage
    {
        return DiscordMessage::create()
            ->embed([
                'title' => '🏈 Game Analysis Summary',
                'description' => $this->generateGamesSummary(),
                'fields' => [
                    [
                        'name' => '📈 Overall Stats',
                        'value' => $this->generateStats(),
                        'inline' => false
                    ]
                ],
                'color' => 0x3498DB, // Blue
                'timestamp' => now()->toIso8601String(),
                'footer' => [
                    'text' => "Total Games Processed: {$this->stats['totalGames']}"
                ]
            ]);
    }

    protected function generateGamesSummary(): string
    {
        $summaries = collect($this->games)->map(function ($game) {
            $result = $game['result'] == 1 ? '✅' : '❌';
            return "{$result} **{$game['awayTeam']}** vs **{$game['homeTeam']}** | " .
                "Spread: {$game['spreadOpen']} | " .
                "Final: {$game['awayPoints']}-{$game['homePoints']}";
        })->join("\n");

        return $summaries;
    }

    protected function generateStats(): string
    {
        return "Games Processed: **{$this->stats['totalGames']}**\n" .
            "Correct Predictions: **{$this->stats['correctPredictions']}**\n" .
            "Accuracy: **{$this->stats['percentage']}%**";
    }
}