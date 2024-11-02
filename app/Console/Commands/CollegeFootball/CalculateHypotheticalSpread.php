<?php

namespace App\Console\Commands\CollegeFootball;

use App\Jobs\CollegeFootball\CalculateHypotheticalSpreadJob;
use App\Services\HypotheticalSpreadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CalculateHypotheticalSpread extends Command
{
    protected $signature = 'calculate:hypothetical-spreads';
    protected $description = 'Calculate hypothetical spreads for upcoming FBS games';

    protected HypotheticalSpreadService $spreadService;

    public function __construct(HypotheticalSpreadService $spreadService)
    {
        parent::__construct();
        $this->spreadService = $spreadService;
    }

    public function handle()
    {
        $games = $this->spreadService->fetchRelevantGames();

        if ($games->isEmpty()) {
            Log::info('No games found for the specified week and season.');
            return;
        }

        foreach ($games as $game) {
            CalculateHypotheticalSpreadJob::dispatch($game, $this->spreadService);
            $this->info("Dispatched job to calculate spread for game ID {$game->id}");
        }
    }
}
