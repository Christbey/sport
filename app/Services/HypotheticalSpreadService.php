<?php

namespace App\Services;

use App\Models\CollegeFootball\AdvancedGameStat;
use App\Models\CollegeFootball\CollegeFootballElo;
use App\Models\CollegeFootball\CollegeFootballFpi;
use App\Models\CollegeFootball\CollegeFootballGame;
use App\Models\CollegeFootball\CollegeFootballHypothetical;
use App\Models\CollegeFootball\Sagarin;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class HypotheticalSpreadService
{
    /**
     * Fetch the relevant games for the current week and season.
     */
    public function fetchRelevantGames()
    {
        $today = Carbon::today();
        $week = config('college_football.week');
        $season = config('college_football.season');

        return CollegeFootballGame::where('home_division', 'fbs')
            ->where('away_division', 'fbs')
            ->where('week', $week)
            ->where('season', $season)
            ->where('start_date', '>=', $today)
            ->with(['homeTeam', 'awayTeam'])
            ->get();
    }

    /**
     * Process the game and calculate the hypothetical spread.
     */
    public function processGame($game)
    {
        $homeTeam = $game->homeTeam;
        $awayTeam = $game->awayTeam;

        if (!$homeTeam || !$awayTeam) {
            Log::warning("Missing team data for game ID {$game->id}.");
            return;
        }

        // Fetch ratings and stats
        $ratings = $this->fetchRatingsAndStats($game, $homeTeam, $awayTeam);

        // Validate the ratings
        if (!$this->ratingsAreValid($ratings)) {
            Log::warning("Incomplete ratings for game ID {$game->id} between {$homeTeam->school} and {$awayTeam->school}.");
            return;
        }

        // Determine if teams are in the same conference
        $multiplier = $this->getConferenceMultiplier($homeTeam, $awayTeam);

        // Calculate the spread
        $spread = $this->calculateHypotheticalSpread($ratings, $multiplier, $game->id);

        // Store the calculated spread
        $this->storeHypotheticalSpread($game, $homeTeam, $awayTeam, $spread, $ratings);
    }

    private function fetchRatingsAndStats($game, $homeTeam, $awayTeam): array
    {
        return [
            'elo' => $this->fetchEloRatings($game, $homeTeam, $awayTeam),
            'fpi' => $this->fetchFpiRatings($game, $homeTeam, $awayTeam),
            'sagarin' => $this->fetchSagarinRatings($homeTeam, $awayTeam),
            'advancedStats' => $this->fetchAdvancedStats($homeTeam, $awayTeam),
            'strengthOfSchedule' => $this->fetchStrengthOfSchedule($game, $homeTeam, $awayTeam),
        ];
    }

    private function fetchEloRatings($game, $homeTeam, $awayTeam): array
    {
        return [
            'home' => CollegeFootballElo::where('team_id', $homeTeam->id)->where('year', $game->season)->value('elo'),
            'away' => CollegeFootballElo::where('team_id', $awayTeam->id)->where('year', $game->season)->value('elo'),
        ];
    }

    private function fetchFpiRatings($game, $homeTeam, $awayTeam): array
    {
        $homeFpi = CollegeFootballFpi::where('team_id', $homeTeam->id)->where('year', $game->season)->first();
        $awayFpi = CollegeFootballFpi::where('team_id', $awayTeam->id)->where('year', $game->season)->first();

        return [
            'home' => $homeFpi->fpi ?? null,
            'away' => $awayFpi->fpi ?? null,
            'home_special_teams' => $homeFpi->special_teams ?? null,
            'away_special_teams' => $awayFpi->special_teams ?? null,
        ];
    }

    private function fetchSagarinRatings($homeTeam, $awayTeam): array
    {
        return [
            'home' => Sagarin::where('id', $homeTeam->id)->value('rating'),
            'away' => Sagarin::where('id', $awayTeam->id)->value('rating'),
        ];
    }

    private function fetchAdvancedStats($homeTeam, $awayTeam): array
    {
        return [
            'home' => AdvancedGameStat::where('team_id', $homeTeam->id)->first(),
            'away' => AdvancedGameStat::where('team_id', $awayTeam->id)->first(),
        ];
    }

    private function fetchStrengthOfSchedule($game, $homeTeam, $awayTeam): array
    {
        return [
            'home' => CollegeFootballFpi::where('team_id', $homeTeam->id)->where('year', $game->season)->value('strength_of_schedule'),
            'away' => CollegeFootballFpi::where('team_id', $awayTeam->id)->where('year', $game->season)->value('strength_of_schedule'),
        ];
    }

    private function ratingsAreValid($ratings)
    {
        $requiredFields = array_merge(
            $ratings['elo'],
            $ratings['fpi'],
            $ratings['sagarin'],
            $ratings['strengthOfSchedule']
        );

        $advancedStatsPresent = !is_null($ratings['advancedStats']['home']) && !is_null($ratings['advancedStats']['away']);

        return !in_array(null, $requiredFields, true) && $advancedStatsPresent;
    }

    private function getConferenceMultiplier($homeTeam, $awayTeam): float|int
    {
        return ($homeTeam->conference === $awayTeam->conference) ? 1.4 : 1;
    }

    private function calculateHypotheticalSpread($ratings, $multiplier, $gameId): float
    {
        // Calculate different spreads
        $fpiSpread = $this->calculateFpiSpread($ratings['fpi']);
        $eloSpread = $this->calculateEloSpread($ratings['elo']);
        $sagarinSpread = $this->calculateSagarinSpread($ratings['sagarin']);
        $advancedSpreads = $this->calculateAdvancedSpreads($ratings['advancedStats']);

        // Combine all the spread components
        $totalSpread = $fpiSpread + $eloSpread + $sagarinSpread + $advancedSpreads;

        // Normalize the total spread
        $spread = $totalSpread / 5;

        // Apply conference multiplier
        $spread *= $multiplier;

        Log::info("Calculated spread after applying multiplier for Game ID {$gameId}: {$spread}");

        return round($spread, 2);
    }

    /**
     * Calculate FPI spread.
     */
    private function calculateFpiSpread($fpi)
    {
        return ($fpi['home'] - $fpi['away']) / 1.4;
    }

    /**
     * Calculate Elo spread.
     */
    private function calculateEloSpread($elo)
    {
        return ($elo['home'] - $elo['away']) / 40;
    }

    /**
     * Calculate Sagarin spread.
     */
    private function calculateSagarinSpread($sagarin)
    {
        return ($sagarin['home'] - $sagarin['away']) / 1.1;
    }

    /**
     * Calculate spreads using advanced stats.
     */
    private function calculateAdvancedSpreads($advancedStats): float
    {
        $homeStats = $advancedStats['home'];
        $awayStats = $advancedStats['away'];

        $offenseDefenseSpread = (($homeStats->offense_ppa - $awayStats->defense_ppa) + ($awayStats->offense_ppa - $homeStats->defense_ppa)) * 1.6;
        $successRateSpread = (($homeStats->offense_success_rate - $awayStats->defense_success_rate) + ($awayStats->offense_success_rate - $homeStats->defense_success_rate)) * 1.6;
        $explosivenessSpread = (($homeStats->offense_explosiveness - $awayStats->defense_explosiveness) + ($awayStats->offense_explosiveness - $homeStats->defense_explosiveness)) * 1.6;
        $rushingSpread = (($homeStats->offense_rushing_ppa - $awayStats->defense_rushing_ppa) + ($awayStats->offense_rushing_ppa - $homeStats->defense_rushing_ppa)) * 0.01;
        $passingSpread = (($homeStats->offense_passing_ppa - $awayStats->defense_passing_ppa) + ($awayStats->offense_passing_ppa - $homeStats->defense_passing_ppa)) * 0.01;
        $lineYardsSpread = (($homeStats->offense_line_yards - $awayStats->defense_line_yards) + ($awayStats->offense_line_yards - $homeStats->defense_line_yards)) * 0.01;

        return $offenseDefenseSpread + $successRateSpread + $explosivenessSpread + $rushingSpread + $passingSpread + $lineYardsSpread;
    }

    private function storeHypotheticalSpread($game, $homeTeam, $awayTeam, $spread, $ratings)
    {
        CollegeFootballHypothetical::updateOrCreate(
            ['game_id' => $game->id],
            [
                'week' => $game->week,
                'home_team_id' => $homeTeam->id,
                'away_team_id' => $awayTeam->id,
                'home_team_school' => $homeTeam->school,
                'away_team_school' => $awayTeam->school,
                'home_elo' => $ratings['elo']['home'],
                'away_elo' => $ratings['elo']['away'],
                'home_fpi' => $ratings['fpi']['home'],
                'away_fpi' => $ratings['fpi']['away'],
                'home_sagarin' => $ratings['sagarin']['home'],
                'away_sagarin' => $ratings['sagarin']['away'],
                'home_sos' => $ratings['strengthOfSchedule']['home'],
                'away_sos' => $ratings['strengthOfSchedule']['away'],
                'home_special_teams' => $ratings['fpi']['home_special_teams'],
                'away_special_teams' => $ratings['fpi']['away_special_teams'],
                'hypothetical_spread' => $spread,
            ]
        );

        Log::info("Hypothetical Spread for {$awayTeam->school} @ {$homeTeam->school}: $spread");
    }
}
