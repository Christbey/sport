<?php

namespace App\Console\Commands\Nfl;

use AllowDynamicProperties;
use App\Helpers\NflCommandHelper;
use App\Models\Nfl\NflTeamSchedule;
use App\Notifications\DiscordCommandCompletionNotification;
use App\Services\NflScheduleService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

#[AllowDynamicProperties] class FetchNFLTeamSchedule extends Command
{
    private const CACHE_KEY = 'nfl_schedule_fetch_in_progress';
    private const CACHE_DURATION = 3600;

    protected $signature = 'nfl:fetch-team-schedule {--week= : Specific week number} {--force : Force refresh}';
    protected $description = 'Fetch and store the NFL team schedule for the current week';

    public function __construct(NflScheduleService $scheduleService)
    {
        parent::__construct();
        $this->scheduleService = $scheduleService;
    }

    public function handle()
    {
        if (!$this->checkAndSetLock()) {
            $this->error('Another fetch is in progress. Use --force to override.');
            return 1;
        }

        try {
            $season = config('nfl.seasonYear');
            $seasonType = config('nfl.seasonType');
            $weekNumber = $this->option('week') ?? NflCommandHelper::getCurrentWeek();
            $this->validateParams($season, $seasonType, $weekNumber);

            DB::beginTransaction();
            $this->fetchAndSaveSchedule($season, $weekNumber, $seasonType);
            DB::commit();
            $this->sendNotification("Schedule fetch completed for Week $weekNumber", 'success');
        } catch (Exception $e) {
            DB::rollBack();
            $this->sendNotification($e->getMessage(), 'error');
            $this->error('Error: ' . $e->getMessage());
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

    private function validateParams($season, $seasonType, $weekNumber): void
    {
        $this->validateSeason($season);
        $this->validateSeasonType($seasonType);
        $this->validateWeekNumber($weekNumber);
    }

    private function validateSeason($season): void
    {
        if (!is_numeric($season) || strlen($season) !== 4) {
            throw new Exception("Invalid season year: $season");
        }
    }

    private function validateSeasonType($seasonType): void
    {
        if (!in_array($seasonType, [1, 2, 3])) {
            throw new Exception("Invalid season type: $seasonType");
        }
    }

    private function validateWeekNumber($weekNumber): void
    {
        if ($weekNumber < 1 || $weekNumber > 22) {
            throw new Exception("Invalid week number: $weekNumber");
        }
    }

    private function fetchAndSaveSchedule($season, $weekNumber, $seasonType): void
    {
        if ($this->option('force')) {
            $this->clearExistingData($weekNumber);
        }
        $this->scheduleService->updateScheduleForWeek($season, $weekNumber, $seasonType);
        $this->info('Schedule fetch completed successfully');
    }

    private function clearExistingData($weekNumber): void
    {
        $espnIds = NflTeamSchedule::where('game_week', "Week $weekNumber")->pluck('espn_event_id')->filter();
        NflTeamSchedule::whereIn('espn_event_id', $espnIds)->delete();
        $this->info('Cleared existing schedule data for week ' . $weekNumber);
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