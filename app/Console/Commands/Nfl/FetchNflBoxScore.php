<?php

namespace App\Console\Commands\Nfl;

use App\Events\BoxScoreFetched;
use App\Models\Nfl\NflTeamSchedule;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchNflBoxScore extends Command
{
    protected $signature = 'nfl:fetch-boxscore {gameID?} {--all} {--week=}';
    protected $description = 'Fetch NFL box score for a specific game or for games in the specified week';

    public function handle()
    {
        try {
            $gameIDs = $this->getGameIDs();

            if ($gameIDs->isEmpty()) {
                $this->info('No games found to fetch box scores.');
                return;
            }

            $gameIDs->each(fn($gameID) => $this->fetchAndDispatchBoxScore($gameID));
            $this->info('All NFL box score fetch events dispatched successfully.');
        } catch (Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
        }
    }

    private function getGameIDs(): Collection
    {
        if ($this->option('all')) {
            return $this->getGamesBeforeToday();
        }

        if ($gameID = $this->argument('gameID')) {
            return collect([$gameID]);
        }

        if ($week = $this->option('week')) {
            return $this->getGamesForSpecifiedWeek($week);
        }

        return $this->getGamesForCurrentWeek();
    }

    private function getGamesBeforeToday(): Collection
    {
        return NflTeamSchedule::where('game_date', '<', Carbon::today())
            ->whereDoesntHave('boxScore')
            ->pluck('game_id');
    }

    private function getGamesForSpecifiedWeek(int $weekNumber): Collection
    {
        $weekConfig = config("nfl.weeks.{$weekNumber}");

        if (!$weekConfig) {
            $this->error("Invalid week number provided: {$weekNumber}");
            return collect();
        }

        return NflTeamSchedule::whereBetween('game_date', [$weekConfig['start'], $weekConfig['end']])->pluck('game_id');
    }

    private function getGamesForCurrentWeek(): Collection
    {
        $currentWeek = collect(config('nfl.weeks'))->first(fn($week) => Carbon::parse($week['start'])->lte(now()) && Carbon::parse($week['end'])->gte(now()));

        if (!$currentWeek) {
            $this->error('No matching week found for the current date.');
            return collect();
        }

        return NflTeamSchedule::whereBetween('game_date', [$currentWeek['start'], $currentWeek['end']])->pluck('game_id');
    }

    private function fetchAndDispatchBoxScore(string $gameID): void
    {
        try {
            Log::info("Fetching box score for game: {$gameID}");

            $response = Http::get(route('nfl.boxscore'), ['gameID' => $gameID]);

            if ($response->successful()) {
                event(new BoxScoreFetched($gameID, $response->json()));
                Log::info("Box score fetched and event dispatched for game: {$gameID}");
                $this->info("Box score fetched and event dispatched for game: {$gameID}");
            } else {
                $this->handleFailedRequest($gameID, $response);
            }
        } catch (Exception $e) {
            Log::error("Error fetching box score for game {$gameID}: " . $e->getMessage());
            $this->error("Error fetching box score for game {$gameID}: " . $e->getMessage());
        }
    }

    private function handleFailedRequest(string $gameID, $response): void
    {
        Log::error("Failed to fetch box score for game {$gameID}", [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
        $this->error("Failed to fetch box score for game {$gameID}");
    }
}
