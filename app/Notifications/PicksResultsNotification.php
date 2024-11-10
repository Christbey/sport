<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Discord\DiscordChannel;
use NotificationChannels\Discord\DiscordMessage;

class PicksResultsNotification extends Notification
{
    public function __construct(
        protected array  $userResults,
        protected string $gameWeek,
        protected array  $weeklyStats,
        protected array  $overallStats
    )
    {
    }

    public function via($notifiable): array
    {
        return ['mail', DiscordChannel::class];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("NFL Pick Results - {$this->gameWeek}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Here are your pick results for {$this->gameWeek}:")
            ->line("Weekly Performance: {$this->weeklyStats['correct']} out of {$this->weeklyStats['total']} ({$this->weeklyStats['percentage']}%)")
            ->line("Overall Season Performance: {$this->overallStats['correct']} out of {$this->overallStats['total']} ({$this->overallStats['percentage']}%)")
            ->line('This Week\'s Pick Details:')
            ->lines(
                collect($this->userResults)->map(function ($pick) {
                    $result = $pick['is_correct'] ? '✅' : '❌';
                    return "{$result} {$pick['game']}: {$pick['team_name']}";
                })
            )
            ->line('Keep up the great picks!')
            ->salutation('See you next week!');
    }

    public function toDiscord($notifiable): DiscordMessage
    {
        $picksMessage = collect($this->userResults)
            ->map(function ($pick) {
                $result = $pick['is_correct'] ? '✅' : '❌';
                return "• {$result} {$pick['game']}: {$pick['team_name']}";
            })
            ->join("\n");

        $description = "Results for **{$notifiable->name}**:\n\n" .
            "**Weekly Performance:** {$this->weeklyStats['correct']}/{$this->weeklyStats['total']} " .
            "({$this->weeklyStats['percentage']}%)\n" .
            "**Season Total:** {$this->overallStats['correct']}/{$this->overallStats['total']} " .
            "({$this->overallStats['percentage']}%)\n\n" .
            "**This Week's Picks:**\n{$picksMessage}";

        return DiscordMessage::create()
            ->embed([
                'title' => "🏈 NFL Pick Results - {$this->gameWeek}",
                'description' => $description,
                'color' => $this->getColorBasedOnPerformance($this->weeklyStats['percentage']),
                'timestamp' => now()->toIso8601String(),
                'footer' => [
                    'text' => '🎲 NFL Pick\'em Results'
                ]
            ]);
    }

    protected function getColorBasedOnPerformance(float $percentage): int
    {
        return match (true) {
            $percentage >= 80 => 0x2ECC71, // Green
            $percentage >= 60 => 0x3498DB, // Blue
            $percentage >= 40 => 0xF1C40F, // Yellow
            default => 0xE74C3C  // Red
        };
    }
}