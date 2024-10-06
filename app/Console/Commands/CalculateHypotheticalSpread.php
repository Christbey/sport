<?php

namespace App\Console\Commands;

use App\Models\CollegeFootball\AdvancedGameStat;
use App\Models\CollegeFootball\CollegeFootballElo;
use App\Models\CollegeFootball\CollegeFootballFpi;
use App\Models\CollegeFootball\CollegeFootballGame;
use App\Models\CollegeFootball\CollegeFootballHypothetical;
use App\Models\CollegeFootball\Sagarin;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CalculateHypotheticalSpread extends Command
{
    protected $signature = 'calculate:hypothetical-spreads';
    protected $description = 'Calculate hypothetical spreads for Week 1 games in CollegeFootballGame table where home_division = "fbs"';

    public function __construct()
    {
        parent::__construct();
    }

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
     */
    private function processGame($game)
    {
        $homeTeam = $game->homeTeam;
        $awayTeam = $game->awayTeam;

        if (!$homeTeam || !$awayTeam) {
            Log::warning("Missing team data for game ID {$game->id}.");
            return;
        }

        [$homeElo, $awayElo] = $this->fetchEloRatings($game, $homeTeam, $awayTeam);
        [$homeFpi, $awayFpi] = $this->fetchFpiRatings($game, $homeTeam, $awayTeam);
        [$homeSagarin, $awaySagarin] = $this->fetchSagarinRatings($homeTeam, $awayTeam);
        [$homeAdvancedStats, $awayAdvancedStats] = $this->fetchAdvancedStats($homeTeam, $awayTeam);

        // Log fetched ratings and stats for debugging
        Log::info("Game ID {$game->id}: Home ELO={$homeElo}, Away ELO={$awayElo}, Home FPI={$homeFpi}, Away FPI={$awayFpi}, Home Sagarin={$homeSagarin}, Away Sagarin={$awaySagarin}");
        Log::info("Game ID {$game->id}: Home Advanced Stats=" . json_encode($homeAdvancedStats) . ', Away Advanced Stats=' . json_encode($awayAdvancedStats));

        if (!$this->ratingsAreValid($homeElo, $awayElo, $homeFpi, $awayFpi, $homeSagarin, $awaySagarin, $homeAdvancedStats, $awayAdvancedStats)) {
            Log::warning("Incomplete ratings for game ID {$game->id} between {$homeTeam->school} and {$awayTeam->school}.");
            return;
        }

        // Ensure advanced stats are not null before accessing properties
        if (!$homeAdvancedStats || !$awayAdvancedStats) {
            Log::warning("Advanced stats missing for game ID {$game->id}.");
            return;
        }

        // Calculate the spread including advanced stats
        $spread = $this->calculateHypotheticalSpread(
            $homeFpi, $awayFpi, $homeElo, $awayElo, $homeSagarin, $awaySagarin,
            $homeAdvancedStats, $awayAdvancedStats
        );

        // Store or update the hypothetical spread in the database
        $this->storeHypotheticalSpread($game, $homeTeam, $awayTeam, $spread, $homeElo, $awayElo, $homeFpi, $awayFpi, $homeSagarin, $awaySagarin);
    }

    /**
     * Fetch Elo ratings for home and away teams.
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
     * Fetch FPI ratings for home and away teams.
     */
    private function fetchFpiRatings($game, $homeTeam, $awayTeam)
    {
        $homeFpi = CollegeFootballFpi::where('team_id', $homeTeam->id)
            ->where('year', $game->season)
            ->value('fpi');
        $awayFpi = CollegeFootballFpi::where('team_id', $awayTeam->id)
            ->where('year', $game->season)
            ->value('fpi');

        return [$homeFpi, $awayFpi];
    }

    /**
     * Fetch Sagarin ratings for home and away teams.
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
     */
    private function fetchAdvancedStats($homeTeam, $awayTeam)
    {
        $homeStats = AdvancedGameStat::where('team_id', $homeTeam->id)->first();
        $awayStats = AdvancedGameStat::where('team_id', $awayTeam->id)->first();

        return [$homeStats, $awayStats]; // Numerically indexed array
    }

    /**
     * Validate that all required ratings and stats are present.
     */
    private function ratingsAreValid($homeElo, $awayElo, $homeFpi, $awayFpi, $homeSagarin, $awaySagarin, $homeStats, $awayStats)
    {
        return !in_array(null, [$homeElo, $awayElo, $homeFpi, $awayFpi, $homeSagarin, $awaySagarin], true) &&
            !is_null($homeStats) && !is_null($awayStats);
    }

    /**
     * Calculate the hypothetical spread using Elo, FPI, Sagarin ratings, and advanced stats.
     */
    private function calculateHypotheticalSpread(
        $homeFpi, $awayFpi, $homeElo, $awayElo, $homeSagarin, $awaySagarin,
        $homeStats, $awayStats
    ): float
    {
        // Basic rating spread calculations
        $fpiSpread = ($homeFpi - $awayFpi) / 2;
        $eloSpread = ($homeElo - $awayElo) / 40;
        $sagarinSpread = ($homeSagarin - $awaySagarin) / 10;

        // Use null coalescing to prevent undefined property access
        $home_offense_ppa = $homeStats->offense_ppa ?? 0;
        $away_defense_ppa = $awayStats->defense_ppa ?? 0;
        $away_offense_ppa = $awayStats->offense_ppa ?? 0;
        $home_defense_ppa = $homeStats->defense_ppa ?? 0;

        Log::info("Calculating spread: Home PPA={$home_offense_ppa}, Away DEF PPA={$away_defense_ppa}, Away PPA={$away_offense_ppa}, Home DEF PPA={$home_defense_ppa}");

        $offenseDefenseSpread = (($home_offense_ppa - $away_defense_ppa) + ($away_offense_ppa - $home_defense_ppa) * 1.43);

        Log::info("Spread components: FPI Spread={$fpiSpread}, ELO Spread={$eloSpread}, Sagarin Spread={$sagarinSpread}, Offense/Defense Spread={$offenseDefenseSpread}");

        // Combine all spreads and normalize
        $spread = round(($fpiSpread + $eloSpread + $sagarinSpread + $offenseDefenseSpread) / 1.675, 2);

        Log::info("Calculated spread: $spread");

        return $spread;
    }

    /**
     * Store or update the hypothetical spread in the database.
     */
    private function storeHypotheticalSpread($game, $homeTeam, $awayTeam, $spread, $homeElo, $awayElo, $homeFpi, $awayFpi, $homeSagarin, $awaySagarin)
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
                'hypothetical_spread' => $spread,
            ]
        );

        Log::info("Hypothetical Spread for {$awayTeam->school} @ {$homeTeam->school}: $spread");
    }
}
