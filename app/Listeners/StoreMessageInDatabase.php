<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Models\Conversation;

class StoreMessageInDatabase
{
    /**
     * Handle the event.
     *
     * @param MessageSent $event
     * @return void
     */
    public function handle(MessageSent $event)
    {
        // Store the message in the database
        Conversation::create([
            'user_id' => $event->message->user_id,
            'input' => $event->message->input,
            'output' => $event->message->output,
            'created_at' => $event->message->created_at,
        ]);
    }
}
