<?php

namespace App\Console\Commands\Nfl;

use App\Events\Nfl\FetchNflEspnScheduleEvent;
use App\Helpers\NflCommandHelper;
use App\Models\Nfl\NflTeamSchedule;
use App\Notifications\DiscordCommandCompletionNotification;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class FetchNFLTeamSchedule extends Command
{
    private const CACHE_KEY = 'nfl_schedule_fetch_in_progress';
private const CACHE_DURATION = 3600;
    protected $signature = 'nfl:fetch-team-schedule 
        {--week= : Specific week number to fetch (defaults to current week)}
        {--force : Force refresh even if cache exists}';
        protected $description = 'Fetch and store the NFL team schedule for the current week'; // 1 hour

    public function handle()
    {
        try {
            if (!$this->checkAndSetLock()) {
                $this->error('Another schedule fetch is in progress. Use --force to override.');
                return 1;
            }

            // Initialize parameters
            $season = config('nfl.seasonYear');
            $seasonType = config('nfl.seasonType');
            $weekNumber = $this->option('week') ?? config('nfl.weekNumber') ?? NflCommandHelper::getCurrentWeek();

            $this->validateParameters($season, $seasonType, $weekNumber);

            // Start transaction
            DB::beginTransaction();

            try {
                $this->info("Fetching schedule for Week {$weekNumber}");

                // Clear existing schedule data for this week if force option is used
                if ($this->option('force')) {
                    NflTeamSchedule::where([
                        'season' => $season,
                        'game_week' => $weekNumber
                    ])->delete();

                    $this->info('Cleared existing schedule data for week ' . $weekNumber);
                }

                // Fetch ESPN schedule for the week
                $this->info('Fetching ESPN schedule...');
                event(new FetchNflEspnScheduleEvent($season, $seasonType, $weekNumber));

                DB::commit();

                $this->info('Schedule fetch completed successfully');

                // Send success notification
                $this->sendNotification("Schedule fetch completed for Week {$weekNumber}", 'success');

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            $this->sendNotification($e->getMessage(), 'error');
            $this->error('An error occurred: ' . $e->getMessage());
            Log::error('Schedule fetch failed: ' . $e->getMessage());
            return 1;
        } finally {
            $this->releaseLock();
        }

        return 0;
    }

    private function checkAndSetLock(): bool
    {
        if (Cache::get(self::CACHE_KEY) && !$this->option('force')) {
            return false;
        }

        Cache::put(self::CACHE_KEY, true, self::CACHE_DURATION);
        return true;
    }

    private function validateParameters($season, $seasonType, $weekNumber): void
    {
        if (!is_numeric($season) || strlen($season) !== 4) {
            throw new Exception("Invalid season year: $season");
        }

        if (!in_array($seasonType, [1, 2, 3])) { // 1=Pre, 2=Reg, 3=Post
            throw new Exception("Invalid season type: $seasonType");
        }

        if ($weekNumber < 1 || $weekNumber > 22) {
            throw new Exception("Invalid week number: $weekNumber");
        }
    }

    private function sendNotification(string $message, string $type): void
    {
        try {
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($message, $type));
        } catch (Exception $e) {
            Log::error('Failed to send Discord notification: ' . $e->getMessage());
        }
    }

    private function releaseLock(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}