<?php

namespace App\Repositories\Nfl\Interfaces;

use App\Models\Nfl\NflBettingOdds;
use Illuminate\Support\Collection;

/**
 * Interface NflBettingOddsRepositoryInterface
 *
 * Defines the contract for the NflBettingOddsRepository.
 */
interface NflBettingOddsRepositoryInterface
{
    /**
     * Retrieve NFL betting odds for specified event IDs.
     *
     * @param Collection $eventIds A collection of event IDs.
     * @return Collection A collection of NflBettingOdds models keyed by event_id.
     */
    public function findByEventIds(Collection $eventIds): Collection;

    /**
     * Retrieve NFL betting odds for a specific game ID.
     *
     * @param string $gameId The game ID to retrieve betting odds for.
     * @return NflBettingOdds|null The NflBettingOdds model or null if not found.
     */
    public function findByGameId(string $gameId): ?NflBettingOdds;

    /**
     * Retrieve NFL betting odds within a specific date range.
     *
     * @param string $startDate The start date (YYYY-MM-DD).
     * @param string $endDate The end date (YYYY-MM-DD).
     * @return Collection A collection of NflBettingOdds models within the date range.
     */
    public function findByDateRange(string $startDate, string $endDate): Collection;

    /**
     * Retrieve NFL betting odds for a specific team, optionally within a date range.
     *
     * @param string $teamId The team ID to retrieve betting odds for.
     * @param string|null $startDate The start date (YYYY-MM-DD) for the date range filter. Optional.
     * @param string|null $endDate The end date (YYYY-MM-DD) for the date range filter. Optional.
     * @return Collection A collection of NflBettingOdds models for the team.
     */
    public function findByTeam(string $teamId, ?string $startDate = null, ?string $endDate = null): Collection;

    /**
     * Retrieve NFL betting odds for a specific game date.
     *
     * @param string $gameDate The game date (YYYY-MM-DD) to retrieve betting odds for.
     * @return Collection A collection of NflBettingOdds models for the specified date.
     */
    public function findByGameDate(string $gameDate): Collection;

    /**
     * Retrieve NFL betting odds for a specific team and season year.
     *
     * @param string $teamId The team ID to retrieve betting odds for.
     * @param int $season The season year (e.g., 2023).
     * @return Collection A collection of NflBettingOdds models for the team and season.
     */
    public function findByTeamAndSeason(string $teamId, int $season): Collection;

    /**
     * Retrieve NFL betting odds for a specific week, optionally filtering by spread and total.
     *
     * @param int $week The week number to retrieve betting odds for.
     * @param float|null $spread Optional spread value to filter betting odds.
     * @param float|null $total Optional total value to filter betting odds.
     * @return Collection A collection of NflBettingOdds models for the specified week.
     */
    public function getOddsByWeek(int $week, ?float $spread = null, ?float $total = null): Collection;

}