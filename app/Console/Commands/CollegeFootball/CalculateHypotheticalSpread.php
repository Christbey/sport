<?php

namespace App\Console\Commands\CollegeFootball;

use App\Services\HypotheticalSpreadService;
use Exception;
use Illuminate\Console\Command;

class CalculateHypotheticalSpread extends Command
{
    protected $signature = 'calculate:hypothetical-spreads {--week= : Specific week to calculate} {--force : Force recalculation of existing spreads}';
    protected $description = 'Calculate hypothetical spreads for college football games';

    public function handle(HypotheticalSpreadService $service): int
    {
        $week = $this->option('week');
        $force = $this->option('force');

        $this->info('Calculating spreads for ' . ($week ? "week $week" : 'current week'));

        if ($force) {
            $this->info('Force recalculation enabled');
        }

        try {
            $games = $service->fetchRelevantGames($week ? (int)$week : null, $force);

            if ($games->isEmpty()) {
                $this->warn('No games found for ' . ($week ? "week $week" : 'current week'));
                return 1;
            }

            $this->info("Found {$games->count()} games to process");

            $games->each(function ($game) use ($service) {
                $this->info("Processing {$game->awayTeam->school} @ {$game->homeTeam->school}");
                $service->processGame($game);
            });

            $this->info('Spread calculation completed successfully');
            return 0;
        } catch (Exception $e) {
            $this->error('Error calculating spreads: ' . $e->getMessage());
            return 1;
        }
    }
}
