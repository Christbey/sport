<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     *
     * @param object $message
     * @return void
     */
    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return PrivateChannel
     */
    public function broadcastOn()
    {
        // Using the user_id for the channel:
        return new PrivateChannel('chat.' . $this->message->user_id);
    }

    /**
     * Define the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'input' => $this->message->input,
            'output' => $this->message->output,
            'user_id' => $this->message->user_id,
            'created_at' => $this->message->created_at->toIso8601String(), // Convert to string here
        ];
    }

    /**
     * Optionally, define a custom broadcast event name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'MessageSent';
    }
}
