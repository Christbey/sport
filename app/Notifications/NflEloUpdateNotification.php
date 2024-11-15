<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use NotificationChannels\Discord\DiscordChannel;
use NotificationChannels\Discord\DiscordMessage;

class NflEloUpdateNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Collection  $teams,
        protected string|int  $year,
        protected ?Collection $spreadChanges = null,
        protected string      $type = 'success'
    )
    {
    }

    public function via($notifiable): array
    {
        return [DiscordChannel::class];
    }

    public function toDiscord($notifiable): DiscordMessage
    {
        $status = $this->type === 'success' ? '✅' : '❌';
        $description = "NFL ELO Predictions Updated\n\n";

        if ($this->spreadChanges && $this->spreadChanges->isNotEmpty()) {
            $description .= "**Spread Changes:**\n\n";

            $this->spreadChanges->each(function ($change) use (&$description) {
                $matchup = $this->formatGameId($change['game_id']);
                $oldSpread = round($change['old_spread'], 1);
                $newSpread = round($change['new_spread'], 1);
                $difference = round($newSpread - $oldSpread, 1);
                $arrow = $difference > 0 ? "⬆️" : "⬇️";

                $description .= sprintf(
                    "**%s** (Week %s)\n%s %.1f → %.1f (%+.1f)\n\n",
                    $matchup,
                    $change['week'],
                    $arrow,
                    $oldSpread,
                    $newSpread,
                    $difference
                );
            });
        }

        return DiscordMessage::create()
            ->embed([
                'title' => "{$status} NFL Spread Updates",
                'description' => $description,
                'color' => 0x00FF00,
                'footer' => [
                    'text' => '🏈 NFL Predictions'
                ]
            ]);
    }

    protected function formatGameId(string $gameId): string
    {
        // Convert 20241124_ARI@SEA to ARI @ SEA
        $parts = explode('_', $gameId);
        return str_replace('@', ' @ ', $parts[1] ?? $gameId);
    }
}
