<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DiscordCommandCompletionNotification extends Notification
{
    use Queueable;

    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['discord'];
    }

    public function toDiscord($notifiable)
    {
        return (object)[
            'body' => $this->message,
            'embed' => [], // Optional: provide embeds here if needed
            'components' => [], // Optional: provide components here if needed
        ];
    }
}
