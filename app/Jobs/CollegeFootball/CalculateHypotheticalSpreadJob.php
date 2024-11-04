<?php

namespace App\Jobs\CollegeFootball;

use App\Services\HypotheticalSpreadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateHypotheticalSpreadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $game;
    protected HypotheticalSpreadService $spreadService;

    /**
     * Create a new job instance.
     *
     * @param $game
     */
    public function __construct($game)
    {
        $this->game = $game;
        $this->spreadService = app(HypotheticalSpreadService::class);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->spreadService->processGame($this->game);
    }
}
