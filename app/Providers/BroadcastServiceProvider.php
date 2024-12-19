<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;
use Log;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Broadcast::routes(['middleware' => ['web', 'auth']]);

        // Add logging
        Broadcast::channel('chat.{id}', function ($user, $id) {
            Log::info('Broadcasting Auth', [
                'user' => $user,
                'id' => $id,
                'channel' => 'chat.' . $id
            ]);
            return (int)$user->id === (int)$id;
        });
    }
}