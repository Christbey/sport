<?php

namespace App\Console\Commands;

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

        // Check if there are any games
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
     * Fetch games for Week 1 where both teams are in the 'fbs' division.
     */
    private function fetchRelevantGames()
    {
        $today = Carbon::today();

        // Fetch week and season from config
        $week = config('college_football.week');
        $season = config('college_football.season');

        // Eager load the home and away team relationships
        return CollegeFootballGame::where('home_division', 'fbs')
            ->where('away_division', 'fbs')
            ->where('week', $week)        // Using config value for week
            ->where('season', $season)    // Using config value for season
            ->where('start_date', '>=', $today)
            ->with(['homeTeam', 'awayTeam'])  // Eager load teams
            ->get();
    }

    /**
     * Process a single game: calculate the spread and update or create the record.
     */
    private function processGame($game)
    {
        $homeTeam = $game->homeTeam;
        $awayTeam = $game->awayTeam;

        if (!$homeTeam || !$awayTeam) {
            return $this->logMissingTeamWarning($game);
        }

        [$homeElo, $awayElo] = $this->fetchEloRatings($game, $homeTeam, $awayTeam);
        [$homeFpi, $awayFpi] = $this->fetchFpiRatings($game, $homeTeam, $awayTeam);
        [$homeSagarin, $awaySagarin] = $this->fetchSagarinRatings($homeTeam, $awayTeam);

        if (!$this->ratingsAreValid($homeElo, $awayElo, $homeFpi, $awayFpi, $homeSagarin, $awaySagarin)) {
            return $this->logMissingRatingsWarning($game, $homeTeam, $awayTeam);
        }

        $spread = $this->calculateHypotheticalSpread($homeFpi, $awayFpi, $homeElo, $awayElo, $homeSagarin, $awaySagarin);
        $this->storeHypotheticalSpread($game, $homeTeam, $awayTeam, $spread);
    }

    /**
     * Log a warning if team data is missing.
     */
    private function logMissingTeamWarning($game)
    {
        Log::warning("Missing team data for game ID {$game->id}. Home or away team is null.");
    }

    /**
     * Fetch Elo ratings for home and away teams.
     */
    private function fetchEloRatings($game, $homeTeam, $awayTeam)
    {
        $homeElo = CollegeFootballElo::where('team_id', $homeTeam->id)->where('year', $game->season)->value('elo');
        $awayElo = CollegeFootballElo::where('team_id', $awayTeam->id)->where('year', $game->season)->value('elo');

        return [$homeElo, $awayElo];
    }

    /**
     * Fetch FPI ratings for home and away teams.
     */
    private function fetchFpiRatings($game, $homeTeam, $awayTeam)
    {
        $homeFpi = CollegeFootballFpi::where('team_id', $homeTeam->id)->where('year', $game->season)->value('fpi');
        $awayFpi = CollegeFootballFpi::where('team_id', $awayTeam->id)->where('year', $game->season)->value('fpi');

        return [$homeFpi, $awayFpi];
    }

    /**
     * Fetch Sagarin ratings for home and away teams.
     */
    private function fetchSagarinRatings($homeTeam, $awayTeam)
    {
        $homeSagarin = Sagarin::where('id', $homeTeam->id)->value('rating');
        $awaySagarin = Sagarin::where('id', $awayTeam->id)->value('rating');

        return [$homeSagarin, $awaySagarin];
    }

    /**
     * Check if all ratings are valid (not null).
     */
    private function ratingsAreValid(...$ratings)
    {
        return !in_array(null, $ratings, true);
    }

    /**
     * Log a warning if ratings data is missing.
     */
    private function logMissingRatingsWarning($game, $homeTeam, $awayTeam)
    {
        Log::warning("ELO, FPI, or Sagarin data missing for {$homeTeam->school} vs {$awayTeam->school} in {$game->season}.");
    }

    /**
     * Calculate the hypothetical spread using Elo, FPI, and Sagarin ratings.
     */
    private function calculateHypotheticalSpread($homeFpi, $awayFpi, $homeElo, $awayElo, $homeSagarin, $awaySagarin): float
    {
        $fpiSpread = $homeFpi && $awayFpi ? ($homeFpi - $awayFpi) / 2 : 0;
        $eloSpread = $homeElo && $awayElo ? ($homeElo - $awayElo) / 25 : 0;
        $sagarinSpread = $homeSagarin && $awaySagarin ? ($homeSagarin - $awaySagarin) / 10 : 0;

        return round(($fpiSpread + $eloSpread + $sagarinSpread) / 1.6, 2);
    }

    /**
     * Store or update the hypothetical spread in the database.
     */
    private function storeHypotheticalSpread($game, $homeTeam, $awayTeam, $spread)
    {
        CollegeFootballHypothetical::updateOrCreate(
            ['game_id' => $game->id],
            [
                'week' => $game->week,
                'home_team_id' => $homeTeam->id,
                'away_team_id' => $awayTeam->id,
                'home_team_school' => $homeTeam->school,
                'away_team_school' => $awayTeam->school,
                'home_elo' => CollegeFootballElo::where('team_id', $homeTeam->id)->where('year', $game->season)->value('elo'),
                'away_elo' => CollegeFootballElo::where('team_id', $awayTeam->id)->where('year', $game->season)->value('elo'),
                'home_fpi' => CollegeFootballFpi::where('team_id', $homeTeam->id)->where('year', $game->season)->value('fpi'),
                'away_fpi' => CollegeFootballFpi::where('team_id', $awayTeam->id)->where('year', $game->season)->value('fpi'),
                'home_sagarin' => Sagarin::where('id', $homeTeam->id)->value('rating'),
                'away_sagarin' => Sagarin::where('id', $awayTeam->id)->value('rating'),
                'hypothetical_spread' => $spread,
            ]
        );

        Log::info("Hypothetical Spread for {$awayTeam->school} @ {$homeTeam->school}: $spread");
    }
}
