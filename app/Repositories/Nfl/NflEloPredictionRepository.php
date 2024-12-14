<?php

namespace App\Repositories\Nfl;

use App\Helpers\OpenAI;
use App\Models\Nfl\NflEloPrediction;
use App\Repositories\Nfl\Interfaces\NflEloPredictionRepositoryInterface;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Log;

class NflEloPredictionRepository implements NflEloPredictionRepositoryInterface
{
    private const DEFAULT_COLUMNS = [
        'game_id',
        'team',
        'opponent',
        'week',
        'team_elo',
        'opponent_elo',
        'expected_outcome',
        'predicted_spread'
    ];

    public function getPredictions(?int $week): Collection
    {
        return NflEloPrediction::query()
            ->when($week, fn($query) => $query->where('week', $week))
            ->orderBy('game_id')
            ->get();
    }

    public function getDistinctWeeks(): Collection
    {
        return NflEloPrediction::distinct()
            ->orderBy(DB::raw('CAST(week AS SIGNED)'))
            ->pluck('week');
    }

    public function enrichPredictionsWithGameData(Collection $predictions, Collection $schedules): Collection
    {
        return $predictions->map(function ($prediction) use ($schedules) {
            $game = $schedules->get($prediction->game_id);
            if (!$game) return $prediction;

            $prediction->homePts = $game->home_pts;
            $prediction->awayPts = $game->away_pts;
            $prediction->gameStatus = $game->game_status;
            $prediction->gameStatusDetail = $game->status_type_detail;

            $this->calculatePredictionAccuracy($prediction, $game);
            return $prediction;
        });
    }

    private function calculatePredictionAccuracy($prediction, $game): void
    {
        if (!isset($game->home_pts, $game->away_pts)) {
            $prediction->wasCorrect = null;
            return;
        }

        $actualSpread = $game->home_pts - $game->away_pts;
        $predictedSpread = $prediction->predicted_spread;

        $prediction->wasCorrect = ($predictedSpread > 0 && $actualSpread > $predictedSpread) ||
            ($predictedSpread < 0 && $actualSpread < $predictedSpread);
    }

    public function findByGameId(string $gameId): ?object
    {
        return NflEloPrediction::where('game_id', $gameId)
            ->select(self::DEFAULT_COLUMNS)
            ->first();
    }

    public function findByDateRange(string $startDate, string $endDate): Collection
    {
        return NflEloPrediction::whereBetween('week', [$startDate, $endDate])
            ->select(self::DEFAULT_COLUMNS)
            ->get();
    }

    public function findByWeek(int $week): Collection
    {
        return NflEloPrediction::where('week', $week)
            ->select(self::DEFAULT_COLUMNS)
            ->get();
    }

    public function hasUpdatedToday(): bool
    {
        return NflEloPrediction::whereDate('created_at', today())->exists();
    }

