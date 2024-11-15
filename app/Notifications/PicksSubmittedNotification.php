<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Log;
use NotificationChannels\Discord\DiscordChannel;
use NotificationChannels\Discord\DiscordMessage;

class PicksSubmittedNotification extends Notification
{
    public function __construct(
        protected array  $picks,
        protected string $gameWeek
    )
    {
        Log::info('PicksSubmittedNotification constructed', [
            'gameWeek' => $this->gameWeek,
            'picks_count' => count($this->picks)
        ]);
    }

    public function via($notifiable): array
    {
        return ['mail', DiscordChannel::class];
    }

    public function toMail($notifiable): MailMessage
    {
        $picksWithMatchups = collect($this->picks)->map(function ($pick) {
            return "• {$pick['team_name']} ({$pick['away_team']} @ {$pick['home_team']})";
        });

        return (new MailMessage)
            ->subject("NFL Picks Submitted for Week {$this->gameWeek}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("You've successfully submitted your picks for Week {$this->gameWeek}.")
            ->line('Here are your picks:')
            ->lines($picksWithMatchups)
            ->line('Good luck!')
            ->salutation('Thanks for playing!');
    }

    public function toDiscord($notifiable): DiscordMessage
    {
        $picksMessage = collect($this->picks)
            ->map(function ($pick) {
                return "• {$pick['team_name']} ({$pick['away_team']} @ {$pick['home_team']})";
            })
            ->join("\n");

        return DiscordMessage::create()
            ->embed([
                'title' => "🏈 NFL Picks Submitted - Week {$this->gameWeek}",
                'description' => "**{$notifiable->name}** has submitted their picks:\n\n{$picksMessage}",
                'color' => 0x1ABC9C,
                'timestamp' => now()->toIso8601String(),
                'footer' => [
                    'text' => '🎲 NFL Pick\'em'
                ]
            ]);
    }
}