<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Discord\DiscordChannel;
use NotificationChannels\Discord\DiscordMessage;

class PicksSubmittedNotification extends Notification
{
    public function __construct(
        protected array  $picks,
        protected string $gameWeek
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
            ->subject("NFL Picks Submitted for {$this->gameWeek}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("You've successfully submitted your picks for {$this->gameWeek}.")
            ->line('Here are your picks:')
            ->lines(
                collect($this->picks)->map(fn($pick) => "• {$pick['game']}: {$pick['team_name']}")
            )
            ->line('Good luck!')
            ->salutation('Thanks for playing!');
    }

    public function toDiscord($notifiable): DiscordMessage
    {
        $picksMessage = collect($this->picks)
            ->map(fn($pick) => "• {$pick['game']}: {$pick['team_name']}")
            ->join("\n");

        return DiscordMessage::create()
            ->embed([
                'title' => "🏈 NFL Picks Submitted - {$this->gameWeek}",
                'description' => "**{$notifiable->name}** has submitted their picks:\n\n{$picksMessage}",
                'color' => 0x1ABC9C, // Teal color
                'timestamp' => now()->toIso8601String(),
                'footer' => [
                    'text' => '🎲 NFL Pick\'em'
                ]
            ]);
    }
}