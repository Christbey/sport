<?php

namespace App\Console\Commands;

use App\Models\CollegeFootball\AdvancedGameStat;
use App\Models\CollegeFootball\CollegeFootballElo;
use App\Models\CollegeFootball\CollegeFootballFpi;
use App\Models\CollegeFootball\CollegeFootballGame;
use App\Models\CollegeFootball\CollegeFootballHypothetical;
use App\Models\CollegeFootball\CollegeFootballTeam;
use App\Models\CollegeFootball\Sagarin;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class CalculateHypotheticalSpread extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'calculate:hypothetical-spreads';

    /**
     * The console command description.
     */
    protected $description = 'Calculate hypothetical spreads for upcoming FBS games';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $games = $this->fetchRelevantGames();

        if ($games->isEmpty()) {
            Log::info('No games found for the specified week and season.');
            return;
        }

        foreach ($games as $game) {
            try {
                $this->processGame($game);
            } catch (Exception $e) {
                Log::error("Error processing game ID {$game->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Fetch games for the specified week and season where both teams are in the 'fbs' division.
     *
     * @return Collection
     */
    private function fetchRelevantGames()
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
     * Process a single game: fetch ratings and stats, calculate spread, and store the result.
     *
     * @param CollegeFootballGame $game
     */
    private function processGame($game)
    {
        $homeTeam = $game->homeTeam;
        $awayTeam = $game->awayTeam;

        if (!$homeTeam || !$awayTeam) {
            Log::warning("Missing team data for game ID {$game->id}.");
            return;
        }

        // Fetch ratings and stats
        [$homeElo, $awayElo] = $this->fetchEloRatings($game, $homeTeam, $awayTeam);
        [$homeFpi, $awayFpi, $homeSpecialTeams, $awaySpecialTeams] = $this->fetchFpiRatings($game, $homeTeam, $awayTeam);
        [$homeSagarin, $awaySagarin] = $this->fetchSagarinRatings($homeTeam, $awayTeam);
        [$homeAdvancedStats, $awayAdvancedStats] = $this->fetchAdvancedStats($homeTeam, $awayTeam);
        [$homeSOS, $awaySOS] = $this->fetchStrengthOfSchedule($game, $homeTeam, $awayTeam);

        // Validate ratings and stats
        if (!$this->ratingsAreValid(
            $homeElo,
            $awayElo,
            $homeFpi,
            $awayFpi,
            $homeSpecialTeams,
            $awaySpecialTeams,
            $homeSagarin,
            $awaySagarin,
            $homeAdvancedStats,
            $awayAdvancedStats,
            $homeSOS,
            $awaySOS
        )) {
            Log::warning("Incomplete ratings for game ID {$game->id} between {$homeTeam->school} and {$awayTeam->school}.");
            return;
        }

        // Check if teams are in the same conference
        $multiplier = 1; // Default multiplier
        if ($this->areTeamsInSameConference($homeTeam, $awayTeam)) {
            $multiplier = 1.1; // Adjust the multiplier as needed
            Log::info("Teams are in the same conference. Applying multiplier of {$multiplier}.");
        }

        // Calculate the spread
        $spread = $this->calculateHypotheticalSpread(
            $homeFpi,
            $awayFpi,
            $homeElo,
            $awayElo,
            $homeSagarin,
            $awaySagarin,
            $homeAdvancedStats,
            $awayAdvancedStats,
            $homeSOS,
            $awaySOS,
            $homeSpecialTeams,
            $awaySpecialTeams,
            $multiplier,
            $game->id
        );

        // Store the spread
        $this->storeHypotheticalSpread(
            $game,
            $homeTeam,
            $awayTeam,
            $spread,
            $homeElo,
            $awayElo,
            $homeFpi,
            $awayFpi,
            $homeSagarin,
            $awaySagarin,
            $homeSOS,
            $awaySOS,
            $homeSpecialTeams,
            $awaySpecialTeams
        );
    }

    /**
     * Fetch Elo ratings for home and away teams.
     *
     * @param CollegeFootballGame $game
     * @param CollegefootballTeam $homeTeam
     * @param CollegefootballTeam $awayTeam
     * @return array
     */
    private function fetchEloRatings($game, $homeTeam, $awayTeam)
    {
        $homeElo = CollegeFootballElo::where('team_id', $homeTeam->id)
            ->where('year', $game->season)
            ->value('elo');

        $awayElo = CollegeFootballElo::where('team_id', $awayTeam->id)
            ->where('year', $game->season)
            ->value('elo');

        return [$homeElo, $awayElo];
    }

    /**
     * Fetch FPI ratings and special teams ratings for home and away teams.
     *
     * @param CollegeFootballGame $game
     * @param CollegefootballTeam $homeTeam
     * @param CollegefootballTeam $awayTeam
     * @return array
     */
    private function fetchFpiRatings($game, $homeTeam, $awayTeam)
    {
        $homeFpiData = CollegeFootballFpi::where('team_id', $homeTeam->id)
            ->where('year', $game->season)
            ->first(['fpi', 'special_teams']);

        $awayFpiData = CollegeFootballFpi::where('team_id', $awayTeam->id)
            ->where('year', $game->season)
            ->first(['fpi', 'special_teams']);

        $homeFpi = $homeFpiData->fpi ?? null;
        $awayFpi = $awayFpiData->fpi ?? null;

        $homeSpecialTeams = $homeFpiData->special_teams ?? null;
        $awaySpecialTeams = $awayFpiData->special_teams ?? null;

        return [$homeFpi, $awayFpi, $homeSpecialTeams, $awaySpecialTeams];
    }

    /**
     * Fetch Sagarin ratings for home and away teams.
     *
     * @param CollegefootballTeam $homeTeam
     * @param CollegefootballTeam $awayTeam
     * @return array
     */
    private function fetchSagarinRatings($homeTeam, $awayTeam)
    {
        $homeSagarin = Sagarin::where('id', $homeTeam->id)
            ->value('rating');

        $awaySagarin = Sagarin::where('id', $awayTeam->id)
            ->value('rating');

        return [$homeSagarin, $awaySagarin];
    }

    /**
     * Fetch advanced stats for home and away teams.
     *
     * @param CollegefootballTeam $homeTeam
     * @param CollegefootballTeam $awayTeam
     * @return array
     */
    private function fetchAdvancedStats($homeTeam, $awayTeam)
    {
        $homeStats = AdvancedGameStat::where('team_id', $homeTeam->id)->first();
        $awayStats = AdvancedGameStat::where('team_id', $awayTeam->id)->first();

        return [$homeStats, $awayStats];
    }

    /**
     * Fetch Strength of Schedule (SOS) for home and away teams.
     *
     * @param CollegeFootballGame $game
     * @param CollegefootballTeam $homeTeam
     * @param CollegefootballTeam $awayTeam
     * @return array
     */
    private function fetchStrengthOfSchedule($game, $homeTeam, $awayTeam)
    {
        $homeSOS = CollegeFootballFpi::where('team_id', $homeTeam->id)
            ->where('year', $game->season)
            ->value('strength_of_schedule');

        $awaySOS = CollegeFootballFpi::where('team_id', $awayTeam->id)
            ->where('year', $game->season)
            ->value('strength_of_schedule');

        return [$homeSOS, $awaySOS];
    }

    /**
     * Validate that all required ratings and stats are present.
     *
     * @param mixed $homeElo
     * @param mixed $awayElo
     * @param mixed $homeFpi
     * @param mixed $awayFpi
     * @param mixed $homeSpecialTeams
     * @param mixed $awaySpecialTeams
     * @param mixed $homeSagarin
     * @param mixed $awaySagarin
     * @param AdvancedGameStat|null $homeStats
     * @param AdvancedGameStat|null $awayStats
     * @param mixed $homeSOS
     * @param mixed $awaySOS
     * @return bool
     */
    private function ratingsAreValid(
        $homeElo,
        $awayElo,
        $homeFpi,
        $awayFpi,
        $homeSpecialTeams,
        $awaySpecialTeams,
        $homeSagarin,
        $awaySagarin,
        $homeStats,
        $awayStats,
        $homeSOS,
        $awaySOS
    )
    {
        $basicRatings = [
            $homeElo,
            $awayElo,
            $homeFpi,
            $awayFpi,
            $homeSpecialTeams,
            $awaySpecialTeams,
            $homeSagarin,
            $awaySagarin,
            $homeSOS,
            $awaySOS
        ];
        $statsPresent = !is_null($homeStats) && !is_null($awayStats);

        return !in_array(null, $basicRatings, true) && $statsPresent;
    }

    /**
     * Check if both teams are in the same conference.
     *
     * @param CollegefootballTeam $homeTeam
     * @param CollegefootballTeam $awayTeam
     * @return bool
     */
    private function areTeamsInSameConference($homeTeam, $awayTeam)
    {
        return $homeTeam->conference === $awayTeam->conference;
    }

    /**
     * Calculate the hypothetical spread using various ratings and advanced stats.
     *
     * @param float $homeFpi
     * @param float $awayFpi
     * @param float $homeElo
     * @param float $awayElo
     * @param float $homeSagarin
     * @param float $awaySagarin
     * @param AdvancedGameStat $homeStats
     * @param AdvancedGameStat $awayStats
     * @param float $homeSOS
     * @param float $awaySOS
     * @param float $homeSpecialTeams
     * @param float $awaySpecialTeams
     * @param float $multiplier
     * @param int $gameId
     * @return float
     */
    private function calculateHypotheticalSpread(
        $homeFpi,
        $awayFpi,
        $homeElo,
        $awayElo,
        $homeSagarin,
        $awaySagarin,
        $homeStats,
        $awayStats,
        $homeSOS,
        $awaySOS,
        $homeSpecialTeams,
        $awaySpecialTeams,
        $multiplier = 1.345,
        $gameId
    )
    {
        // Ensure all numeric values are cast properly
        $homeFpi = (float)$homeFpi;
        $awayFpi = (float)$awayFpi;
        $homeElo = (float)$homeElo;
        $awayElo = (float)$awayElo;
        $homeSagarin = (float)$homeSagarin;
        $awaySagarin = (float)$awaySagarin;
        $homeSOS = (float)$homeSOS;
        $awaySOS = (float)$awaySOS;
        $homeSpecialTeams = (float)$homeSpecialTeams;
        $awaySpecialTeams = (float)$awaySpecialTeams;

        // Basic rating spread calculations
        $fpiSpread = ($homeFpi - $awayFpi) / 1.2;
        $eloSpread = ($homeElo - $awayElo) / 40;
        $sagarinSpread = ($homeSagarin - $awaySagarin) / 1.36;

        // Advanced stats calculations

        // Offense-Defense PPA Spread
        $homeOffensePpa = $homeStats->offense_ppa ?? 0;
        $awayDefensePpa = $awayStats->defense_ppa ?? 0;
        $awayOffensePpa = $awayStats->offense_ppa ?? 0;
        $homeDefensePpa = $homeStats->defense_ppa ?? 0;
        $offenseDefenseSpread = (($homeOffensePpa - $awayDefensePpa) + ($awayOffensePpa - $homeDefensePpa)) * 1.43;

        // Success Rate Spread
        $homeOffenseSuccessRate = $homeStats->offense_success_rate ?? 0;
        $awayDefenseSuccessRate = $awayStats->defense_success_rate ?? 0;
        $awayOffenseSuccessRate = $awayStats->offense_success_rate ?? 0;
        $homeDefenseSuccessRate = $homeStats->defense_success_rate ?? 0;
        $successRateSpread = (($homeOffenseSuccessRate - $awayDefenseSuccessRate) + ($awayOffenseSuccessRate - $homeDefenseSuccessRate)) * 100;

        // Explosiveness Spread
        $homeOffenseExplosiveness = $homeStats->offense_explosiveness ?? 0;
        $awayDefenseExplosiveness = $awayStats->defense_explosiveness ?? 0;
        $awayOffenseExplosiveness = $awayStats->offense_explosiveness ?? 0;
        $homeDefenseExplosiveness = $homeStats->defense_explosiveness ?? 0;
        $explosivenessSpread = (($homeOffenseExplosiveness - $awayDefenseExplosiveness) + ($awayOffenseExplosiveness - $homeDefenseExplosiveness)) * 100;

        // Rushing PPA Spread
        $homeRushingPpa = $homeStats->offense_rushing_ppa ?? 0;
        $awayRushingDefensePpa = $awayStats->defense_rushing_ppa ?? 0;
        $awayRushingPpa = $awayStats->offense_rushing_ppa ?? 0;
        $homeRushingDefensePpa = $homeStats->defense_rushing_ppa ?? 0;
        $rushingSpread = (($homeRushingPpa - $awayRushingDefensePpa) + ($awayRushingPpa - $homeRushingDefensePpa)) * 1.5;

        // Passing PPA Spread
        $homePassingPpa = $homeStats->offense_passing_ppa ?? 0;
        $awayPassingDefensePpa = $awayStats->defense_passing_ppa ?? 0;
        $awayPassingPpa = $awayStats->offense_passing_ppa ?? 0;
        $homePassingDefensePpa = $homeStats->defense_passing_ppa ?? 0;
        $passingSpread = (($homePassingPpa - $awayPassingDefensePpa) + ($awayPassingPpa - $homePassingDefensePpa)) * 1.5;

        // Line Yards Spread
        $homeOffenseLineYards = $homeStats->offense_line_yards ?? 0;
        $awayDefenseLineYards = $awayStats->defense_line_yards ?? 0;
        $awayOffenseLineYards = $awayStats->offense_line_yards ?? 0;
        $homeDefenseLineYards = $homeStats->defense_line_yards ?? 0;
        $lineYardsSpread = (($homeOffenseLineYards - $awayDefenseLineYards) + ($awayOffenseLineYards - $homeDefenseLineYards)) * 10;

        // Strength of Schedule difference
        $sosDifference = ($homeSOS - $awaySOS) / 10; // Adjust the divisor to scale impact

        // Special Teams Difference
        $specialTeamsDifference = $homeSpecialTeams - $awaySpecialTeams;
        $specialTeamsSpread = $specialTeamsDifference * 1.2; // Adjust the multiplier as needed

        // Logging
        Log::info("Calculating spread components for Game ID {$gameId}:");
        Log::info("FPI Spread={$fpiSpread}, ELO Spread={$eloSpread}, Sagarin Spread={$sagarinSpread}");
        Log::info("Offense/Defense PPA Spread={$offenseDefenseSpread}, Success Rate Spread={$successRateSpread}, Explosiveness Spread={$explosivenessSpread}");
        Log::info("Rushing Spread={$rushingSpread}, Passing Spread={$passingSpread}, Line Yards Spread={$lineYardsSpread}");
        Log::info("Strength of Schedule Difference={$sosDifference}, Special Teams Spread={$specialTeamsSpread}");

        // Combine all spreads
        $totalSpread = $fpiSpread + $eloSpread + $sagarinSpread + $offenseDefenseSpread + $successRateSpread + $explosivenessSpread + $rushingSpread + $passingSpread + $lineYardsSpread + $sosDifference + $specialTeamsSpread;

        // Normalize the total spread
        $spread = $totalSpread / 5; // Adjust this divisor as necessary based on testing

        // Apply conference multiplier
        $spread *= $multiplier;

        // Round the spread
        $spread = round($spread, 2);

        // Log the final spread
        Log::info("Calculated spread after applying multiplier: $spread");

        return $spread;
    }

    /**
     * Store or update the hypothetical spread in the database.
     *
     * @param CollegeFootballGame $game
     * @param CollegefootballTeam $homeTeam
     * @param CollegefootballTeam $awayTeam
     * @param float $spread
     * @param float $homeElo
     * @param float $awayElo
     * @param float $homeFpi
     * @param float $awayFpi
     * @param float $homeSagarin
     * @param float $awaySagarin
     * @param float $homeSOS
     * @param float $awaySOS
     * @param float $homeSpecialTeams
     * @param float $awaySpecialTeams
     */
    private function storeHypotheticalSpread(
        $game,
        $homeTeam,
        $awayTeam,
        $spread,
        $homeElo,
        $awayElo,
        $homeFpi,
        $awayFpi,
        $homeSagarin,
        $awaySagarin,
        $homeSOS,
        $awaySOS,
        $homeSpecialTeams,
        $awaySpecialTeams
    )
    {
        CollegeFootballHypothetical::updateOrCreate(
            ['game_id' => $game->id],
            [
                'week' => $game->week,
                'home_team_id' => $homeTeam->id,
                'away_team_id' => $awayTeam->id,
                'home_team_school' => $homeTeam->school,
                'away_team_school' => $awayTeam->school,
                'home_elo' => $homeElo,
                'away_elo' => $awayElo,
                'home_fpi' => $homeFpi,
                'away_fpi' => $awayFpi,
                'home_sagarin' => $homeSagarin,
                'away_sagarin' => $awaySagarin,
                'home_sos' => $homeSOS,
                'away_sos' => $awaySOS,
                'home_special_teams' => $homeSpecialTeams,
                'away_special_teams' => $awaySpecialTeams,
                'hypothetical_spread' => $spread,
            ]
        );

        Log::info("Hypothetical Spread for {$awayTeam->school} @ {$homeTeam->school}: $spread");
    }
}
