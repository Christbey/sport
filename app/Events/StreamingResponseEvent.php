<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class StreamingResponseEvent implements ShouldBroadcastNow
{
    use InteractsWithSockets;

    public $userId;
    public $chunk;

    public function __construct($userId, $chunk)
    {
        $this->userId = $userId;
        $this->chunk = $chunk;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('streaming-response.' . $this->userId);
    }

    public function broadcastAs()
    {
        return 'streaming-chunk';
    }
}