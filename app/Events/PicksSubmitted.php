<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PicksSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User   $user,
        public array  $userPicks,
        public string $gameWeek
    )
    {

        // Add debugging
        Log::info('PicksSubmitted event constructed', [
            'gameWeek' => $this->gameWeek,
            'user' => $this->user->id
        ]);
    }
}