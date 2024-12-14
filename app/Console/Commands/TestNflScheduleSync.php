<?php

namespace App\Console\Commands;

use App\Models\Nfl\NflBoxScore;
use App\Services\NflScheduleService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{DB, Log};

class TestNflScheduleSync extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'nfl:test-sync 
                        {--season= : The NFL season year (defaults to current config)}
                        {--week= : The NFL week (defaults to current week)}
                        {--type= : The season type (1: Preseason, 2: Regular, 3: Postseason, defaults to Regular)}';
    /**
     * The console command description.
     */
    protected $description = 'Test NFL schedule sync for a specific week';

    /**
     * The schedule service instance.
     */
    private NflScheduleService $scheduleService;

    /**
     * Create a new command instance.
     */
    public function __construct(NflScheduleService $scheduleService)
    {
        parent::__construct();
        $this->scheduleService = $scheduleService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        try {
            // Use provided options or fallback to defaults
            $season = $this->option('season') ?? config('nfl.seasonYear');
            $type = $this->option('type') ?? '2'; // Default to regular season
            $week = $this->option('week') ?? $this->getCurrentWeek();

            $seasonType = $this->getSeasonType($type);

            $this->info("Starting sync for Season: $season, Week: $week, Type: $type");

            $this->syncSchedule($season, (int)$week, $type);
            $games = $this->fetchGames($season, (int)$week, $seasonType);
            $this->displayResults($games);
        } catch (Exception $e) {
            $this->error('Error during sync: ' . $e->getMessage());
            Log::error('Error during sync', ['exception' => $e]);
        }
    }

    /**
     * Determine the current NFL week based on today's date.
     */
    private function getCurrentWeek(): int
    {
        $today = now()->toDateString(); // Current date in 'Y-m-d' format
        $weeks = config('nfl.weeks'); // Fetch the weeks array

        foreach ($weeks as $week => $dates) {
            if ($today >= $dates['start'] && $today <= $dates['end']) {
                return (int)$week;
            }
        }

        throw new Exception("Unable to determine the current NFL week for today's date: $today");
    }

    /**
     * Get the correct season type string.
     *
     * @throws Exception
     */
    private function getSeasonType(string $type): string
    {
        return match ($type) {
            '1' => 'Preseason',
            '2' => 'Regular Season',
            '3' => 'Postseason',
            default => throw new Exception("Invalid season type: {$type}")
        };
    }

    /**
     * Sync the schedule for the given parameters.
     */
    private function syncSchedule(string $season, int $week, string $type): void
    {
        $this->info("\nStarting full sync...");
        $this->scheduleService->updateScheduleForWeek($season, $week, $type);
    }

    /**
     * Fetch games from the database.
     */
    private function fetchGames(string $season, int $week, string $seasonType): Collection
    {
        return DB::table('nfl_team_schedules')
            ->where('season', $season)
            ->where('game_week', $week)
            ->where('season_type', $seasonType)
            ->get();
    }

    /**
     * Display the results of the sync.
     */
    private function displayResults(Collection $games): void
    {
        $this->info("\nSync completed. Found {$games->count()} games.");

        if ($games->isEmpty()) {
            $this->warn('No games found for the specified week and type.');
            return;
        }

        foreach ($games as $game) {
            $this->displayGameInfo($game);
            $this->displayBoxScore($game->game_id);
        }
    }

    /**
     * Display information for a single game.
     */
    private function displayGameInfo(object $game): void
    {
        $this->info("\nGame ID: {$game->game_id}");
        $this->info("Teams: {$game->away_team} @ {$game->home_team}");
        $this->info("Score: {$game->away_pts} - {$game->home_pts}");
        $this->info("Status: {$game->game_status}");
    }

    /**
     * Display box score for a game.
     */
    private function displayBoxScore(string $gameId): void
    {
        $boxScore = NflBoxScore::where('game_id', $gameId)->first();

        if ($boxScore) {
            $this->info("Box Score - Home Points: {$boxScore->home_points}, Away Points: {$boxScore->away_points}");
        } else {
            $this->warn("No box score found for Game ID: {$gameId}");
        }
    }
}