    /**
     * Get comprehensive team prediction data
     *
     * @param string|null $teamAbv
     * @param int|null $week
     * @param bool $includeStats
     * @param bool $includeFactors
     * @return array
     */
    public function getTeamPrediction(
        ?string $teamAbv,
        ?int    $week = null,
        bool    $includeStats = false,
        bool    $includeFactors = false
    ): array
    {
        try {
            // Get current week if not specified
            $week = $week ?? OpenAI::getCurrentNFLWeek();

            // Get predictions for the specified team and week
            $predictions = $this->findByTeam(
                teamAbv: $teamAbv,
                week: $week
            );

            // If no predictions found
            if ($predictions->isEmpty()) {
                Log::info('No predictions found', [
                    'team' => $teamAbv,
                    'week' => $week
                ]);

                return [
                    'found' => false,
                    'message' => "No predictions found for {$teamAbv} in week {$week}.",
                    'data' => null
                ];
            }

            $prediction = $predictions->first();

            // Calculate win probability based on Elo ratings
            $winProbability = $this->calculateWinProbability(
                $prediction->team_elo,
                $prediction->opponent_elo
            );

            // Calculate predicted scores based on spread
            $predictedScores = $this->calculatePredictedScores($prediction->predicted_spread);

            // Format base response
            $response = [
                'found' => true,
                'prediction' => [
                    'team' => $teamAbv,
                    'opponent' => $prediction->opponent,
                    'week' => $week,
                    'is_predicted_winner' => $prediction->predicted_spread > 0,
                    'win_probability' => round($winProbability * 100, 1),
                    'predicted_score' => [
                        $teamAbv => round($predictedScores['team'], 1),
                        $prediction->opponent => round($predictedScores['opponent'], 1)
                    ],
                    'point_differential' => abs($prediction->predicted_spread),
                    'confidence_level' => $this->determineConfidenceLevel($winProbability)
                ]
            ];

            // Add summary
            $response['summary'] = $this->generatePredictionSummary(
                teamAbv: $teamAbv,
                opponent: $prediction->opponent,
                isPredictedWinner: $prediction->predicted_spread > 0,
                winProbability: $winProbability * 100,
                pointDifferential: abs($prediction->predicted_spread)
            );

            return $response;

        } catch (Exception $e) {
            Log::error('Error processing team prediction', [
                'team' => $teamAbv,
                'week' => $week,
                'error' => $e->getMessage()
            ]);

            return [
                'found' => false,
                'message' => 'Error processing prediction request: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function findByTeam(
        ?string $teamAbv = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $opponent = null,
        ?int    $week = null
    ): Collection
    {
        $query = NflEloPrediction::query();

        if ($teamAbv) {
            $query->where(function ($q) use ($teamAbv) {
                $q->where('team', $teamAbv)
                    ->orWhere('opponent', $teamAbv);
            });
        }

        if ($opponent) {
            $query->where(function ($q) use ($opponent) {
                $q->where('team', $opponent)
                    ->orWhere('opponent', $opponent);
            });
        }

        if ($week) {
            $query->where('week', $week);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('week', [$startDate, $endDate]);
        }

        return $query->select([
            'game_id',
            'team',
            'opponent',
            'week',
            'team_elo',
            'opponent_elo',
            'expected_outcome',
            'predicted_spread'
        ])->get()->map(function ($prediction) use ($teamAbv) {
            // If the team we're looking for is in the opponent column, flip the prediction
            if ($teamAbv && $prediction->opponent === $teamAbv) {
                return (object)[
                    'game_id' => $prediction->game_id,
                    'team' => $prediction->opponent,
                    'opponent' => $prediction->team,
                    'week' => $prediction->week,
                    'team_elo' => $prediction->opponent_elo,
                    'opponent_elo' => $prediction->team_elo,
                    'expected_outcome' => 1 - $prediction->expected_outcome,
                    'predicted_spread' => -$prediction->predicted_spread
                ];
            }

            return $prediction;
        });
    }

    /**
     * Calculate win probability based on Elo ratings
     */
    private function calculateWinProbability(float $teamElo, float $opponentElo): float
    {
        $eloDiff = $teamElo - $opponentElo;
        return 1 / (1 + pow(10, (-$eloDiff / 400)));
    }

    /**
     * Calculate predicted scores based on spread
     */
    private function calculatePredictedScores(float $spread): array
    {
        $baseScore = 24; // Average NFL score
        $halfSpread = $spread / 2;

        return [
            'team' => $baseScore + $halfSpread,
            'opponent' => $baseScore - $halfSpread
        ];
    }

    /**
     * Determine confidence level based on win probability
     */
    private function determineConfidenceLevel(float $winProbability): string
    {
        return match (true) {
            $winProbability >= 0.75 => 'HIGH',
            $winProbability >= 0.60 => 'MODERATE',
            default => 'LOW'
        };
    }

    /**
     * Generate a narrative summary of the prediction
     */
    private function generatePredictionSummary(
        string $teamAbv,
        string $opponent,
        bool   $isPredictedWinner,
        float  $winProbability,
        float  $pointDifferential
    ): string
    {
        $confidenceLevel = match (true) {
            $winProbability >= 75 => 'strongly',
            $winProbability >= 60 => 'moderately',
            default => 'slightly'
        };

        if ($isPredictedWinner) {
            return "{$teamAbv} is {$confidenceLevel} favored to win against {$opponent} " .
                "with a {$winProbability}% win probability and projected {$pointDifferential}-point margin.";
        } else {
            return "{$teamAbv} is predicted to lose to {$opponent} " .
                "with a {$winProbability}% win probability and projected {$pointDifferential}-point deficit.";
        }
    }
}