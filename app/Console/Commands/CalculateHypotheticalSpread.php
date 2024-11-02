<?php

namespace App\Console\Commands;

use App\Notifications\DiscordCommandCompletionNotification;
use App\Services\HypotheticalSpreadService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

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
            try {
                $this->spreadService->processGame($game);
                // Send success notification
                Notification::route('discord', config('services.discord.channel_id'))
                    ->notify(new DiscordCommandCompletionNotification('', 'success'));

            } catch (Exception $e) {
                // Send failure notification
                Notification::route('discord', config('services.discord.channel_id'))
                    ->notify(new DiscordCommandCompletionNotification($e->getMessage(), 'error'));

            }
        }
    }
}
