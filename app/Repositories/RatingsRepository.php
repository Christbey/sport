<?php

namespace App\Repositories;

use App\DataTransferObjects\GameRatingsDTO;
use App\Models\CollegeFootball\{AdvancedGameStat, CollegeFootballElo, CollegeFootballFpi, CollegeFootballGame, Sagarin};
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Cache, Log};

class RatingsRepository
{
    private const CACHE_TTL = 3600; // 1 hour cache
    private const CACHE_PREFIX = 'college_football_ratings_';

    public function getBulkRatings(Collection $games): Collection
    {
        return $games->map(fn(CollegeFootballGame $game) => [
            'game_id' => $game->id,
            'ratings' => Cache::remember(self::CACHE_PREFIX . "game_{$game->id}", self::CACHE_TTL, fn() => $this->fetchRatings($game))
        ]);
    }

    private function fetchRatings(CollegeFootballGame $game): GameRatingsDTO
    {
        try {
            $homeId = $game->homeTeam->id;
            $awayId = $game->awayTeam->id;
            $season = $game->season;

            Log::info('Fetching ratings for game', [
                'game_id' => $game->id,
                'home_team' => $game->homeTeam->school,
                'away_team' => $game->awayTeam->school,
                'home_id' => $homeId,
                'away_id' => $awayId,
                'season' => $season
            ]);

            $elo = $this->fetchEloRatings($homeId, $awayId, $season);
            $fpi = $this->fetchFpiRatings($homeId, $awayId, $season);
            $sagarin = $this->fetchSagarinRatings($homeId, $awayId);
            $advancedStats = $this->fetchAdvancedStats($homeId, $awayId);
            $strengthOfSchedule = $this->fetchStrengthOfSchedule($homeId, $awayId, $season);

            Log::info('Fetched ratings', [
                'elo' => $elo,
                'fpi' => $fpi,
                'sagarin' => $sagarin,
                'advanced_stats_exists' => [
                    'home' => isset($advancedStats['home']),
                    'away' => isset($advancedStats['away'])
                ],
                'sos' => $strengthOfSchedule
            ]);

            return new GameRatingsDTO([
                'elo' => $elo,
                'fpi' => $fpi,
                'sagarin' => $sagarin,
                'advancedStats' => $advancedStats,
                'strengthOfSchedule' => $strengthOfSchedule,
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching ratings', [
                'game_id' => $game->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function fetchEloRatings(int $homeId, int $awayId, int $season): array
    {
        $ratings = CollegeFootballElo::whereIn('team_id', [$homeId, $awayId])
            ->where('year', $season)
            ->get()
            ->keyBy('team_id');

        return [
            'home' => $ratings->get($homeId)?->elo,
            'away' => $ratings->get($awayId)?->elo
        ];
    }

    private function fetchFpiRatings(int $homeId, int $awayId, int $season): array
    {
        $ratings = CollegeFootballFpi::whereIn('team_id', [$homeId, $awayId])
            ->where('year', $season)
            ->get()
            ->keyBy('team_id');

        return [
            'home' => $ratings->get($homeId)?->fpi,
            'away' => $ratings->get($awayId)?->fpi,
            'home_special_teams' => $ratings->get($homeId)?->special_teams,
            'away_special_teams' => $ratings->get($awayId)?->special_teams
        ];
    }

    private function fetchSagarinRatings(int $homeId, int $awayId): array
    {
        $ratings = Sagarin::whereIn('id', [$homeId, $awayId])
            ->get()
            ->keyBy('id');

        return [
            'home' => $ratings->get($homeId)?->rating,
            'away' => $ratings->get($awayId)?->rating
        ];
    }

    private function fetchAdvancedStats(int $homeId, int $awayId): array
    {
        $stats = AdvancedGameStat::whereIn('team_id', [$homeId, $awayId])
            ->get()
            ->keyBy('team_id');

        return [
            'home' => $stats->get($homeId),
            'away' => $stats->get($awayId)
        ];
    }

    private function fetchStrengthOfSchedule(int $homeId, int $awayId, int $season): array
    {
        $ratings = CollegeFootballFpi::whereIn('team_id', [$homeId, $awayId])
            ->where('year', $season)
            ->get()
            ->keyBy('team_id');

        return [
            'home' => $ratings->get($homeId)?->strength_of_schedule,
            'away' => $ratings->get($awayId)?->strength_of_schedule
        ];
    }

    public function clearAllCache(): void
    {
        Cache::flush();
    }

    public function refreshGameRatings(CollegeFootballGame $game): GameRatingsDTO
    {
        $this->clearGameCache($game->id);
        return $this->getRatings($game);
    }

    public function clearGameCache(int $gameId): void
    {
        Cache::forget(self::CACHE_PREFIX . "game_{$gameId}");
    }

    public function getRatings(CollegeFootballGame $game): GameRatingsDTO
    {
        $cacheKey = self::CACHE_PREFIX . "game_{$game->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, fn() => $this->fetchRatings($game));
    }

    public function hasRatings(CollegeFootballGame $game): bool
    {
        try {
            $ratings = $this->getRatings($game);

            return !is_null($ratings->elo['home']) &&
                !is_null($ratings->elo['away']) &&
                !is_null($ratings->fpi['home']) &&
                !is_null($ratings->fpi['away']) &&
                !is_null($ratings->sagarin['home']) &&
                !is_null($ratings->sagarin['away']) &&
                !is_null($ratings->advancedStats['home']) &&
                !is_null($ratings->advancedStats['away']);
        } catch (Exception $e) {
            Log::warning('Error checking ratings existence', [
                'game_id' => $game->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}