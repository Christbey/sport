<?php

namespace App\Repositories\Nfl;

use App\Models\Nfl\NflBoxScore;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use LaravelIdea\Helper\App\Models\Nfl\_IH_NflBoxScore_C;

class NflBoxScoreRepository
{
    /**
     * Update or create a box score record using API data.
     *
     * @param array $boxScoreData Merged game and box score data.
     */
    public function updateOrCreateFromRapidApi(array $boxScoreData): void
    {
        // Extract the lineScore data
        $lineScore = $boxScoreData['lineScore'] ?? null;

        $data = [
            'game_id' => $boxScoreData['gameID'] ?? null,
            'home_team' => $boxScoreData['home'] ?? null,
            'away_team' => $boxScoreData['away'] ?? null,
            'home_points' => isset($boxScoreData['homePts']) ? (int)$boxScoreData['homePts'] : null,
            'away_points' => isset($boxScoreData['awayPts']) ? (int)$boxScoreData['awayPts'] : null,
            'game_date' => isset($boxScoreData['gameDate']) ? Carbon::createFromFormat('Ymd', $boxScoreData['gameDate'])->toDateString() : null,
            'location' => $boxScoreData['gameLocation'] ?? null,
            'home_line_score' => $lineScore['home'] ?? null,
            'away_line_score' => $lineScore['away'] ?? null,
            'away_result' => $boxScoreData['awayResult'] ?? null,
            'home_result' => $boxScoreData['homeResult'] ?? null,
            'game_status' => $boxScoreData['gameStatus'] ?? null,
        ];

        // Remove null values
        $data = array_filter($data, fn($value) => !is_null($value));

        // Update or create the box score record
        NflBoxScore::updateOrCreate(
            ['game_id' => $data['game_id']],
            $data
        );
    }


    public function getGamesByTeam(string $teamName, ?int $season, int $limit = 20): array|Collection|_IH_NflBoxScore_C
    {
        return NflBoxScore::query()
            ->join('nfl_team_schedules', function ($join) {
                $join->on('nfl_box_scores.game_id', '=', 'nfl_team_schedules.game_id')
                    ->where('nfl_team_schedules.season_type', 'Regular Season');
            })
            ->where(fn($q) => $q->where('nfl_box_scores.home_team', $teamName)
                ->orWhere('nfl_box_scores.away_team', $teamName))
            ->when($season, fn($q) => $q->whereYear('nfl_box_scores.game_date', $season))
            ->with('teamStats')
            ->orderBy('nfl_box_scores.game_date', 'desc')
            ->take($limit)
            ->get();
    }
}

