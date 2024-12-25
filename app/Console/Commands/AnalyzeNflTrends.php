<?php

namespace App\Console\Commands;

use App\Models\Nfl\NflBettingOdds;
use App\Models\Nfl\NflBoxScore;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AnalyzeNflTrends extends Command
{
    private const QUARTERS = ['Q1', 'Q2', 'Q3', 'Q4'];
    private const HALVES = [
        'first' => ['Q1', 'Q2'],
        'second' => ['Q3', 'Q4'],
    ];

    // Configure analyses in a single array for easy addition/removal
    private const ANALYSES = [
        'scoring' => [
            'thresholds' => [20, 24, 30],
            'message' => 'have scored over %d points in %d of their last %d games'
        ],
        'quarter' => [
            'score_thresholds' => [
                ['threshold' => 7, 'message' => 'have scored 7+ points in %s in'],
                ['threshold' => 0, 'message' => 'have scored in %s in']
            ],
            'win_message' => 'have won %s in'
        ],
        'half' => [
            'score_thresholds' => [
                ['min' => 14, 'message' => 'have scored 14+ points'],
                ['min' => 7, 'max' => 13, 'message' => 'have scored 7-13 points'],
                ['max' => 7, 'message' => 'have scored fewer than 7 points']
            ],
            'win_message' => 'have won the %s half'
        ],
        'margin' => [
            'thresholds' => [
                ['min' => 10, 'message' => 'have won by 10+ points'],
                ['min' => -3, 'max' => 3, 'message' => 'have played games decided by 3 or fewer points']
            ]
        ],
        'totals' => [
            'thresholds' => [
                ['min' => 50, 'message' => 'have been in games with 50+ total points'],
                ['max' => 40, 'message' => 'have been in games with 40 or fewer total points']
            ],
            'over_message' => 'games have gone OVER'
        ]
    ];

    protected $signature = 'analyze:nfl-trends 
                        {team : Team abbreviation or name}
                        {--season= : Specific season to analyze}
                        {--week= : Analyze trends up to this week (exclusive)}
                        {--min-occurrences=2 : Minimum occurrences for a trend}
                        {--games=20 : Number of games to analyze}';


    protected $description = 'Analyze NFL betting trends for a specific team';

    private Collection $games;
    private Collection $bettingOdds;
    private string $teamName;
    private int $minOccurrences;
    private array $trends = [];

    public function handle(): int
    {
        try {
            $this->analyzeTrends();
            $this->displayResults();
            return 0;
        } catch (Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    private function analyzeTrends(): void
    {
        foreach ($this->games as $game) {
            $this->collectGameData($game);
        }
    }

    private function collectGameData(NflBoxScore $game): void
    {
        $isHome = $game->home_team === $this->teamName;
        $this->analyzeSpread($game, $isHome);
        $this->analyzeScoring($game, $isHome);
        $this->analyzeQuarters($game, $isHome);
        $this->analyzeMargins($game, $isHome);
        $this->analyzeTotals($game);
        $this->analyzeFirstScore($game, $isHome);
    }

    private function analyzeSpread(NflBoxScore $game, bool $isHome): void
    {
        $odds = $this->bettingOdds->get($game->game_id);
        if (!$odds) return;

        $spread = $isHome ? $odds->spread_home : $odds->spread_away;
        $actualDiff = $this->calculateMargin($game, $isHome);

        $covered = $spread < 0 ?
            $actualDiff > abs($spread) :
            $actualDiff > -$spread;

        $this->trends['spread_cover'][] = [
            'covered' => $covered,
            'spread' => $spread,
            'margin' => $actualDiff,
            'game_id' => $game->game_id
        ];
    }

    private function calculateMargin(NflBoxScore $game, bool $isHome): float
    {
        $diff = $game->home_points - $game->away_points;
        return $isHome ? $diff : -$diff;
    }

    // Rest of the methods remain largely the same, just streamlined...
    // I can continue with the rest of the implementation if you'd like

    private function analyzeScoring(NflBoxScore $game, bool $isHome): void
    {
        $teamScore = $isHome ? $game->home_points : $game->away_points;

        $this->trends['scoring'][] = [
            'points' => $teamScore,
            'game_id' => $game->game_id
        ];
    }

    private function analyzeQuarters(NflBoxScore $game, bool $isHome): void
    {
        $lineScore = $isHome ? $game->home_line_score : $game->away_line_score;
        $oppLineScore = $isHome ? $game->away_line_score : $game->home_line_score;

        if (!$lineScore || !$oppLineScore) return;

        foreach (self::QUARTERS as $quarter) {
            $teamScore = (int)($lineScore[$quarter] ?? 0);
            $oppScore = (int)($oppLineScore[$quarter] ?? 0);

            $this->trends['quarter'][] = [
                'quarter' => $quarter,
                'team_score' => $teamScore,
                'opp_score' => $oppScore,
                'won_quarter' => $teamScore > $oppScore,
                'game_id' => $game->game_id
            ];
        }

        // Analyze halves
        foreach (self::HALVES as $half => $quarters) {
            $teamHalfScore = array_sum(array_map(fn($q) => (int)($lineScore[$q] ?? 0), $quarters));
            $oppHalfScore = array_sum(array_map(fn($q) => (int)($oppLineScore[$q] ?? 0), $quarters));

            $this->trends['half'][] = [
                'half' => $half,
                'team_score' => $teamHalfScore,
                'opp_score' => $oppHalfScore,
                'won_half' => $teamHalfScore > $oppHalfScore,
                'game_id' => $game->game_id
            ];
        }
    }

    private function analyzeMargins(NflBoxScore $game, bool $isHome): void
    {
        $margin = $this->calculateMargin($game, $isHome);

        $this->trends['margin'][] = [
            'margin' => $margin,
            'is_win' => $margin > 0,
            'game_id' => $game->game_id
        ];
    }

    private function analyzeTotals(NflBoxScore $game): void
    {
        $odds = $this->bettingOdds->get($game->game_id);
        if (!$odds) return;

        $totalPoints = $game->home_points + $game->away_points;

        $this->trends['totals'][] = [
            'total_points' => $totalPoints,
            'over_under' => $odds->total_over ?? 0,
            'went_over' => $totalPoints > ($odds->total_over ?? 0),
            'game_id' => $game->game_id
        ];
    }

    private function analyzeFirstScore(NflBoxScore $game, bool $isHome): void
    {
        $lineScore = $isHome ? $game->home_line_score : $game->away_line_score;
        $oppLineScore = $isHome ? $game->away_line_score : $game->home_line_score;

        if (!$lineScore || !$oppLineScore) return;

        foreach (self::QUARTERS as $quarter) {
            $teamScore = (int)($lineScore[$quarter] ?? 0);
            $oppScore = (int)($oppLineScore[$quarter] ?? 0);

            if ($teamScore > 0 || $oppScore > 0) {
                $this->trends['first_score'][] = [
                    'scoring_quarter' => $quarter,
                    'scored_first' => $teamScore > 0 && ($oppScore === 0 || $teamScore > $oppScore),
                    'game_id' => $game->game_id
                ];
                break;
            }
        }
    }

    private function displayResults(): void
    {
        $totalGames = $this->games->count();
        $this->info("\nAnalyzing trends for {$this->teamName}");

        $this->displayGeneralTrends($totalGames);
        $this->displaySpreadTrends($totalGames);
        $this->displayConfiguredTrends($totalGames);
        $this->displayFirstScoreTrends($totalGames);
    }

    private function displayGeneralTrends(int $totalGames): void
    {
        $this->info("\n=== General Trends ===");

        // Win/Loss Record
        $wins = collect($this->trends['margin'])->where('is_win', true)->count();

        // ATS Record
        $covers = collect($this->trends['spread_cover'])->where('covered', true)->count();

        // Over/Under Record
        $overs = collect($this->trends['totals'])->where('went_over', true)->count();

        foreach ([
                     ['Record', $wins],
                     ['Against The Spread', $covers],
                     ['Over/Under', $overs]
                 ] as [$label, $count]) {
            $this->info(sprintf(
                '%s: %d-%d (%d%%)',
                $label,
                $count,
                $totalGames - $count,
                round(($count / $totalGames) * 100)
            ));
        }
    }

    private function displaySpreadTrends(int $totalGames): void
    {
        $covers = collect($this->trends['spread_cover'])->where('covered', true)->count();

        if ($covers >= $this->minOccurrences) {
            $this->info(sprintf(
                'The %s have covered the spread in %d of their last %d games',
                $this->teamName, $covers, $totalGames

            ));

        }
    }


    private function displayConfiguredTrends(int $totalGames): void
    {
        foreach (self::ANALYSES as $type => $config) {
            $trends = collect($this->trends[$type]);

            if ($type === 'quarter') {
                $this->displayQuarterTrends($trends, $totalGames);
            } elseif ($type === 'half') {
                $this->displayHalfTrends($trends, $totalGames);
            } elseif ($type === 'margin') {
                $this->displayMarginTrends($trends, $totalGames);
            } elseif ($type === 'totals') {
                $this->displayTotalsTrends($trends, $totalGames);
            } elseif ($type === 'scoring') {
                $this->displayScoringTrends($trends, $totalGames);
            }
        }
    }

    private function displayQuarterTrends(Collection $trends, int $totalGames): void
    {
        $this->info("\n== Quarter Performance ==");

        foreach (self::QUARTERS as $quarter) {
            $quarterData = $trends->where('quarter', $quarter);
            $this->displayQuarterStats($quarter, $quarterData, $totalGames);
        }
    }

    private function displayQuarterStats(string $quarter, Collection $data, int $totalGames): void
    {
        $quarterWins = $data->where('won_quarter', true)->count();
        if ($quarterWins >= $this->minOccurrences) {
            $this->info(sprintf(
                'The %s %s %d of their last %d games',
                $this->teamName,
                sprintf(self::ANALYSES['quarter']['win_message'], $quarter),
                $quarterWins,
                $totalGames
            ));
        }

        foreach (self::ANALYSES['quarter']['score_thresholds'] as $threshold) {
            $count = $data->where('team_score', '>', $threshold['threshold'])->count();
            if ($count >= $this->minOccurrences) {
                $this->info(sprintf(
                    'The %s %s %d of their last %d games',
                    $this->teamName,
                    sprintf($threshold['message'], $quarter),
                    $count,
                    $totalGames
                ));
            }
        }
    }

    private function displayHalfTrends(Collection $trends, int $totalGames): void
    {
        $this->info("\n== Half Performance ==");

        foreach (array_keys(self::HALVES) as $half) {
            $halfData = $trends->where('half', $half);
            $this->displayHalfStats($half, $halfData, $totalGames);
        }
    }

    private function displayHalfStats(string $half, Collection $data, int $totalGames): void
    {
        $halfWins = $data->where('won_half', true)->count();
        if ($halfWins >= $this->minOccurrences) {
            $this->info(sprintf(
                'The %s %s in %d of their last %d games',
                $this->teamName,
                sprintf(self::ANALYSES['half']['win_message'], $half),
                $halfWins,
                $totalGames
            ));
        }

        foreach (self::ANALYSES['half']['score_thresholds'] as $threshold) {
            $count = $data;

            if (isset($threshold['min']) && isset($threshold['max'])) {
                $count = $count->whereBetween('team_score', [$threshold['min'], $threshold['max']]);
            } elseif (isset($threshold['min'])) {
                $count = $count->where('team_score', '>=', $threshold['min']);
            } else {
                $count = $count->where('team_score', '<', $threshold['max']);
            }

            $count = $count->count();

            if ($count >= $this->minOccurrences) {
                $this->info(sprintf(
                    'The %s %s in the %s half in %d of their last %d games',
                    $this->teamName,
                    $threshold['message'],
                    $half,
                    $count,
                    $totalGames
                ));
            }
        }
    }

    private function displayMarginTrends(Collection $trends, int $totalGames): void
    {
        $this->info("\n== Margin Trends ==");

        foreach (self::ANALYSES['margin']['thresholds'] as $threshold) {
            $count = $trends;

            if (isset($threshold['min']) && isset($threshold['max'])) {
                $count = $count->whereBetween('margin', [$threshold['min'], $threshold['max']]);
            } elseif (isset($threshold['min'])) {
                $count = $count->where('margin', '>=', $threshold['min']);
            } elseif (isset($threshold['max'])) {
                $count = $count->where('margin', '<=', $threshold['max']);
            }

            $count = $count->count();

            if ($count >= $this->minOccurrences) {
                $this->info(sprintf(
                    'The %s %s in %d of their last %d games',
                    $this->teamName,
                    $threshold['message'],
                    $count,
                    $totalGames
                ));
            }
        }
    }

    private function displayTotalsTrends(Collection $trends, int $totalGames): void
    {
        $this->info("\n== Game Totals Trends ==");

        $overs = $trends->where('went_over', true)->count();
        if ($overs >= $this->minOccurrences) {
            $this->info(sprintf(
                'The %s %s in %d of their last %d games',
                $this->teamName,
                self::ANALYSES['totals']['over_message'],
                $overs,
                $totalGames
            ));
        }

        foreach (self::ANALYSES['totals']['thresholds'] as $threshold) {
            $count = $trends;

            if (isset($threshold['min'])) {
                $count = $count->where('total_points', '>=', $threshold['min']);
            } else {
                $count = $count->where('total_points', '<=', $threshold['max']);
            }

            $count = $count->count();

            if ($count >= $this->minOccurrences) {
                $this->info(sprintf(
                    'The %s %s in %d of their last %d games',
                    $this->teamName,
                    $threshold['message'],
                    $count,
                    $totalGames
                ));
            }
        }
    }

    private function displayScoringTrends(Collection $trends, int $totalGames): void
    {
        foreach (self::ANALYSES['scoring']['thresholds'] as $threshold) {
            $count = $trends->where('points', '>', $threshold)->count();

            if ($count >= $this->minOccurrences) {
                $this->info(sprintf(
                    'The %s ' . self::ANALYSES['scoring']['message'],
                    $this->teamName, $threshold, $count, $totalGames
                ));
            }
        }
    }

    private function displayFirstScoreTrends(int $totalGames): void
    {
        $this->info("\n== First Score Trends ==");
        $scores = collect($this->trends['first_score']);

        $scoredFirst = $scores->where('scored_first', true)->count();
        if ($scoredFirst >= $this->minOccurrences) {
            $this->info(sprintf(
                'The %s have scored first in %d of their last %d games',
                $this->teamName, $scoredFirst, $totalGames
            ));
        }

        foreach (self::QUARTERS as $quarter) {
            $count = $scores->where('scoring_quarter', $quarter)->count();
            if ($count >= $this->minOccurrences) {
                $this->info(sprintf(
                    'The %s had their first score in %s in %d of their last %d games',
                    $this->teamName, $quarter, $count, $totalGames
                ));
            }
        }
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->setupAnalysis();
    }

    private function setupAnalysis(): void
    {
        $this->teamName = strtoupper($this->argument('team'));
        $this->minOccurrences = (int)$this->option('min-occurrences');
        $this->games = $this->fetchGames();
        $this->bettingOdds = $this->fetchBettingOdds();

        if ($this->games->isEmpty()) {
            throw new Exception("No games found for {$this->teamName}");
        }

        // Initialize trends array
        foreach (array_keys(self::ANALYSES) as $type) {
            $this->trends[$type] = [];
        }
        $this->trends['spread_cover'] = [];
        $this->trends['first_score'] = [];
    }

    private function fetchGames(): Collection
    {
        $query = NflBoxScore::query()
            ->join('nfl_team_schedules', function ($join) {
                $join->on('nfl_box_scores.game_id', '=', 'nfl_team_schedules.game_id')
                    ->where('nfl_team_schedules.season_type', 'Regular Season');
            })
            ->where(function ($q) {
                $q->where('nfl_box_scores.home_team', $this->teamName)
                    ->orWhere('nfl_box_scores.away_team', $this->teamName);
            })
            ->with('teamStats')
            ->orderBy('nfl_box_scores.game_date', 'desc')
            ->select('nfl_box_scores.*');

        if ($season = $this->option('season')) {
            $query->whereYear('nfl_box_scores.game_date', $season);
        }

        // Filter games prior to the specified week
        if ($week = $this->option('week')) {
            if (!is_numeric($week) || $week < 1 || $week > 17) { // Adjust max week as needed
                throw new Exception("Invalid week number '{$week}'. Please provide a week between 1 and 17.");
            }
            // Cast game_week to unsigned integer for accurate comparison
            $query->whereRaw('CAST(nfl_team_schedules.game_week AS UNSIGNED) < ?', [$week]);
        }

        // Limit the number of games if 'week' is not provided
        if (!$this->option('week')) {
            $query->take($this->option('games'));
        }

        return $query->get();
    }

    private function fetchBettingOdds(): Collection
    {
        return NflBettingOdds::whereIn('event_id', $this->games->pluck('game_id'))
            ->get()
            ->keyBy('event_id');
    }


    // ... Additional display methods would follow the same pattern
}