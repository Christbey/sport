<?php

namespace App\Http\Controllers;

use App\Models\Nfl\NflBettingOdds;
use App\Models\Nfl\NflBoxScore;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class NflTrendsController extends Controller
{
    private const QUARTERS = ['Q1', 'Q2', 'Q3', 'Q4'];
    private const HALVES = [
        'first' => ['Q1', 'Q2'],
        'second' => ['Q3', 'Q4'],
    ];

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

    private Collection $games;
    private Collection $bettingOdds;
    private string $teamName;
    private int $minOccurrences = 2;
    private array $trends = [];

    public function show(Request $request): View
    {
        // If no team is selected, just show the form
        if (!$request->has('team') || empty($request->input('team'))) {
            return view('nfl.trends.config');
        }

        $request->validate([
            'team' => 'required|string',
            'season' => 'nullable|integer',
            'games' => 'nullable|integer|min:1|max:100',
        ]);

        $this->teamName = strtoupper($request->input('team'));

        // Add error handling for invalid team
        if (!in_array($this->teamName, [
            'ARI', 'ATL', 'BAL', 'BUF', 'CAR', 'CHI', 'CIN', 'CLE',
            'DAL', 'DEN', 'DET', 'GB', 'HOU', 'IND', 'JAX', 'KC',
            'LAC', 'LAR', 'LV', 'MIA', 'MIN', 'NE', 'NO', 'NYG',
            'NYJ', 'PHI', 'PIT', 'SEA', 'SF', 'TB', 'TEN', 'WAS'
        ])) {
            return view('nfl.trends.config')->withErrors(['Invalid team selected']);
        }

        $this->games = $this->fetchGames($request);
        $this->bettingOdds = $this->fetchBettingOdds();

        if ($this->games->isEmpty()) {
            return view('nfl.trends.config')->withErrors(['No games found for ' . $this->teamName]);
        }

        $this->initializeTrends();
        $this->analyzeTrends();
        $trends = $this->formatTrends();

        return view('nfl.trends.config', [
            'selectedTeam' => $this->teamName,
            'trends' => $trends,
            'totalGames' => $this->games->count(),
            'season' => $request->input('season'),
            'games' => $request->input('games', 10)
        ]);
    }

    private function fetchGames(Request $request): Collection
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

        if ($season = $request->input('season')) {
            $query->whereYear('nfl_box_scores.game_date', $season);
        }

        return $query->take($request->input('games', 20))->get();
    }

    private function fetchBettingOdds(): Collection
    {
        return NflBettingOdds::whereIn('event_id', $this->games->pluck('game_id'))
            ->get()
            ->keyBy('event_id');
    }

    private function initializeTrends(): void
    {
        foreach (array_keys(self::ANALYSES) as $type) {
            $this->trends[$type] = [];
        }
        $this->trends['spread_cover'] = [];
        $this->trends['first_score'] = [];
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

    // Include all the analysis methods from your command...
    // [analyzeSpread, analyzeScoring, analyzeQuarters, etc.]

    private function analyzeSpread(NflBoxScore $game, bool $isHome): void
    {
        $odds = $this->bettingOdds->get($game->game_id);
        if (!$odds) return;

        $spread = $isHome ? $odds->spread_home : $odds->spread_away;
        $actualDiff = $this->calculateMargin($game, $isHome);

        // Check for push - when actual difference equals the spread exactly
        $covered = null;
        if ($spread) {
            if ($spread < 0) {
                // Team is favorite
                if ($actualDiff > abs($spread)) {
                    $covered = true;  // Covered as favorite
                } elseif ($actualDiff < abs($spread)) {
                    $covered = false; // Did not cover as favorite
                }
                // If equal, it's a push ($covered remains null)
            } else {
                // Team is underdog
                if ($actualDiff > -$spread) {
                    $covered = true;  // Covered as underdog
                } elseif ($actualDiff < -$spread) {
                    $covered = false; // Did not cover as underdog
                }
                // If equal, it's a push ($covered remains null)
            }
        }

        // Add detailed tracking for debugging
        $this->trends['spread_cover'][] = [
            'covered' => $covered,
            'spread' => $spread,
            'margin' => $actualDiff,
            'game_id' => $game->game_id,
            'game_date' => $game->game_date,
            'home_team' => $game->home_team,
            'away_team' => $game->away_team,
            'home_points' => $game->home_points,
            'away_points' => $game->away_points,
            'is_home' => $isHome,
            'is_favorite' => $spread < 0,
            'is_push' => $covered === null
        ];
    }

    private function calculateMargin(NflBoxScore $game, bool $isHome): float
    {
        $diff = $game->home_points - $game->away_points;
        return $isHome ? $diff : -$diff;
    }


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

    // Rest of the methods remain largely the same, just streamlined...
    // I can continue with the rest of the implementation if you'd like

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


    private function formatTrends(): array
    {
        $totalGames = $this->games->count();
        $formattedTrends = [
            'general' => $this->formatGeneralTrends($totalGames),
            'scoring' => $this->formatScoringTrends($totalGames),
            'quarters' => $this->formatQuarterTrends($totalGames),
            'halves' => $this->formatHalfTrends($totalGames),
            'margins' => $this->formatMarginTrends($totalGames),
            'totals' => $this->formatTotalsTrends($totalGames),
            'first_score' => $this->formatFirstScoreTrends($totalGames)
        ];

        return $formattedTrends;
    }

    private function formatGeneralTrends(int $totalGames): array
    {
        $wins = collect($this->trends['margin'])->where('is_win', true)->count();
        $spreadData = collect($this->trends['spread_cover']);

        // Filter out pushes
        $validBets = $spreadData->filter(fn($bet) => $bet['covered'] !== null);
        $covers = $validBets->where('covered', true)->count();
        $totalValidBets = $validBets->count();
        $pushes = $totalGames - $totalValidBets;

        $overs = collect($this->trends['totals'])->where('went_over', true)->count();

        return [
            'record' => [
                'wins' => $wins,
                'losses' => $totalGames - $wins,
                'percentage' => round(($wins / $totalGames) * 100)
            ],
            'ats' => [
                'wins' => $covers,
                'losses' => $totalValidBets - $covers,
                'pushes' => $pushes,
                'percentage' => $totalValidBets ? round(($covers / $totalValidBets) * 100) : 0
            ],
            'over_under' => [
                'overs' => $overs,
                'unders' => $totalGames - $overs,
                'percentage' => round(($overs / $totalGames) * 100)
            ]
        ];
    }

    private function formatScoringTrends(int $totalGames): array
    {
        $trends = [];
        foreach (self::ANALYSES['scoring']['thresholds'] as $threshold) {
            $count = collect($this->trends['scoring'])
                ->where('points', '>', $threshold)
                ->count();

            if ($count >= $this->minOccurrences) {
                $trends[] = sprintf(
                    self::ANALYSES['scoring']['message'],
                    $threshold,
                    $count,
                    $totalGames
                );
            }
        }
        return $trends;
    }

    private function formatQuarterTrends(int $totalGames): array
    {
        $trends = [];
        $quarterData = collect($this->trends['quarter']);

        foreach (self::QUARTERS as $quarter) {
            $quarterStats = $quarterData->where('quarter', $quarter);
            $quarterWins = $quarterStats->where('won_quarter', true)->count();

            if ($quarterWins >= $this->minOccurrences) {
                $trends[] = sprintf(
                    'The %s %s %d of their last %d games',
                    $this->teamName,
                    sprintf(self::ANALYSES['quarter']['win_message'], $quarter),
                    $quarterWins,
                    $totalGames
                );
            }

            foreach (self::ANALYSES['quarter']['score_thresholds'] as $threshold) {
                $count = $quarterStats->where('team_score', '>', $threshold['threshold'])->count();
                if ($count >= $this->minOccurrences) {
                    $trends[] = sprintf(
                        'The %s %s %d of their last %d games',
                        $this->teamName,
                        sprintf($threshold['message'], $quarter),
                        $count,
                        $totalGames
                    );
                }
            }
        }
        return $trends;
    }

    private function formatHalfTrends(int $totalGames): array
    {
        $trends = [];
        $halfData = collect($this->trends['half']);

        foreach (array_keys(self::HALVES) as $half) {
            $halfStats = $halfData->where('half', $half);
            $halfWins = $halfStats->where('won_half', true)->count();

            if ($halfWins >= $this->minOccurrences) {
                $trends[] = sprintf(
                    'The %s have won the %s half in %d of their last %d games',
                    $this->teamName,
                    $half,
                    $halfWins,
                    $totalGames
                );
            }

            foreach (self::ANALYSES['half']['score_thresholds'] as $threshold) {
                $filteredStats = $halfStats;
                if (isset($threshold['min']) && isset($threshold['max'])) {
                    $filteredStats = $filteredStats->whereBetween('team_score', [$threshold['min'], $threshold['max']]);
                } elseif (isset($threshold['min'])) {
                    $filteredStats = $filteredStats->where('team_score', '>=', $threshold['min']);
                } else {
                    $filteredStats = $filteredStats->where('team_score', '<', $threshold['max']);
                }

                $count = $filteredStats->count();
                if ($count >= $this->minOccurrences) {
                    $trends[] = sprintf(
                        'The %s %s in the %s half in %d of their last %d games',
                        $this->teamName,
                        $threshold['message'],
                        $half,
                        $count,
                        $totalGames
                    );
                }
            }
        }
        return $trends;
    }

    private function formatMarginTrends(int $totalGames): array
    {
        $trends = [];
        $marginData = collect($this->trends['margin']);

        foreach (self::ANALYSES['margin']['thresholds'] as $threshold) {
            $filteredData = $marginData;
            if (isset($threshold['min']) && isset($threshold['max'])) {
                $filteredData = $filteredData->whereBetween('margin', [$threshold['min'], $threshold['max']]);
            } elseif (isset($threshold['min'])) {
                $filteredData = $filteredData->where('margin', '>=', $threshold['min']);
            } else {
                $filteredData = $filteredData->where('margin', '<=', $threshold['max']);
            }

            $count = $filteredData->count();
            if ($count >= $this->minOccurrences) {
                $trends[] = sprintf(
                    'The %s %s in %d of their last %d games',
                    $this->teamName,
                    $threshold['message'],
                    $count,
                    $totalGames
                );
            }
        }
        return $trends;
    }

    private function formatTotalsTrends(int $totalGames): array
    {
        $trends = [];
        $totalsData = collect($this->trends['totals']);

        $overs = $totalsData->where('went_over', true)->count();
        if ($overs >= $this->minOccurrences) {
            $trends[] = sprintf(
                'The %s %s in %d of their last %d games',
                $this->teamName,
                self::ANALYSES['totals']['over_message'],
                $overs,
                $totalGames
            );
        }

        foreach (self::ANALYSES['totals']['thresholds'] as $threshold) {
            $filteredData = $totalsData;
            if (isset($threshold['min'])) {
                $filteredData = $filteredData->where('total_points', '>=', $threshold['min']);
            } else {
                $filteredData = $filteredData->where('total_points', '<=', $threshold['max']);
            }

            $count = $filteredData->count();
            if ($count >= $this->minOccurrences) {
                $trends[] = sprintf(
                    'The %s %s in %d of their last %d games',
                    $this->teamName,
                    $threshold['message'],
                    $count,
                    $totalGames
                );
            }
        }
        return $trends;
    }

    private function formatFirstScoreTrends(int $totalGames): array
    {
        $trends = [];
        $firstScoreData = collect($this->trends['first_score']);

        $scoredFirst = $firstScoreData->where('scored_first', true)->count();
        if ($scoredFirst >= $this->minOccurrences) {
            $trends[] = sprintf(
                'The %s have scored first in %d of their last %d games',
                $this->teamName,
                $scoredFirst,
                $totalGames
            );
        }

        foreach (self::QUARTERS as $quarter) {
            $count = $firstScoreData->where('scoring_quarter', $quarter)->count();
            if ($count >= $this->minOccurrences) {
                $trends[] = sprintf(
                    'The %s had their first score in %s in %d of their last %d games',
                    $this->teamName,
                    $quarter,
                    $count,
                    $totalGames
                );
            }
        }
        return $trends;
    }
    // Add other formatting methods for each trend type...
}