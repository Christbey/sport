<?php

namespace App\Console\Commands;

use App\Services\HypotheticalSpreadService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CalculateHypotheticalSpread extends Command
{
    protected $signature = 'calculate:hypothetical-spreads';
    protected $description = 'Calculate hypothetical spreads for upcoming FBS games';

    protected $spreadService;

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
            try {
                $this->spreadService->processGame($game);
            } catch (Exception $e) {
                Log::error("Error processing game ID {$game->id}: " . $e->getMessage());
            }
        }
    }
}
