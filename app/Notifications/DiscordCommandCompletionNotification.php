<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class DiscordCommandCompletionNotification extends Notification
{
    use Queueable;

    protected $message;
    protected $type;

    /**
     * Create a new notification instance.
     *
     * @param string $message The message content for the notification.
     * @param string $type The type of message (e.g., 'success' or 'failure').
     */
    public function __construct(string $message, string $type = 'success')
    {
        // Get the calling class name dynamically
        $className = class_basename(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['class']);

        // Format the message
        $this->message = "[$className] " . $message;
        $this->type = $type;

        // Log based on the type of message
        if ($this->type === 'failure') {
            Log::error($this->message);
        } else {
            Log::info($this->message);
        }
    }

    public function via($notifiable)
    {
        return ['discord'];
    }

    public function toDiscord($notifiable)
    {
        // Set color based on message type
        $color = $this->type === 'success' ? hexdec('2ECC71') : hexdec('E74C3C'); // Green for success, Red for failure

        return (object)[
            'body' => '', // Placeholder for compatibility
            'embed' => [
                'title' => ucfirst($this->type) . ' Notification',
                'description' => $this->message,
                'color' => $color,
                'footer' => [
                    'text' => 'Powered by Picksports Alerts',
                ],
                'timestamp' => now()->toIso8601String(),
            ],
            'components' => [],
        ];
    }
}
