<?php

namespace App\Console\Commands\Nfl;

use App\Events\BoxScoreFetched;
use App\Models\Nfl\NflTeamSchedule;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchNflBoxScore extends Command
{
    protected $signature = 'nfl:fetch-boxscore {gameID?} {--all} {--week=}';
    protected $description = 'Fetch NFL box score for a specific game or for games in the specified week';

    public function handle()
    {
        try {
            $gameID = $this->argument('gameID');
            $weekOption = $this->option('week');

            if ($this->option('all')) {
                $this->fetchBoxScoresForGames($this->getGamesBeforeToday(), 'all games before today');
            } elseif ($gameID) {
                $this->fetchAndDispatchBoxScore($gameID, "specific game: {$gameID}");
            } elseif ($weekOption) {
                $this->fetchBoxScoresForGames($this->getGamesForSpecifiedWeek($weekOption), "Week {$weekOption}");
            } else {
                $this->fetchBoxScoresForGames($this->getGamesForCurrentWeek(), 'current week');
            }

            $this->info('All NFL box score fetch events dispatched successfully.');

        } catch (Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            // Optionally, handle exception f urther or log it
        }
    }

    /**
     * Fetch and dispatch box scores for a collection of game IDs.
     *
     * @param Collection $games
     * @param string $context
     * @return void
     */
    protected function fetchBoxScoresForGames($games, $context)
    {
        if ($games->isEmpty()) {
            $this->info("No games found for {$context}.");
            return;
        }

        foreach ($games as $gameID) {
            $this->fetchAndDispatchBoxScore($gameID, $context);
        }
    }

    /**
     * Fetch the box score for a single game and dispatch the BoxScoreFetched event.
     *
     * @param string $gameID
     * @param string $context
     * @return void
     */
    protected function fetchAndDispatchBoxScore($gameID, $context)
    {
        try {
            Log::info("Fetching box score for game: {$gameID} in {$context}");

            // Make the HTTP GET request to fetch the box score
            $response = Http::get(route('nfl.boxscore'), ['gameID' => $gameID]);

            if ($response->successful()) {
                $data = $response->json();

                // Dispatch the BoxScoreFetched event with the fetched data
                event(new BoxScoreFetched($gameID, $data));

                Log::info("Box score fetched and BoxScoreFetched event dispatched for game: {$gameID}");
                $this->info("Box score fetched and event dispatched for game: {$gameID}");
            } else {
                Log::error("Failed to fetch box score for game {$gameID}", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                $this->error("Failed to fetch box score for game {$gameID}");
                // Optionally, dispatch a failure event here
            }
        } catch (Exception $e) {
            // Log the error
            Log::error("Error fetching box score for game {$gameID}: " . $e->getMessage());
            $this->error("Error fetching box score for game {$gameID}: " . $e->getMessage());
            // Optionally, dispatch a failure event here
        }
    }

    /**
     * Retrieve game IDs for all games before today that haven't been fetched yet.
     *
     * @return Collection
     */
    protected function getGamesBeforeToday(): Collection
    {
        $today = Carbon::today();

        return NflTeamSchedule::where('game_date', '<', $today)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('nfl_box_scores')
                    ->whereColumn('nfl_box_scores.game_id', 'nfl_team_schedules.game_id');
            })
            ->whereNotNull('game_id')
            ->where('game_id', '!=', '')
            ->pluck('game_id');
    }


    /**
     * Retrieve game IDs for games in the specified week.
     *
     * @param int $weekNumber
     * @return Collection
     */
    protected function getGamesForSpecifiedWeek($weekNumber): Collection
    {
        $weeks = config('nfl.weeks');
        $weekConfig = $weeks["Week {$weekNumber}"] ?? null;

        if (!$weekConfig) {
            $this->error("Invalid week number provided: {$weekNumber}");
            return collect();
        }

        return NflTeamSchedule::whereBetween('game_date', [$weekConfig['start'], $weekConfig['end']])
            ->pluck('game_id');
    }

    /**
     * Retrieve game IDs for games in the current week.
     *
     * @return Collection
     */
    protected function getGamesForCurrentWeek(): Collection
    {
        $currentDate = Carbon::now();
        $weekConfig = $this->getCurrentWeekConfig($currentDate);

        if (!$weekConfig) {
            $this->error('No matching week found for the current date.');
            return collect();
        }

        return NflTeamSchedule::whereBetween('game_date', [$weekConfig['start'], $weekConfig['end']])
            ->pluck('game_id');
    }

    /**
     * Get the configuration for the current week based on the provided date.
     *
     * @param Carbon $date
     * @return array|null
     */
    protected function getCurrentWeekConfig($date)
    {
        return collect(config('nfl.weeks'))->first(function ($week) use ($date) {
            return Carbon::parse($week['start'])->lte($date) && Carbon::parse($week['end'])->gte($date);
        });
    }
}
