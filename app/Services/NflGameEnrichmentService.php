<?php

namespace App\Services;

use App\Repositories\Nfl\Interfaces\NflBettingOddsRepositoryInterface;
use Illuminate\Support\{Carbon, Collection};

readonly class NflGameEnrichmentService
{
    public function __construct(
        private NflBettingOddsRepositoryInterface $oddsRepo
    )
    {
    }

    public function sortAndGroupPredictions(Collection $predictions): Collection
    {
        return $predictions
            ->groupBy(fn($prediction) => Carbon::parse($prediction->game_date)->format('Y-m-d'))
            ->sortKeys()
            ->flatten(1);
    }

    public function enrichGame($game, int $teamId)
    {
        $odds = $this->oddsRepo->findByGameId($game->game_id);
        if ($odds) {
            $game = $this->enrichWithBettingData($game, $odds);
        }
        $game->marginOfVictory = $this->calculateMarginOfVictory($game, $game->home_team_id === $teamId);
        return $game;
    }

    public function enrichWithBettingData($schedule, $odds)
    {
        if (!isset($schedule->home_pts, $schedule->away_pts)) {
            return $this->enrichIncompleteGame($schedule, $odds);
        }

        return $this->enrichCompletedGame($schedule, $odds);
    }

    private function enrichIncompleteGame($schedule, $odds): object
    {
        $schedule->totalPoints = null;
        $schedule->overUnderResult = 'N/A';
        $schedule->totalOver = $odds->total_over ?? null;
        $schedule->totalUnder = $odds->total_under ?? null;
        return $schedule;
    }

    private function enrichCompletedGame($schedule, $odds): object
    {
        $totalPoints = $schedule->home_pts + $schedule->away_pts;
        $schedule->totalPoints = $totalPoints;
        $schedule->overUnderResult = $this->determineOverUnderResult($totalPoints, $odds->total_over);
        $schedule->totalOver = $odds->total_over;
        $schedule->totalUnder = $odds->total_under;
        return $schedule;
    }

    private function determineOverUnderResult(int $total, float $line): string
    {
        return match (true) {
            $total > $line => 'Over',
            $total < $line => 'Under',
            default => 'Push'
        };
    }

    private function calculateMarginOfVictory($game, bool $isHomeTeam): ?int
    {
        if (!isset($game->home_pts, $game->away_pts)) return null;
        return $isHomeTeam ? $game->home_pts - $game->away_pts : $game->away_pts - $game->home_pts;
    }
}