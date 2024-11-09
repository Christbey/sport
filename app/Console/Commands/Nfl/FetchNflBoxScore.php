<?php

namespace App\Console\Commands\Nfl;

use App\Jobs\Nfl\FetchNflBoxScoreJob;
use App\Models\Nfl\NflTeamSchedule;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FetchNflBoxScore extends Command
{
    protected $signature = 'nfl:fetch-boxscore {gameID?} {--all} {--week=}';
    protected $description = 'Fetch NFL box score for a specific game or for games in the specified week';

    public function handle()
    {
        $gameID = $this->argument('gameID');
        $weekOption = $this->option('week');

        if ($this->option('all')) {
            $this->fetchBoxScoresForGames($this->getGamesBeforeToday(), 'all games before today');
        } elseif ($gameID) {
            $this->dispatchJob($gameID, "specific game: {$gameID}");
        } elseif ($weekOption) {
            $this->fetchBoxScoresForGames($this->getGamesForSpecifiedWeek($weekOption), "Week {$weekOption}");
        } else {
            $this->fetchBoxScoresForGames($this->getGamesForCurrentWeek(), 'current week');
        }
    }

    protected function fetchBoxScoresForGames($games, $context)
    {
        if ($games->isEmpty()) {
            $this->info("No games found for {$context}.");
            return;
        }

        foreach ($games as $gameID) {
            $this->dispatchJob($gameID, $context);
        }
    }

    protected function dispatchJob($gameID, $context)
    {
        dispatch(new FetchNflBoxScoreJob($gameID));
        $this->info("Job dispatched for game: {$gameID} in {$context}");
    }

    protected function getGamesBeforeToday(): Collection
    {
        $today = Carbon::today();

        return NflTeamSchedule::where('game_date', '<', $today)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('nfl_box_scores')
                    ->whereColumn('nfl_box_scores.game_id', 'nfl_team_schedules.game_id');
            })
            ->pluck('game_id');
    }

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

    protected function getCurrentWeekConfig($date)
    {
        return collect(config('nfl.weeks'))->first(function ($week) use ($date) {
            return Carbon::parse($week['start'])->lte($date) && Carbon::parse($week['end'])->gte($date);
        });
    }
}
