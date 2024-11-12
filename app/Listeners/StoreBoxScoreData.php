<?php

namespace App\Listeners;

use App\Events\BoxScoreFetched;
use App\Models\Nfl\NflBoxScore;
use App\Notifications\DiscordCommandCompletionNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class StoreBoxScoreData
{
    /**
     * Handle the event.
     *
     * @param BoxScoreFetched $event
     * @return void
     */
    public function handle(BoxScoreFetched $event)
    {
        $gameData = $event->boxScoreData['body'] ?? [];

        if (empty($gameData) || !isset($gameData['gameID'])) {
            Log::warning('Invalid game data received: "gameID" is missing.');
            return;
        }

        $gameID = $gameData['gameID'];

        // Retrieve the existing box score to compare game status
        $existingBoxScore = NflBoxScore::where('game_id', $gameID)->first();

        $oldGameStatus = $existingBoxScore->game_status ?? null;
        $newGameStatus = $gameData['gameStatus'] ?? null;

        // Store or update the box score
        $boxScore = NflBoxScore::updateOrCreate(
            ['game_id' => $gameID],
            [
                'home_team' => $gameData['home'] ?? null,
                'away_team' => $gameData['away'] ?? null,
                'home_points' => $gameData['homePts'] ?? 0,
                'away_points' => $gameData['awayPts'] ?? 0,
                'game_date' => $gameData['gameDate'] ?? null,
                'location' => $gameData['gameLocation'] ?? null,
                'home_line_score' => $gameData['lineScore']['home'] ?? null,
                'away_line_score' => $gameData['lineScore']['away'] ?? null,
                'away_result' => $gameData['awayResult'] ?? null,
                'home_result' => $gameData['homeResult'] ?? null,
                'game_status' => $newGameStatus,
            ]
        );

        Log::info("NFL Box Score for game {$gameID} stored successfully.");

        // Prepare variables for notifications
        $homeTeam = $boxScore->home_team ?? 'Home Team';
        $awayTeam = $boxScore->away_team ?? 'Away Team';
        $gameTime = $boxScore->game_date ?? 'Unknown Time';
        $location = $boxScore->location ?? 'Unknown Location';

        // Define game ended statuses
        $gameEndedStatuses = ['Completed', 'Final', 'Game Over', 'Final/OT'];

        // Send notification when game status 'Live — In Progress'
        if ($newGameStatus === 'Live - In Progress' && $oldGameStatus == 'Live - In Progress') {
            // Game has just started
            $message = "The game between **{$awayTeam}** and **{$homeTeam}** is now live!\n";
            $message .= "Time: {$gameTime}\n";
            $message .= "Location: {$location}\n";
            $message .= "Home: {$boxScore->home_points} - Away: {$boxScore->away_points}";

            // Send the notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($message, 'success'));

            Log::info("Notification sent: Game {$gameID} is now live.");
        }

        // Send notification when game status changes from 'Live - In Progress' to a completed status
        if (in_array($newGameStatus, $gameEndedStatuses) && $oldGameStatus === 'Live - In Progress') {
            // Game has just ended
            $message = "The game between **{$awayTeam}** and **{$homeTeam}** has ended.\n";
            $message .= "Final Score:\n";
            $message .= "{$awayTeam}: {$boxScore->away_points}\n";
            $message .= "{$homeTeam}: {$boxScore->home_points}";

            // Send the notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($message, 'success'));

            Log::info("Notification sent: Game {$gameID} has ended.");
        }
    }
}