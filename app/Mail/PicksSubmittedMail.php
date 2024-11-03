<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PicksSubmittedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $picks;
    public $gameWeek;

    /**
     * Create a new message instance.
     *
     * @param User $user
     * @param array $picks
     * @param string $gameWeek
     */
    public function __construct(User $user, $picks, $gameWeek)
    {
        $this->user = $user;
        $this->picks = $picks;
        $this->gameWeek = $gameWeek;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Your Picks for ' . $this->gameWeek)
            ->markdown('emails.picks_submitted');
    }
}
