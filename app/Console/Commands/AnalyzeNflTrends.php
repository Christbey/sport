<?php

namespace App\Console\Commands;

use App\Models\Nfl\NflBettingOdds;
use App\Models\Nfl\NflBoxScore;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class AnalyzeNflTrends extends Command
{
    /** @var array */
    private const QUARTERS = ['Q1', 'Q2', 'Q3', 'Q4'];
    /** @var array */
    private const HALVES = [
        'first' => ['Q1', 'Q2'],
        'second' => ['Q3', 'Q4'],
    ];
    /** @var array */
    private const SCORING_THRESHOLDS = [
        20 => 'twenty',
        24 => 'twenty-four',
        30 => 'thirty',
    ];
    /** @var string */
    protected $signature = 'analyze:nfl-trends 
                            {team : Team abbreviation or name}
                            {--season= : Specific season to analyze}
                            {--min-occurrences=2 : Minimum occurrences for a trend}
                            {--games=20 : Number of games to analyze}
                            ';
    /** @var string */
    protected $description = 'Analyze NFL betting trends for a specific team';
    /** @var int */
    private int $unit = 100;

    /** @var string */
    private string $teamName;

    /** @var int */
    private int $minOccurrences;

    /** @var Collection */
    private Collection $games;

    /** @var Collection */
    private Collection $bettingOdds;

    /** @var array */
    private array $trendsFound = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        try {
            $this->initializeAnalysis();

            if ($this->games->isEmpty()) {
                $this->error("No games found for {$this->teamName}");
                return 1;
            }

            $this->info("\nFound {$this->games->count()} games for analysis");

            $this->analyzeGames();
            $this->displayTrends();

            return 0;
        } catch (Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            return 1;
        }
    }

    // --------------------------------------------
    // Initialization Methods
    // --------------------------------------------

    /**
     * Initialize analysis parameters and data.
     */
    private function initializeAnalysis(): void
    {
        $this->teamName = strtoupper($this->argument('team'));
        $this->minOccurrences = (int)$this->option('min-occurrences');
        $this->games = $this->fetchGames();
        $this->bettingOdds = $this->fetchBettingOdds();
        $this->initializeTrends();
    }

    /**
     * Fetch games for the specified team and season.
     *
     * @return Collection
     */
    private function fetchGames(): Collection
    {
        $query = NflBoxScore::query()
            ->where(function ($query) {
                $query->where('home_team', $this->teamName)
                    ->orWhere('away_team', $this->teamName);
            })
            ->with('teamStats')
            ->orderBy('game_date', 'desc');

        if ($season = $this->option('season')) {
            $query->whereYear('game_date', $season);
        }

        return $query->take($this->option('games'))->get();
    }

    // --------------------------------------------
    // Data Fetching Methods
    // --------------------------------------------

    /**
     * Fetch betting odds for the games.
     *
     * @return Collection
     */
    private function fetchBettingOdds(): Collection
    {
        return NflBettingOdds::whereIn('event_id', $this->games->pluck('game_id'))
            ->get()
            ->keyBy('event_id');
    }

    /**
     * Initialize the trends array.
     */
    private function initializeTrends(): void
    {
        $this->trendsFound = [
            'spread_cover' => [],
            'scoring' => [],
            'quarter_scoring' => [],
            'half_scoring' => [],
            'margin' => [],
            'first_score' => [],
            'totals' => [],
            'stat_trends' => [],
        ];
    }

    // --------------------------------------------
    // Analysis Methods
    // --------------------------------------------

    /**
     * Analyze all fetched games.
     */
    private function analyzeGames(): void
    {
        foreach ($this->games as $game) {
            $this->analyzeGameSpread($game);
            $this->analyzeGameScoring($game);
            $this->analyzeQuarterAndHalfScoring($game);
            $this->analyzeMargins($game);
            $this->analyzeFirstScore($game);
            $this->analyzeTotals($game);
            $this->analyzeStats($game);
        }
    }

    /**
     * Analyze spread cover data for a game.
     *
     * @param NflBoxScore $game
     */
    private function analyzeGameSpread(NflBoxScore $game): void
    {
        $isHome = $game->home_team === $this->teamName;
        $odds = $this->bettingOdds->get($game->game_id);

        if (!$odds) {
            $this->warn("No betting odds found for game {$game->game_id}");
            return;
        }

        $spread = $isHome ? $odds->spread_home : $odds->spread_away;
        $actualDiff = $this->calculateActualDiff($game, $isHome);

        $this->trendsFound['spread_cover'][] = [
            'covered' => $actualDiff > $spread,
            'odds' => $odds->spread_odds ?? -110,
            'game_id' => $game->game_id,
        ];
    }

    /**
     * Calculate the actual point differential for a game.
     *
     * @param NflBoxScore $game
     * @param bool $isHome
     * @return float
     */
    private function calculateActualDiff(NflBoxScore $game, bool $isHome): float
    {
        $diff = $game->home_points - $game->away_points;
        return $isHome ? $diff : -$diff;
    }

    /**
     * Analyze scoring data for a game.
     *
     * @param NflBoxScore $game
     */
    private function analyzeGameScoring(NflBoxScore $game): void
    {
        $isHome = $game->home_team === $this->teamName;
        $teamScore = $isHome ? $game->home_points : $game->away_points;

        $this->trendsFound['scoring'][] = [
            'points' => $teamScore,
            'game_id' => $game->game_id,
        ];
    }

    /**
     * Analyze quarter and half scoring data for a game.
     *
     * @param NflBoxScore $game
     */
    private function analyzeQuarterAndHalfScoring(NflBoxScore $game): void
    {
        $isHome = $game->home_team === $this->teamName;
        $lineScore = $isHome ? $game->home_line_score : $game->away_line_score;
        $oppLineScore = $isHome ? $game->away_line_score : $game->home_line_score;

        if (!$lineScore || !$oppLineScore) {
            return;
        }

        // Analyze half scoring
        foreach (self::HALVES as $half => $quarters) {
            $teamHalfScore = array_sum(array_map(fn($q) => $lineScore[$q] ?? 0, $quarters));
            $oppHalfScore = array_sum(array_map(fn($q) => $oppLineScore[$q] ?? 0, $quarters));

            $this->trendsFound['half_scoring'][] = [
                'half' => $half,
                'won_half' => $teamHalfScore > $oppHalfScore,
                'team_score' => $teamHalfScore,
                'opp_score' => $oppHalfScore,
                'game_id' => $game->game_id,
            ];
        }

        // Analyze quarter scoring
        foreach (self::QUARTERS as $quarter) {
            $teamScore = (int)($lineScore[$quarter] ?? 0);
            $oppScore = (int)($oppLineScore[$quarter] ?? 0);

            $this->trendsFound['quarter_scoring'][] = [
                'quarter' => $quarter,
                'won_quarter' => $teamScore > $oppScore,
                'team_score' => $teamScore,
                'opp_score' => $oppScore,
                'game_id' => $game->game_id,
            ];
        }
    }

    /**
     * Analyze margin of victory data for a game.
     *
     * @param NflBoxScore $game
     */
    private function analyzeMargins(NflBoxScore $game): void
    {
        $isHome = $game->home_team === $this->teamName;
        $margin = $this->calculateActualDiff($game, $isHome);

        $this->trendsFound['margin'][] = [
            'margin' => $margin,
            'game_id' => $game->game_id,
            'is_win' => $margin > 0,
            'is_double_digit' => abs($margin) >= 10,
        ];
    }

    /**
     * Analyze first score data for a game.
     *
     * @param NflBoxScore $game
     */
    private function analyzeFirstScore(NflBoxScore $game): void
    {
        $isHome = $game->home_team === $this->teamName;
        $lineScore = $isHome ? $game->home_line_score : $game->away_line_score;
        $oppLineScore = $isHome ? $game->away_line_score : $game->home_line_score;

        if (!$lineScore || !$oppLineScore) {
            return;
        }

        foreach (self::QUARTERS as $quarter) {
            $teamScore = (int)($lineScore[$quarter] ?? 0);
            $oppScore = (int)($oppLineScore[$quarter] ?? 0);

            if ($teamScore > 0 || $oppScore > 0) {
                $this->trendsFound['first_score'][] = [
                    'scored_first' => $teamScore > 0 && ($oppScore === 0 || $teamScore > $oppScore),
                    'scoring_quarter' => $quarter,
                    'game_id' => $game->game_id,
                ];
                break; // Found the first scoring quarter
            }
        }
    }

    /**
     * Analyze total points data for a game.
     *
     * @param NflBoxScore $game
     */
    private function analyzeTotals(NflBoxScore $game): void
    {
        $odds = $this->bettingOdds->get($game->game_id);
        if (!$odds) {
            return;
        }

        $totalPoints = $game->home_points + $game->away_points;
        $overUnder = $odds->total_over ?? 0;

        if ($overUnder > 0) {
            $this->trendsFound['totals'][] = [
                'total_points' => $totalPoints,
                'over_under' => $overUnder,
                'went_over' => $totalPoints > $overUnder,
                'game_id' => $game->game_id,
            ];
        }
    }

    // --------------------------------------------
    // Display Methods
    // --------------------------------------------

    /**
     * Analyze statistical data for a game.
     *
     * @param NflBoxScore $game
     */
    private function analyzeStats(NflBoxScore $game): void
    {
        $isHome = $game->home_team === $this->teamName;
        $teamAbv = $isHome ? $game->home_team : $game->away_team;
        $oppAbv = $isHome ? $game->away_team : $game->home_team;

        $stats = $game->teamStats->firstWhere('team_abv', $teamAbv);
        $oppStats = $game->teamStats->firstWhere('team_abv', $oppAbv);

        if (!$stats || !$oppStats) {
            $this->warn("No stats found for game {$game->game_id}");
            return;
        }

        $this->trendsFound['stat_trends'][] = [
            'total_yards' => (int)$stats->total_yards,
            'rushing_yards' => (int)$stats->rushing_yards,
            'passing_yards' => (int)$stats->passing_yards,
            'game_id' => $game->game_id,
            'result' => $this->getGameResult($game),
            'spread_result' => $this->getSpreadResult($game),
            'total_result' => $this->getTotalResult($game),
            'yard_differential' => (int)$stats->total_yards - (int)$oppStats->total_yards,
            'spread' => $this->getClosingSpread($game),
            'next_game_q1_points' => $this->getNextGamePoints($game, 'Q1'),
            'next_game_h2_points' => $this->getNextGameHalfPoints($game, 'second'),
            'previous_rushing_yards' => $this->getPreviousGameStat($game, 'rushing_yards'),
            'previous_passing_yards' => $this->getPreviousGameStat($game, 'passing_yards'),
            'previous_total_yards' => $this->getPreviousGameStat($game, 'total_yards'),
            'previous_result' => $this->getPreviousGameResult($game),
            'points' => $isHome ? $game->home_points : $game->away_points,
        ];
    }

    /**
     * Get the game result (Win/Loss).
     *
     * @param NflBoxScore $game
     * @return string
     */
    private function getGameResult(NflBoxScore $game): string
    {
        $isHome = $game->home_team === $this->teamName;
        $teamScore = $isHome ? $game->home_points : $game->away_points;
        $oppScore = $isHome ? $game->away_points : $game->home_points;

        return $teamScore > $oppScore ? 'W' : 'L';
    }

    /**
     * Get the spread result (Cover/Miss).
     *
     * @param NflBoxScore $game
     * @return string|null
     */
    private function getSpreadResult(NflBoxScore $game): ?string
    {
        $odds = $this->bettingOdds->get($game->game_id);
        if (!$odds) {
            return null;
        }

        $isHome = $game->home_team === $this->teamName;
        $spread = $isHome ? $odds->spread_home : $odds->spread_away;
        $actualDiff = $this->calculateActualDiff($game, $isHome);

        return $actualDiff > $spread ? 'COVER' : 'MISS';
    }

    /**
     * Get the total result (Over/Under).
     *
     * @param NflBoxScore $game
     * @return string|null
     */
    private function getTotalResult(NflBoxScore $game): ?string
    {
        $odds = $this->bettingOdds->get($game->game_id);
        if (!$odds) {
            return null;
        }

        $totalPoints = $game->home_points + $game->away_points;
        $totalLine = $odds->total_over ?? 0;

        if ($totalLine === 0) {
            return null;
        }

        return $totalPoints > $totalLine ? 'OVER' : 'UNDER';
    }

    /**
     * Get the closing spread for a game.
     *
     * @param NflBoxScore $game
     * @return float|null
     */
    private function getClosingSpread(NflBoxScore $game): ?float
    {
        $odds = $this->bettingOdds->get($game->game_id);
        if (!$odds) {
            return null;
        }

        $isHome = $game->home_team === $this->teamName;
        return $isHome ? $odds->spread_home : $odds->spread_away;
    }

    /**
     * Get points scored in the next game for a specific quarter.
     *
     * @param NflBoxScore $game
     * @param string $quarter
     * @return int|null
     */
    private function getNextGamePoints(NflBoxScore $game, string $quarter): ?int
    {
        $nextGame = $this->getNextGame($game);
        if (!$nextGame) {
            return null;
        }

        $isHome = $nextGame->home_team === $this->teamName;
        $lineScore = $isHome ? $nextGame->home_line_score : $nextGame->away_line_score;

        return (int)($lineScore[$quarter] ?? 0);
    }

    /**
     * Get the next game for the team.
     *
     * @param NflBoxScore $game
     * @return NflBoxScore|null
     */
    private function getNextGame(NflBoxScore $game): ?NflBoxScore
    {
        return $this->games->where('game_date', '>', $game->game_date)->first();
    }

    /**
     * Get points scored in the next game for a specific half.
     *
     * @param NflBoxScore $game
     * @param string $half
     * @return int|null
     */
    private function getNextGameHalfPoints(NflBoxScore $game, string $half): ?int
    {
        $nextGame = $this->getNextGame($game);
        if (!$nextGame) {
            return null;
        }

        $isHome = $nextGame->home_team === $this->teamName;
        $lineScore = $isHome ? $nextGame->home_line_score : $nextGame->away_line_score;

        $quarters = self::HALVES[$half];
        return array_sum(array_map(fn($q) => (int)($lineScore[$q] ?? 0), $quarters));
    }

    /**
     * Get a specific stat from the previous game.
     *
     * @param NflBoxScore $game
     * @param string $statField
     * @return int
     */
    private function getPreviousGameStat(NflBoxScore $game, string $statField): int
    {
        $previousGame = $this->games->where('game_date', '<', $game->game_date)->last();

        if (!$previousGame) {
            return 0;
        }

        $isHome = $previousGame->home_team === $this->teamName;
        $stats = $previousGame->teamStats->firstWhere('team_abv', $isHome ? $previousGame->home_team : $previousGame->away_team);

        return (int)($stats->$statField ?? 0);
    }

    /**
     * Get the previous game result (Win/Loss).
     *
     * @param NflBoxScore $game
     * @return string|null
     */
    private function getPreviousGameResult(NflBoxScore $game): ?string
    {
        $previousGame = $this->games->where('game_date', '<', $game->game_date)->last();

        if (!$previousGame) {
            return null;
        }

        $isHome = $previousGame->home_team === $this->teamName;
        $teamScore = $isHome ? $previousGame->home_points : $previousGame->away_points;
        $oppScore = $isHome ? $previousGame->away_points : $previousGame->home_points;

        return $teamScore > $oppScore ? 'W' : 'L';
    }

    /**
     * Display all analyzed trends.
     */
    private function displayTrends(): void
    {
        $totalGames = $this->games->count();
        $this->info("\nAnalyzing trends for {$this->teamName}");
        $this->displaySpreadTrends($totalGames);
        $this->displayScoringTrends($totalGames);
        $this->displayQuarterTrends($totalGames);
        $this->displayHalfTrends($totalGames);
        $this->displayMarginTrends($totalGames);
        $this->displayFirstScoreTrends($totalGames);
        $this->displayTotalsTrends($totalGames);
        $this->displayStatTrends($totalGames);
        $this->displayAnalysisParameters($totalGames);
    }

    /**
     * Display spread cover trends.
     *
     * @param int $totalGames
     */
    private function displaySpreadTrends(int $totalGames): void
    {
        $covers = collect($this->trendsFound['spread_cover'])
            ->where('covered', true)
            ->count();

        if ($covers >= $this->minOccurrences) {
            $this->info(sprintf(
                'The %s have covered the spread in %d of their last %d games',
                $this->teamName,
                $covers,
                $totalGames
            ));
        }
    }

    /**
     * Display scoring trends.
     *
     * @param int $totalGames
     */
    private function displayScoringTrends(int $totalGames): void
    {
        $scores = collect($this->trendsFound['scoring']);

        foreach (self::SCORING_THRESHOLDS as $threshold => $label) {
            $count = $scores->where('points', '>', $threshold)->count();

            if ($count >= $this->minOccurrences) {
                $this->info(sprintf(
                    'The %s have scored over %d points in %d of their last %d games',
                    $this->teamName,
                    $threshold,
                    $count,
                    $totalGames
                ));
            }
        }
    }

    /**
     * Display quarter performance trends.
     *
     * @param int $totalGames
     */
    private function displayQuarterTrends(int $totalGames): void
    {
        $this->info("\n== Quarter Performance ==");

        $quarters = collect($this->trendsFound['quarter_scoring']);

        foreach (self::QUARTERS as $quarter) {
            $quarterData = $quarters->where('quarter', $quarter);

            $quarterWins = $quarterData->where('won_quarter', true)->count();
            $highScoring = $quarterData->where('team_score', '>=', 7)->count();
            $anyScoring = $quarterData->where('team_score', '>', 0)->count();

            if ($quarterWins >= $this->minOccurrences) {
                $this->info(sprintf(
                    'The %s have won %s in %d of their last %d games',
                    $this->teamName,
                    $quarter,
                    $quarterWins,
                    $totalGames
                ));
            }

            if ($highScoring >= $this->minOccurrences) {
                $this->info(sprintf(
                    'The %s have scored 7+ points in %s in %d of their last %d games',
                    $this->teamName,
                    $quarter,
                    $highScoring,
                    $totalGames
                ));
            }

            if ($anyScoring >= $this->minOccurrences) {
                $this->info(sprintf(
                    'The %s have scored in %s in %d of their last %d games',
                    $this->teamName,
                    $quarter,
                    $anyScoring,
                    $totalGames
                ));
            }
        }
    }

    /**
     * Display half performance trends.
     *
     * @param int $totalGames
     */
    private function displayHalfTrends(int $totalGames): void
    {
        $this->info("\n== Half Performance ==");

        foreach (array_keys(self::HALVES) as $half) {
            $halfData = collect($this->trendsFound['half_scoring'])
                ->where('half', $half)
                ->unique('game_id');

            $halfWins = $halfData->where('won_half', true)->count();
            $highScoring = $halfData->where('team_score', '>=', 14)->count();
            $midScoring = $halfData->whereBetween('team_score', [7, 13])->count();
            $lowScoring = $halfData->where('team_score', '<', 7)->count();

            if ($halfWins >= $this->minOccurrences) {
                $this->info(sprintf(
                    'The %s have won the %s half in %d of their last %d games',
                    $this->teamName,
                    $half,
                    $halfWins,
                    $totalGames
                ));
            }

            if ($highScoring >= $this->minOccurrences) {
                $this->info(sprintf(
                    'The %s have scored 14+ points in the %s half in %d of their last %d games',
                    $this->teamName,
                    $half,
                    $highScoring,
                    $totalGames
                ));
            }

            if ($midScoring >= $this->minOccurrences) {
                $this->info(sprintf(
                    'The %s have scored 7-13 points in the %s half in %d of their last %d games',
                    $this->teamName,
                    $half,
                    $midScoring,
                    $totalGames
                ));
            }

            if ($lowScoring >= $this->minOccurrences) {
                $this->info(sprintf(
                    'The %s have scored fewer than 7 points in the %s half in %d of their last %d games',
                    $this->teamName,
                    $half,
                    $lowScoring,
                    $totalGames
                ));
            }

            if ($this->option('verbose')) {
                $this->line("Debug for {$half} half:");
                $halfData->each(function ($item) {
                    $this->line("Game: {$item['game_id']} - Score: {$item['team_score']}");
                });
            }
        }
    }

    /**
     * Display margin trends.
     *
     * @param int $totalGames
     */
    private function displayMarginTrends(int $totalGames): void
    {
        $this->info("\n== Margin Trends ==");

        $margins = collect($this->trendsFound['margin']);

        $doubleDigitWins = $margins->where('is_win', true)->where('is_double_digit', true)->count();
        $closeGames = $margins->whereBetween('margin', [-7, 7])->count();

        if ($doubleDigitWins >= $this->minOccurrences) {
            $this->info(sprintf(
                'The %s have won by 10+ points in %d of their last %d games',
                $this->teamName,
                $doubleDigitWins,
                $totalGames
            ));
        }

        if ($closeGames >= $this->minOccurrences) {
            $this->info(sprintf(
                'The %s have played games decided by 7 or fewer points in %d of their last %d games',
                $this->teamName,
                $closeGames,
                $totalGames
            ));
        }
    }

    /**
     * Display first score trends.
     *
     * @param int $totalGames
     */
    private function displayFirstScoreTrends(int $totalGames): void
    {
        $this->info("\n== First Score Trends ==");

        $scores = collect($this->trendsFound['first_score']);
        $scoredFirst = $scores->where('scored_first', true)->count();
        $q1Score = $scores->where('scoring_quarter', 'Q1')->count();
        $q2Score = $scores->where('scoring_quarter', 'Q2')->count();

        if ($scoredFirst >= $this->minOccurrences) {
            $this->info(sprintf(
                'The %s have scored first in %d of their last %d games',
                $this->teamName,
                $scoredFirst,
                $totalGames
            ));
        }

        if ($q1Score >= $this->minOccurrences) {
            $this->info(sprintf(
                'The %s had their first score in Q1 in %d of their last %d games',
                $this->teamName,
                $q1Score,
                $totalGames
            ));
        }

        if ($q2Score >= $this->minOccurrences) {
            $this->info(sprintf(
                'The %s had their first score in Q2 in %d of their last %d games',
                $this->teamName,
                $q2Score,
                $totalGames
            ));
        }
    }

    /**
     * Display totals trends.
     *
     * @param int $totalGames
     */
    private function displayTotalsTrends(int $totalGames): void
    {
        $this->info("\n== Game Totals Trends ==");

        $totals = collect($this->trendsFound['totals']);
        $overs = $totals->where('went_over', true)->count();
        $highScoring = $totals->where('total_points', '>=', 50)->count();
        $lowScoring = $totals->where('total_points', '<=', 40)->count();

        if ($overs >= $this->minOccurrences) {
            $this->info(sprintf(
                'The %s games have gone OVER in %d of their last %d games',
                $this->teamName,
                $overs,
                $totalGames
            ));
        }

        if ($highScoring >= $this->minOccurrences) {
            $this->info(sprintf(
                'The %s have been in games with 50+ total points in %d of their last %d games',
                $this->teamName,
                $highScoring,
                $totalGames
            ));
        }

        if ($lowScoring >= $this->minOccurrences) {
            $this->info(sprintf(
                'The %s have been in games with 40 or fewer total points in %d of their last %d games',
                $this->teamName,
                $lowScoring,
                $totalGames
            ));
        }
    }

    /**
     * Display statistical and betting trends.
     *
     * @param int $totalGames
     */
    private function displayStatTrends(int $totalGames): void
    {
        if (empty($this->trendsFound['stat_trends'])) {
            return;
        }

        $this->info("\n== Statistical & Betting Trends ==");
        $stats = collect($this->trendsFound['stat_trends']);

        // Winning Formula Stats
        $this->displayWinningFormula($stats);

        // Over/Under Correlations
        $this->displayTotalCorrelations($stats);

        // Spread Performance by Yardage
        $this->displaySpreadCorrelations($stats);

        // Quarter-based Performance
        $this->displayQuarterBasedStats($stats);

        // Primary Performance Correlations
        $this->displayPerformanceCorrelations($stats);

        // Scoring Pattern Analysis
        $this->displayScoringPatternCorrelations($stats);

        // Situational Betting Analysis
        $this->displaySituationalTrends($stats);

        // Momentum-Based Trends
        $this->displayMomentumTrends($stats);

        // Additional Analyses
        $this->performAdditionalAnalyses($stats);
    }

    /**
     * Display winning formula trends.
     *
     * @param Collection $stats
     */
    private function displayWinningFormula(Collection $stats): void
    {
        // When team rushes for 100+ yards
        $rush100Games = $stats->where('rushing_yards', '>=', 100);
        $rush100Wins = $rush100Games->where('result', 'W')->count();

        if ($rush100Games->count() >= $this->minOccurrences) {
            $this->info(sprintf(
                'When rushing for 100+ yards, the %s are %d-%d',
                $this->teamName,
                $rush100Wins,
                $rush100Games->count() - $rush100Wins
            ));
        }

        // When team passes for 250+ yards
        $pass250Games = $stats->where('passing_yards', '>=', 250);
        $pass250Wins = $pass250Games->where('result', 'W')->count();

        if ($pass250Games->count() >= $this->minOccurrences) {
            $this->info(sprintf(
                'When passing for 250+ yards, the %s are %d-%d',
                $this->teamName,
                $pass250Wins,
                $pass250Games->count() - $pass250Wins
            ));
        }
    }

    /**
     * Display total correlations.
     *
     * @param Collection $stats
     */
    private function displayTotalCorrelations(Collection $stats): void
    {
        $this->info("\n=== Over/Under Trends ===");

        // High offensive output games (350+ total yards)
        $highYardageGames = $stats->where('total_yards', '>=', 350);
        $highYardageOvers = $highYardageGames->where('total_result', 'OVER')->count();

        if ($highYardageGames->count() >= $this->minOccurrences) {
            $this->info(sprintf(
                'When gaining 350+ yards, games go OVER %d of %d times',
                $highYardageOvers,
                $highYardageGames->count()
            ));
        }

        // Rushing heavy games (125+ rushing yards)
        $rushHeavyGames = $stats->where('rushing_yards', '>=', 125);
        $rushHeavyOvers = $rushHeavyGames->where('total_result', 'OVER')->count();

        if ($rushHeavyGames->count() >= $this->minOccurrences) {
            $this->info(sprintf(
                'When rushing for 125+ yards, games go OVER %d of %d times',
                $rushHeavyOvers,
                $rushHeavyGames->count()
            ));
        }
    }

    /**
     * Display spread correlations.
     *
     * @param Collection $stats
     */
    private function displaySpreadCorrelations(Collection $stats): void
    {
        $this->info("\n=== Spread Performance by Yardage ===");

        // Spread performance when outgaining opponent
        $outgainGames = $stats->where('yard_differential', '>', 0);
        $outgainCovers = $outgainGames->where('spread_result', 'COVER')->count();

        if ($outgainGames->count() >= $this->minOccurrences) {
            $this->info(sprintf(
                'When outgaining opponent, the %s cover %d of %d times',
                $this->teamName,
                $outgainCovers,
                $outgainGames->count()
            ));
        }

        $this->analyzeSpreadRanges($stats);

        // Performance as favorite/underdog
        $favGames = $stats->where('spread', '<', 0);
        $favCovers = $favGames->where('spread_result', 'COVER')->count();

        if ($favGames->count() >= $this->minOccurrences) {
            $this->info(sprintf(
                'As a favorite, the %s cover %d of %d times',
                $this->teamName,
                $favCovers,
                $favGames->count()
            ));
        }
    }

    /**
     * Analyze spread ranges.
     *
     * @param Collection $stats
     */
    private function analyzeSpreadRanges(Collection $stats): void
    {
        $this->info("\n=== Spread Range Analysis ===");

        $ranges = [
            ['min' => -20, 'max' => -10.5, 'label' => 'Heavy Favorite (-20 to -10.5)'],
            ['min' => -10, 'max' => -3.5, 'label' => 'Moderate Favorite (-10 to -3.5)'],
            ['min' => -3, 'max' => -1, 'label' => 'Slight Favorite (-3 to -1)'],
            ['min' => 1, 'max' => 3, 'label' => 'Slight Underdog (+1 to +3)'],
            ['min' => 3.5, 'max' => 10, 'label' => 'Moderate Underdog (+3.5 to +10)'],
            ['min' => 10.5, 'max' => 20, 'label' => 'Heavy Underdog (+10.5 to +20)'],
        ];

        foreach ($ranges as $range) {
            $gamesInRange = $stats->filter(function ($item) use ($range) {
                return isset($item['spread']) &&
                    $item['spread'] >= $range['min'] &&
                    $item['spread'] <= $range['max'];
            });

            $coversInRange = $gamesInRange->where('spread_result', 'COVER')->count();

            if ($gamesInRange->count() >= $this->minOccurrences) {
                $this->info(sprintf(
                    '%s: %d-%d ATS (%d%%)',
                    $range['label'],
                    $coversInRange,
                    $gamesInRange->count() - $coversInRange,
                    $gamesInRange->count() > 0 ? round(($coversInRange / $gamesInRange->count()) * 100) : 0
                ));
            }
        }
    }

    /**
     * Display quarter-based betting trends.
     *
     * @param Collection $stats
     */
    private function displayQuarterBasedStats(Collection $stats): void
    {
        $this->info("\n=== Quarter-Based Betting Trends ===");

        // First quarter scoring after 300+ yard games
        $bigGames = $stats->where('total_yards', '>=', 300);
        $followupQ1Points = $bigGames->where('next_game_q1_points', '>', 0)->count();

        if ($bigGames->count() >= $this->minOccurrences) {
            $this->info(sprintf(
                'After 300+ yard games, the %s score in Q1 %d of %d times',
                $this->teamName,
                $followupQ1Points,
                $bigGames->count()
            ));
        }

        // Second half performance after rushing heavy games
        $rushGames = $stats->where('rushing_yards', '>=', 100);
        $followupH2Points = $rushGames->where('next_game_h2_points', '>=', 14)->count();

        if ($rushGames->count() >= $this->minOccurrences) {
            $this->info(sprintf(
                'After 100+ rushing yard games, the %s score 14+ in 2H %d of %d times',
                $this->teamName,
                $followupH2Points,
                $rushGames->count()
            ));
        }
    }

    /**
     * Display performance correlations.
     *
     * @param Collection $stats
     */
    private function displayPerformanceCorrelations(Collection $stats): void
    {
        $this->info("\n=== Performance Correlations ===");

        // Rushing Yard Correlations
        $this->analyzeTrendCorrelation(
            $stats,
            fn($item) => $item['rushing_yards'] >= 100,
            'When rushing for 100+ yards',
            ['spread_result', 'total_result', 'result']
        );

        // Passing Yard Correlations
        $this->analyzeTrendCorrelation(
            $stats,
            fn($item) => $item['passing_yards'] >= 250,
            'When passing for 250+ yards',
            ['spread_result', 'total_result', 'result']
        );

        // Yard Differential Impact
        $this->analyzeTrendCorrelation(
            $stats,
            fn($item) => $item['yard_differential'] > 50,
            'When outgaining opponent by 50+ yards',
            ['spread_result', 'result']
        );
    }

    /**
     * Analyze trend correlations.
     *
     * @param Collection $stats
     * @param callable $condition
     * @param string $description
     * @param array $outcomes
     */
    private function analyzeTrendCorrelation(Collection $stats, callable $condition, string $description, array $outcomes): void
    {
        $filtered = $stats->filter($condition);

        if ($filtered->count() >= $this->minOccurrences) {
            $this->info("\n$description:");

            foreach ($outcomes as $outcome) {
                $successCount = $filtered->filter(function ($item) use ($outcome) {
                    return isset($item[$outcome]) && in_array($item[$outcome], ['COVER', 'OVER', 'W']);
                })->count();

                $percentage = round(($successCount / $filtered->count()) * 100);

                $resultType = match ($outcome) {
                    'spread_result' => 'ATS',
                    'total_result' => 'O/U',
                    'result' => 'SU',
                    default => $outcome
                };

                $this->info(sprintf(
                    '- %s: %d-%d (%d%%)',
                    $resultType,
                    $successCount,
                    $filtered->count() - $successCount,
                    $percentage
                ));
            }
        }
    }

    /**
     * Display scoring pattern correlations.
     *
     * @param Collection $stats
     */
    private function displayScoringPatternCorrelations(Collection $stats): void
    {
        $this->info("\n=== Scoring Pattern Analysis ===");

        // First Quarter Scoring Impact
        $firstQuarterScoring = $stats->where('next_game_q1_points', '>', 0);

        if ($firstQuarterScoring->count() >= $this->minOccurrences) {
            $spreads = $firstQuarterScoring->where('spread_result', 'COVER')->count();
            $this->info(sprintf(
                'After scoring in Q1: Covers %d of %d spreads',
                $spreads,
                $firstQuarterScoring->count()
            ));
        }

        // Second Half Performance
        $strongSecondHalf = $stats->where('next_game_h2_points', '>=', 14);

        if ($strongSecondHalf->count() >= $this->minOccurrences) {
            $overs = $strongSecondHalf->where('total_result', 'OVER')->count();
            $this->info(sprintf(
                'After scoring 14+ in 2H: Overs hit %d of %d games',
                $overs,
                $strongSecondHalf->count()
            ));
        }
    }

    /**
     * Display situational trends.
     *
     * @param Collection $stats
     */
    private function displaySituationalTrends(Collection $stats): void
    {
        $this->info("\n=== Situational Analysis ===");

        // As Favorite vs Underdog
        $asFavorite = $stats->where('spread', '<', 0);
        $asUnderdog = $stats->where('spread', '>', 0);

        if ($asFavorite->count() >= $this->minOccurrences) {
            $favCovers = $asFavorite->where('spread_result', 'COVER')->count();
            $this->info(sprintf(
                'As Favorite: %d-%d ATS (%d%%)',
                $favCovers,
                $asFavorite->count() - $favCovers,
                round(($favCovers / $asFavorite->count()) * 100)
            ));
        }

        if ($asUnderdog->count() >= $this->minOccurrences) {
            $dogCovers = $asUnderdog->where('spread_result', 'COVER')->count();
            $this->info(sprintf(
                'As Underdog: %d-%d ATS (%d%%)',
                $dogCovers,
                $asUnderdog->count() - $dogCovers,
                round(($dogCovers / $asUnderdog->count()) * 100)
            ));
        }

        // Post-Win/Loss Performance
        $this->analyzePostResultTrends($stats);
    }

    // --------------------------------------------
    // Helper Methods
    // --------------------------------------------

    /**
     * Analyze post result trends.
     *
     * @param Collection $stats
     */
    private function analyzePostResultTrends(Collection $stats): void
    {
        // After Win Performance
        $afterWin = $stats->where('previous_result', 'W');

        if ($afterWin->count() >= $this->minOccurrences) {
            $nextCovers = $afterWin->where('spread_result', 'COVER')->count();
            $nextOvers = $afterWin->where('total_result', 'OVER')->count();

            $this->info(sprintf(
                'After Win: %d-%d ATS, %d-%d O/U',
                $nextCovers,
                $afterWin->count() - $nextCovers,
                $nextOvers,
                $afterWin->count() - $nextOvers
            ));
        }

        // After Loss Performance
        $afterLoss = $stats->where('previous_result', 'L');

        if ($afterLoss->count() >= $this->minOccurrences) {
            $nextCovers = $afterLoss->where('spread_result', 'COVER')->count();
            $nextOvers = $afterLoss->where('total_result', 'OVER')->count();

            $this->info(sprintf(
                'After Loss: %d-%d ATS, %d-%d O/U',
                $nextCovers,
                $afterLoss->count() - $nextCovers,
                $nextOvers,
                $afterLoss->count() - $nextOvers
            ));
        }
    }

    /**
     * Display momentum-based trends.
     *
     * @param Collection $stats
     */
    private function displayMomentumTrends(Collection $stats): void
    {
        $this->info("\n=== Momentum-Based Trends ===");

        // After 300+ total yards
        $after300TotalYards = $stats->where('previous_total_yards', '>=', 300);

        if ($after300TotalYards->count() >= $this->minOccurrences) {
            $nextOvers = $after300TotalYards->where('total_result', 'OVER')->count();
            $this->info(sprintf(
                'After 300+ total yards: Overs hit %d of %d times',
                $nextOvers,
                $after300TotalYards->count()
            ));
        }

        // After 100+ rushing yards
        $after100RushingYards = $stats->where('previous_rushing_yards', '>=', 100);

        if ($after100RushingYards->count() >= $this->minOccurrences) {
            $nextOvers = $after100RushingYards->where('total_result', 'OVER')->count();
            $this->info(sprintf(
                'After 100+ rushing yards: Overs hit %d of %d times',
                $nextOvers,
                $after100RushingYards->count()
            ));
        }

        // After 250+ passing yards
        $after250PassingYards = $stats->where('previous_passing_yards', '>=', 250);

        if ($after250PassingYards->count() >= $this->minOccurrences) {
            $nextOvers = $after250PassingYards->where('total_result', 'OVER')->count();
            $this->info(sprintf(
                'After 250+ passing yards: Overs hit %d of %d times',
                $nextOvers,
                $after250PassingYards->count()
            ));
        }
    }

    /**
     * Perform additional analyses.
     *
     * @param Collection $stats
     */
    private function performAdditionalAnalyses(Collection $stats): void
    {
        $this->analyzePerfectATS($stats, [
            ['points', '>=', 24, 'When scoring 24+ points'],
            ['rushing_yards', '>=', 150, 'With 150+ rushing yards'],
            ['passing_yards', '>=', 250, 'With 250+ passing yards'],
        ]);

        $this->analyzeQuarterConsistency($stats);
        $this->analyzePointsAfterYardage($stats);
        $this->analyzeQuarterSuccess($stats);
        $this->analyzeScoringThresholds($stats);
        $this->analyzeYardageThresholds($stats);
    }

    /**
     * Analyze perfect ATS conditions.
     *
     * @param Collection $stats
     * @param array $conditions
     */
    private function analyzePerfectATS(Collection $stats, array $conditions): void
    {
        foreach ($conditions as $condition) {
            [$field, $operator, $value, $description] = $condition;

            $filtered = $stats->filter(fn($item) => isset($item[$field]) &&
                $this->compareValues($item[$field], $operator, $value)
            );

            if ($filtered->count() >= $this->minOccurrences) {
                $covers = $filtered->where('spread_result', 'COVER')->count();

                if ($covers === $filtered->count()) {
                    $this->info(sprintf(
                        '%s: %d-%d ATS (Perfect)',
                        $description,
                        $covers,
                        $filtered->count() - $covers
                    ));
                }
            }
        }
    }

    /**
     * Compare two values with the given operator.
     *
     * @param mixed $a
     * @param string $operator
     * @param mixed $b
     * @return bool
     */
    private function compareValues($a, string $operator, $b): bool
    {
        return match ($operator) {
            '>' => $a > $b,
            '>=' => $a >= $b,
            '<' => $a < $b,
            '<=' => $a <= $b,
            '=' => $a == $b,
            default => false,
        };
    }

    /**
     * Analyze quarter consistency.
     *
     * @param Collection $stats
     */
    private function analyzeQuarterConsistency(Collection $stats): void
    {
        foreach (self::QUARTERS as $quarter) {
            $scoringGames = $stats->filter(fn($item) => isset($item["{$quarter}_points"]) &&
                $item["{$quarter}_points"] > 0
            );

            if ($scoringGames->count() >= $this->minOccurrences) {
                $covers = $scoringGames->where('spread_result', 'COVER')->count();
                $overs = $scoringGames->where('total_result', 'OVER')->count();

                $this->info(sprintf(
                    'When scoring in %s: %d-%d ATS, %d-%d O/U',
                    $quarter,
                    $covers,
                    $scoringGames->count() - $covers,
                    $overs,
                    $scoringGames->count() - $overs
                ));
            }
        }
    }

    /**
     * Analyze points after yardage thresholds.
     *
     * @param Collection $stats
     */
    private function analyzePointsAfterYardage(Collection $stats): void
    {
        $thresholds = [
            300 => 'After 300+ total yards',
            400 => 'After 400+ total yards',
            150 => 'After 150+ rushing yards',
            250 => 'After 250+ passing yards',
        ];

        foreach ($thresholds as $yards => $description) {
            $games = $stats->filter(fn($item) => isset($item['total_yards']) &&
                $item['total_yards'] >= $yards
            );

            if ($games->count() >= $this->minOccurrences) {
                $nextGamePoints = $games->avg('next_game_points');

                if ($nextGamePoints !== null) {
                    $this->info(sprintf(
                        '%s: Averages %.1f points in next game',
                        $description,
                        $nextGamePoints
                    ));
                } else {
                    $this->info(sprintf(
                        '%s: No data available',
                        $description
                    ));
                }
            } else {
                $this->info(sprintf(
                    '%s: No data available',
                    $description
                ));
            }
        }
    }

    /**
     * Analyze quarter success.
     *
     * @param Collection $stats
     */
    private function analyzeQuarterSuccess(Collection $stats): void
    {
        foreach (self::QUARTERS as $quarter) {
            $scoreInQuarter = $stats->filter(fn($item) => isset($item["{$quarter}_points"]) &&
                $item["{$quarter}_points"] > 0
            );

            if ($scoreInQuarter->count() >= $this->minOccurrences) {
                $wins = $scoreInQuarter->where('result', 'W')->count();
                $covers = $scoreInQuarter->where('spread_result', 'COVER')->count();

                $this->info(sprintf(
                    'When scoring in %s: %d-%d SU, %d-%d ATS',
                    $quarter,
                    $wins,
                    $scoreInQuarter->count() - $wins,
                    $covers,
                    $scoreInQuarter->count() - $covers
                ));
            }
        }
    }

    /**
     * Analyze scoring thresholds.
     *
     * @param Collection $stats
     */
    private function analyzeScoringThresholds(Collection $stats): void
    {
        $thresholds = [20, 24, 27, 30];

        foreach ($thresholds as $points) {
            $highScoring = $stats->filter(fn($item) => isset($item['points']) &&
                $item['points'] >= $points
            );

            if ($highScoring->count() >= $this->minOccurrences) {
                $covers = $highScoring->where('spread_result', 'COVER')->count();
                $overs = $highScoring->where('total_result', 'OVER')->count();

                $this->info(sprintf(
                    'When scoring %d+ points: %d-%d ATS, %d-%d O/U',
                    $points,
                    $covers,
                    $highScoring->count() - $covers,
                    $overs,
                    $highScoring->count() - $overs
                ));
            }
        }
    }

    /**
     * Analyze yardage thresholds.
     *
     * @param Collection $stats
     */
    private function analyzeYardageThresholds(Collection $stats): void
    {
        $thresholds = [
            'total_yards' => [300, 350, 400],
            'rushing_yards' => [100, 125, 150],
            'passing_yards' => [200, 250, 300],
        ];

        foreach ($thresholds as $type => $values) {
            foreach ($values as $yards) {
                $games = $stats->filter(fn($item) => isset($item[$type]) &&
                    $item[$type] >= $yards
                );

                if ($games->count() >= $this->minOccurrences) {
                    $wins = $games->where('result', 'W')->count();
                    $covers = $games->where('spread_result', 'COVER')->count();
                    $overs = $games->where('total_result', 'OVER')->count();

                    $this->info(sprintf(
                        'With %d+ %s: %d-%d SU, %d-%d ATS, %d-%d O/U',
                        $yards,
                        str_replace('_', ' ', $type),
                        $wins,
                        $games->count() - $wins,
                        $covers,
                        $games->count() - $covers,
                        $overs,
                        $games->count() - $overs
                    ));
                }
            }
        }
    }

    /**
     * Display analysis parameters.
     *
     * @param int $totalGames
     */
    private function displayAnalysisParameters(int $totalGames): void
    {
        $this->info("\nAnalysis Parameters:");
        $this->info("- Games analyzed: $totalGames");
        $this->info("- Minimum occurrences: {$this->minOccurrences}");

        if ($season = $this->option('season')) {
            $this->info("- Season: $season");
        }
    }

}
