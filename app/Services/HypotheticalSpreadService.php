<?php

namespace App\Services;

use App\DataTransferObjects\GameRatingsDTO;
use App\Models\CollegeFootball\{AdvancedGameStat,
    CollegeFootballElo,
    CollegeFootballFpi,
    CollegeFootballGame,
    CollegeFootballHypothetical,
    Sagarin};
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class HypotheticalSpreadService
{
    // Constants for spread calculations
    private const CONFERENCE_MULTIPLIER = 1.4;
    private const ELO_DIVISOR = 40;
    private const FPI_DIVISOR = 1.4;
    private const SAGARIN_DIVISOR = 1.1;
    private const ADVANCED_STATS_MULTIPLIER = 1.6;
    private const PPA_MULTIPLIER = 0.01;

    /**
     * Fetch games for current week and season.
     */
    public function fetchRelevantGames(): Collection
    {
        $week = $this->determineCurrentWeek();

        if (!$week) {
            Log::info('No valid week found based on the current date.');
            return $this->fetchRelevantGames();
        }

        return CollegeFootballGame::query()
            ->where('home_division', 'fbs')
            ->where('away_division', 'fbs')
            ->where('week', $week)
            ->where('season', config('college_football.season'))
            ->where('start_date', '>=', Carbon::today())
            ->with(['homeTeam', 'awayTeam'])
            ->get();
    }

    /**
     * Determine current week from config.
     */
    private function determineCurrentWeek(): ?int
    {
        $today = Carbon::today();

        return collect(config('college_football.weeks'))
            ->filter(function ($dates, $weekNumber) use ($today) {
                return $today->between(
                    Carbon::parse($dates['start']),
                    Carbon::parse($dates['end'])
                );
            })
            ->keys()
            ->first();
    }

    /**
     * Process game and calculate spread.
     */
    public function processGame(CollegeFootballGame $game): void
    {
        if (!$this->validateTeams($game)) {
            return;
        }

        $ratings = $this->fetchRatingsAndStats($game);

        if (!$this->ratingsAreValid($ratings)) {
            Log::warning("Incomplete ratings for game ID {$game->id} between {$game->homeTeam->school} and {$game->awayTeam->school}.");
            return;
        }

        $multiplier = $this->getConferenceMultiplier($game->homeTeam, $game->awayTeam);
        $spread = $this->calculateHypotheticalSpread($ratings, $multiplier, $game->id);

        $this->storeHypotheticalSpread($game, $spread, $ratings);
    }

    /**
     * Validate teams exist.
     */
    private function validateTeams(CollegeFootballGame $game): bool
    {
        if (!$game->homeTeam || !$game->awayTeam) {
            Log::warning("Missing team data for game ID {$game->id}.");
            return false;
        }
        return true;
    }

    /**
     * Fetch all ratings and stats in parallel using eager loading.
     */
    private function fetchRatingsAndStats(CollegeFootballGame $game): GameRatingsDTO
    {
        $season = $game->season;
        $homeId = $game->homeTeam->id;
        $awayId = $game->awayTeam->id;

        // Eager load all ratings in a single query per model
        $elos = CollegeFootballElo::whereIn('team_id', [$homeId, $awayId])
            ->where('year', $season)
            ->get()
            ->keyBy('team_id');

        $fpis = CollegeFootballFpi::whereIn('team_id', [$homeId, $awayId])
            ->where('year', $season)
            ->get()
            ->keyBy('team_id');

        $sagarins = Sagarin::whereIn('id', [$homeId, $awayId])
            ->get()
            ->keyBy('id');

        $advancedStats = AdvancedGameStat::whereIn('team_id', [$homeId, $awayId])
            ->get()
            ->keyBy('team_id');

        return new GameRatingsDTO([
            'elo' => [
                'home' => $elos[$homeId]->elo ?? null,
                'away' => $elos[$awayId]->elo ?? null,
            ],
            'fpi' => [
                'home' => $fpis[$homeId]->fpi ?? null,
                'away' => $fpis[$awayId]->fpi ?? null,
                'home_special_teams' => $fpis[$homeId]->special_teams ?? null,
                'away_special_teams' => $fpis[$awayId]->special_teams ?? null,
            ],
            'sagarin' => [
                'home' => $sagarins[$homeId]->rating ?? null,
                'away' => $sagarins[$awayId]->rating ?? null,
            ],
            'advancedStats' => [
                'home' => $advancedStats[$homeId] ?? null,
                'away' => $advancedStats[$awayId] ?? null,
            ],
            'strengthOfSchedule' => [
                'home' => $fpis[$homeId]->strength_of_schedule ?? null,
                'away' => $fpis[$awayId]->strength_of_schedule ?? null,
            ],
        ]);
    }

    /**
     * Validate all required ratings exist.
     */
    private function ratingsAreValid(GameRatingsDTO $ratings): bool
    {
        $requiredFields = array_merge(
            $ratings->elo,
            $ratings->fpi,
            $ratings->sagarin,
            $ratings->strengthOfSchedule
        );

        return !in_array(null, $requiredFields, true)
            && $ratings->advancedStats['home']
            && $ratings->advancedStats['away'];
    }

    /**
     * Calculate conference multiplier.
     */
    private function getConferenceMultiplier($homeTeam, $awayTeam): float
    {
        return ($homeTeam->conference === $awayTeam->conference)
            ? self::CONFERENCE_MULTIPLIER
            : 1;
    }

    /**
     * Calculate hypothetical spread using all components.
     */
    private function calculateHypotheticalSpread(GameRatingsDTO $ratings, float $multiplier, int $gameId): float
    {
        $spreadComponents = [
            $this->calculateFpiSpread($ratings->fpi),
            $this->calculateEloSpread($ratings->elo),
            $this->calculateSagarinSpread($ratings->sagarin),
            $this->calculateAdvancedSpreads($ratings->advancedStats)
        ];

        $spread = (array_sum($spreadComponents) / 5) * $multiplier;

        Log::info("Calculated spread after applying multiplier for Game ID {$gameId}: {$spread}");

        return round($spread, 2);
    }

    /**
     * Calculate FPI spread component.
     */
    private function calculateFpiSpread(array $fpi): float
    {
        return ($fpi['home'] - $fpi['away']) / self::FPI_DIVISOR;
    }

    /**
     * Calculate ELO spread component.
     */
    private function calculateEloSpread(array $elo): float
    {
        return ($elo['home'] - $elo['away']) / self::ELO_DIVISOR;
    }

    /**
     * Calculate Sagarin spread component.
     */
    private function calculateSagarinSpread(array $sagarin): float
    {
        return ($sagarin['home'] - $sagarin['away']) / self::SAGARIN_DIVISOR;
    }

    /**
     * Calculate advanced stats spread components.
     */
    private function calculateAdvancedSpreads(array $stats): float
    {
        $home = $stats['home'];
        $away = $stats['away'];

        $spreadComponents = [
            ($home->offense_ppa - $away->defense_ppa + $away->offense_ppa - $home->defense_ppa) * self::ADVANCED_STATS_MULTIPLIER,
            ($home->offense_success_rate - $away->defense_success_rate + $away->offense_success_rate - $home->defense_success_rate) * self::ADVANCED_STATS_MULTIPLIER,
            ($home->offense_explosiveness - $away->defense_explosiveness + $away->offense_explosiveness - $home->defense_explosiveness) * self::ADVANCED_STATS_MULTIPLIER,
            ($home->offense_rushing_ppa - $away->defense_rushing_ppa + $away->offense_rushing_ppa - $home->defense_rushing_ppa) * self::PPA_MULTIPLIER,
            ($home->offense_passing_ppa - $away->defense_passing_ppa + $away->offense_passing_ppa - $home->defense_passing_ppa) * self::PPA_MULTIPLIER,
            ($home->offense_line_yards - $away->defense_line_yards + $away->offense_line_yards - $home->defense_line_yards) * self::PPA_MULTIPLIER
        ];

        return array_sum($spreadComponents);
    }

    /**
     * Store calculated spread and related data.
     */
    private function storeHypotheticalSpread(CollegeFootballGame $game, float $spread, GameRatingsDTO $ratings): void
    {
        CollegeFootballHypothetical::updateOrCreate(
            ['game_id' => $game->id],
            [
                'week' => $game->week,
                'home_team_id' => $game->homeTeam->id,
                'away_team_id' => $game->awayTeam->id,
                'home_team_school' => $game->homeTeam->school,
                'away_team_school' => $game->awayTeam->school,
                'home_elo' => $ratings->elo['home'],
                'away_elo' => $ratings->elo['away'],
                'home_fpi' => $ratings->fpi['home'],
                'away_fpi' => $ratings->fpi['away'],
                'home_sagarin' => $ratings->sagarin['home'],
                'away_sagarin' => $ratings->sagarin['away'],
                'home_sos' => $ratings->strengthOfSchedule['home'],
                'away_sos' => $ratings->strengthOfSchedule['away'],
                'home_special_teams' => $ratings->fpi['home_special_teams'],
                'away_special_teams' => $ratings->fpi['away_special_teams'],
                'hypothetical_spread' => $spread,
            ]
        );

        Log::info("Hypothetical Spread for {$game->awayTeam->school} @ {$game->homeTeam->school}: $spread");
    }
}