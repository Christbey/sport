<?php

namespace App\Jobs\CollegeFootball;

use App\Events\{GameCalculationsStarted, GameResultProcessed, WeeklyCalculationsCompleted};
use App\Models\CollegeFootball\CollegeFootballGame;
use App\Notifications\DiscordCommandCompletionNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\{Log, Notification};

class CalculateGameDifferences implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        try {
            $now = Carbon::now();
            $dateRange = [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek()
            ];

            // Dispatch start event
            GameCalculationsStarted::dispatch($dateRange);

            $totalProcessedGames = 0;
            $correctPredictions = 0;
            $gamesData = collect();

            $games = CollegeFootballGame::with(['hypothetical', 'homeTeam', 'awayTeam'])
                ->whereHas('hypothetical')
                ->where('completed', '1')
                ->whereBetween('start_date', array_values($dateRange))
                ->get();

            foreach ($games as $game) {
                if (is_null($game->away_points) || is_null($game->home_points)) {
                    continue;
                }

                $actualPointDifference = $game->home_points - $game->away_points;
                $hypotheticalSpread = $game->hypothetical->hypothetical_spread;
                $wasCorrect = ($actualPointDifference >= $hypotheticalSpread);

                // Dispatch individual game process event
                GameResultProcessed::dispatch(
                    $game,
                    $wasCorrect,
                    $hypotheticalSpread,
                    $actualPointDifference
                );

                $totalProcessedGames++;
                if ($wasCorrect) {
                    $correctPredictions++;
                }

                $gamesData->push([
                    'id' => $game->id,
                    'homeTeam' => $game->homeTeam->school ?? 'Home Team',
                    'awayTeam' => $game->awayTeam->school ?? 'Away Team',
                    'homePoints' => $game->home_points,
                    'awayPoints' => $game->away_points,
                    'spreadOpen' => $game->spread_open,
                    'result' => $wasCorrect ? 1 : 0
                ]);
            }

            if ($gamesData->isNotEmpty()) {
                $percentage = number_format(($correctPredictions / $totalProcessedGames) * 100, 2);

                // Dispatch completion event
                WeeklyCalculationsCompleted::dispatch(
                    $gamesData->toArray(),
                    $totalProcessedGames,
                    $correctPredictions,
                    $percentage
                );
            }

            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification('Processed games for current week', 'success'));

        } catch (Exception $e) {
            Log::error('Error processing games: ' . $e->getMessage());
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($e->getMessage(), 'error'));
        }
    }
}