<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NflOddsProcessed
{
    use Dispatchable, SerializesModels;

    public string $date;
    public array $changes;

    public function __construct(string $date, array $changes)
    {
        $this->date = $date;
        $this->changes = $changes;
    }
}