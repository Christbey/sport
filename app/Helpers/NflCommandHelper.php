<?php

namespace App\Helpers;

use App\Notifications\DiscordCommandCompletionNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

class NflCommandHelper
{
    public static function getCurrentWeek(): ?int
    {
        $today = Carbon::today();
        $weeks = config('nfl.weeks');

        foreach ($weeks as $weekLabel => $dates) {
            $weekNumber = (int)filter_var($weekLabel, FILTER_SANITIZE_NUMBER_INT);
            $start = Carbon::parse($dates['start']);
            $end = Carbon::parse($dates['end']);

            if ($today->between($start, $end)) {
                return $weekNumber;
            }
        }

        // Return null if no matching week is found
        return null;
    }

    public static function sendNotification(string $message, string $type = 'success'): void
    {
        $discordWebhook = config('services.discord.channel_id');

        Notification::route('discord', $discordWebhook)
            ->notify(new DiscordCommandCompletionNotification($message, $type));
    }


}
