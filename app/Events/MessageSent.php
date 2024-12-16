<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        // Using the user_id for the channel:
        return new PrivateChannel('chat.' . $this->message->user_id);
    }

    public function broadcastWith()
    {
        // Since $this->message is now an object with string properties, just return them directly
        return [
            'input' => $this->message->input,
            'output' => $this->message->output,
            'user_id' => $this->message->user_id,
            // created_at is already a string, so just return it
            'created_at' => $this->message->created_at,
        ];
    }

}
