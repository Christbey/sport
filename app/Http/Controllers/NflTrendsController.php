<?php

namespace App\Http\Controllers;

use App\Models\Nfl\{NflBettingOdds, NflBoxScore};
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

    private const VALID_TEAMS = [
        'ARI', 'ATL', 'BAL', 'BUF', 'CAR', 'CHI', 'CIN', 'CLE',
        'DAL', 'DEN', 'DET', 'GB', 'HOU', 'IND', 'JAX', 'KC',
        'LAC', 'LAR', 'LV', 'MIA', 'MIN', 'NE', 'NO', 'NYG',
        'NYJ', 'PHI', 'PIT', 'SEA', 'SF', 'TB', 'TEN', 'WAS'
    ];

    private Collection $games;
    private Collection $bettingOdds;
    private string $teamName;
    private int $minOccurrences = 2;
    private array $trends = [];

    public function show(Request $request): View
    {
        if (!$request->has('team') || empty($request->input('team'))) {
            return view('nfl.trends.config');
        }

        $request->validate([
            'team' => 'required|string',
            'season' => 'nullable|integer',
            'games' => 'nullable|integer|min:1|max:100',
        ]);

        $this->teamName = strtoupper($request->input('team'));

        if (!in_array($this->teamName, self::VALID_TEAMS)) {
            return view('nfl.trends.config')->withErrors(['Invalid team selected']);
        }

        $this->games = $this->fetchGames($request);

        if ($this->games->isEmpty()) {
            return view('nfl.trends.config')->withErrors(['No games found for ' . $this->teamName]);
        }

        $this->bettingOdds = $this->fetchBettingOdds();
        $this->trends = $this->generateTrends();

        return view('nfl.trends.config', [
            'selectedTeam' => $this->teamName,
            'trends' => $this->formatTrends(),
            'totalGames' => $this->games->count(),
            'season' => $request->input('season'),
            'games' => $request->input('games', 10)
        ]);
    }

    private function fetchGames(Request $request): Collection
    {
        return NflBoxScore::query()
            ->join('nfl_team_schedules', function ($join) {
                $join->on('nfl_box_scores.game_id', '=', 'nfl_team_schedules.game_id')
                    ->where('nfl_team_schedules.season_type', 'Regular Season');
            })
            ->where(fn($q) => $q->where('nfl_box_scores.home_team', $this->teamName)
                ->orWhere('nfl_box_scores.away_team', $this->teamName))
            ->when($request->input('season'), fn($q, $season) => $q->whereYear('nfl_box_scores.game_date', $season))
            ->with('teamStats')
            ->orderBy('nfl_box_scores.game_date', 'desc')
            ->take($request->input('games', 20))
            ->get();
    }

    private function fetchBettingOdds(): Collection
    {
        return NflBettingOdds::whereIn('event_id', $this->games->pluck('game_id'))
            ->get()
            ->keyBy('event_id');
    }

    private function generateTrends(): array
    {
        $trends = array_fill_keys(
            [...array_keys(self::ANALYSES), 'spread_cover', 'first_score'],
            []
        );

        foreach ($this->games as $game) {
            $isHome = $game->home_team === $this->teamName;
            $this->collectGameData($game, $isHome, $trends);
        }

        return $trends;
    }

    private function collectGameData(NflBoxScore $game, bool $isHome, array &$trends): void
    {
        $lineScore = $isHome ? $game->home_line_score : $game->away_line_score;
        $oppLineScore = $isHome ? $game->away_line_score : $game->home_line_score;
        $teamScore = $isHome ? $game->home_points : $game->away_points;
        $margin = $this->calculateMargin($game, $isHome);

        $this->collectSpreadData($game, $isHome, $margin, $trends);
        $this->collectScoringData($teamScore, $game->game_id, $trends);
        $this->collectQuarterData($lineScore, $oppLineScore, $game->game_id, $trends);
        $this->collectMarginData($margin, $game->game_id, $trends);
        $this->collectTotalsData($game, $trends);
        $this->collectFirstScoreData($lineScore, $oppLineScore, $game->game_id, $trends);
    }

    private function calculateMargin(NflBoxScore $game, bool $isHome): float
    {
        return ($game->home_points - $game->away_points) * ($isHome ? 1 : -1);
    }

    private function collectSpreadData(NflBoxScore $game, bool $isHome, float $margin, array &$trends): void
    {
        $odds = $this->bettingOdds->get($game->game_id);
        if (!$odds) return;

        $spread = $isHome ? $odds->spread_home : $odds->spread_away;
        $covered = $this->determineSpreadCover($spread, $margin);

        $trends['spread_cover'][] = [
            'covered' => $covered,
            'spread' => $spread,
            'margin' => $margin,
            'game_id' => $game->game_id,
            'is_home' => $isHome,
            'is_favorite' => $spread < 0,
            'is_push' => $covered === null
        ];
    }

    private function determineSpreadCover(?float $spread, float $margin): ?bool
    {
        if (!$spread) return null;
        if ($spread < 0) return $margin > abs($spread) ? true : ($margin < abs($spread) ? false : null);
        return $margin > -$spread ? true : ($margin < -$spread ? false : null);
    }

    private function collectScoringData(int $teamScore, string $gameId, array &$trends): void
    {
        $trends['scoring'][] = compact('teamScore', 'gameId');
    }

    private function collectQuarterData(?array $lineScore, ?array $oppLineScore, string $gameId, array &$trends): void
    {
        if (!$lineScore || !$oppLineScore) return;

        foreach (self::QUARTERS as $quarter) {
            $teamScore = (int)($lineScore[$quarter] ?? 0);
            $oppScore = (int)($oppLineScore[$quarter] ?? 0);

            $trends['quarter'][] = [
                'quarter' => $quarter,
                'team_score' => $teamScore,
                'opp_score' => $oppScore,
                'won_quarter' => $teamScore > $oppScore,
                'game_id' => $gameId
            ];
        }

        foreach (self::HALVES as $half => $quarters) {
            $teamHalfScore = array_sum(array_map(fn($q) => (int)($lineScore[$q] ?? 0), $quarters));
            $oppHalfScore = array_sum(array_map(fn($q) => (int)($oppLineScore[$q] ?? 0), $quarters));

            $trends['half'][] = [
                'half' => $half,
                'team_score' => $teamHalfScore,
                'opp_score' => $oppHalfScore,
                'won_half' => $teamHalfScore > $oppHalfScore,
                'game_id' => $gameId
            ];
        }
    }

    private function collectMarginData(float $margin, string $gameId, array &$trends): void
    {
        $trends['margin'][] = [
            'margin' => $margin,
            'is_win' => $margin > 0,
            'game_id' => $gameId
        ];
    }

    private function collectTotalsData(NflBoxScore $game, array &$trends): void
    {
        $odds = $this->bettingOdds->get($game->game_id);
        if (!$odds) return;

        $totalPoints = $game->home_points + $game->away_points;
        $trends['totals'][] = [
            'total_points' => $totalPoints,
            'over_under' => $odds->total_over ?? 0,
            'went_over' => $totalPoints > ($odds->total_over ?? 0),
            'game_id' => $game->game_id
        ];
    }

    private function collectFirstScoreData(?array $lineScore, ?array $oppLineScore, string $gameId, array &$trends): void
    {
        if (!$lineScore || !$oppLineScore) return;

        foreach (self::QUARTERS as $quarter) {
            $teamScore = (int)($lineScore[$quarter] ?? 0);
            $oppScore = (int)($oppLineScore[$quarter] ?? 0);

            if ($teamScore > 0 || $oppScore > 0) {
                $trends['first_score'][] = [
                    'scoring_quarter' => $quarter,
                    'scored_first' => $teamScore > 0 && ($oppScore === 0 || $teamScore > $oppScore),
                    'game_id' => $gameId
                ];
                break;
            }
        }
    }

    private function formatTrends(): array
    {
        $totalGames = $this->games->count();

        return [
            'general' => $this->formatGeneralTrends($totalGames),
            'scoring' => $this->formatScoringTrends($totalGames),
            'quarters' => $this->formatQuarterTrends($totalGames),
            'halves' => $this->formatHalfTrends($totalGames),
            'margins' => $this->formatMarginTrends($totalGames),
            'totals' => $this->formatTotalsTrends($totalGames),
            'first_score' => $this->formatFirstScoreTrends($totalGames)
        ];
    }

    private function formatGeneralTrends(int $totalGames): array
    {
        $spreadData = collect($this->trends['spread_cover']);
        $validBets = $spreadData->filter(fn($bet) => $bet['covered'] !== null);

        return [
            'record' => $this->formatRecord($totalGames),
            'ats' => $this->formatATS($validBets, $totalGames),
            'over_under' => $this->formatOverUnder($totalGames)
        ];
    }

    private function formatRecord(int $totalGames): array
    {
        $wins = collect($this->trends['margin'])->where('is_win', true)->count();
        return [
            'wins' => $wins,
            'losses' => $totalGames - $wins,
            'percentage' => round(($wins / $totalGames) * 100)
        ];
    }

    private function formatATS(Collection $validBets, int $totalGames): array
    {
        $covers = $validBets->where('covered', true)->count();
        $totalValidBets = $validBets->count();

        return [
            'wins' => $covers,
            'losses' => $totalValidBets - $covers,
            'pushes' => $totalGames - $totalValidBets,
            'percentage' => $totalValidBets ? round(($covers / $totalValidBets) * 100) : 0
        ];
    }

    private function formatOverUnder(int $totalGames): array
    {
        $overs = collect($this->trends['totals'])->where('went_over', true)->count();
        return [
            'overs' => $overs,
            'unders' => $totalGames - $overs,
            'percentage' => round(($overs / $totalGames) * 100)
        ];
    }

    private function formatScoringTrends(int $totalGames): array
    {
        return collect(self::ANALYSES['scoring']['thresholds'])
            ->map(fn($threshold) => [
                'count' => collect($this->trends['scoring'])->where('points', '>', $threshold)->count(),
                'threshold' => $threshold
            ])
            ->filter(fn($data) => $data['count'] >= $this->minOccurrences)
            ->map(fn($data) => sprintf(
                self::ANALYSES['scoring']['message'],
                $data['threshold'],
                $data['count'],
                $totalGames
            ))
            ->values()
            ->all();
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
                $count = $this->countHalfScores($halfStats, $threshold);
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

    private function countHalfScores(Collection $stats, array $threshold): int
    {
        return $stats->filter(function ($stat) use ($threshold) {
            if (isset($threshold['min']) && isset($threshold['max'])) {
                return $stat['team_score'] >= $threshold['min'] && $stat['team_score'] <= $threshold['max'];
            }
            if (isset($threshold['min'])) {
                return $stat['team_score'] >= $threshold['min'];
            }
            return $stat['team_score'] < $threshold['max'];
        })->count();
    }

    private function formatMarginTrends(int $totalGames): array
    {
        $trends = [];
        $marginData = collect($this->trends['margin']);

        foreach (self::ANALYSES['margin']['thresholds'] as $threshold) {
            $count = $this->countMarginThresholds($marginData, $threshold);
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

    private function countMarginThresholds(Collection $data, array $threshold): int
    {
        return $data->filter(function ($item) use ($threshold) {
            if (isset($threshold['min']) && isset($threshold['max'])) {
                return $item['margin'] >= $threshold['min'] && $item['margin'] <= $threshold['max'];
            }
            return isset($threshold['min'])
                ? $item['margin'] >= $threshold['min']
                : $item['margin'] <= $threshold['max'];
        })->count();
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
            $count = $this->countTotalThresholds($totalsData, $threshold);
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

    private function countTotalThresholds(Collection $data, array $threshold): int
    {
        return $data->filter(function ($item) use ($threshold) {
            return isset($threshold['min'])
                ? $item['total_points'] >= $threshold['min']
                : $item['total_points'] <= $threshold['max'];
        })->count();
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
}