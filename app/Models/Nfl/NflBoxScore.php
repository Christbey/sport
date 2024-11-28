<?php

namespace App\Models\Nfl;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class NflBoxScore extends Model
{
    use HasFactory;

    protected $table = 'nfl_box_scores';

    protected $fillable = [
        'game_id',
        'home_team',
        'away_team',
        'home_points',
        'away_points',
        'game_date',
        'location',
        'home_line_score',
        'away_line_score',
        'away_result',
        'home_result',
        'game_status',
    ];

    protected $casts = [
        'home_line_score' => 'array',
        'away_line_score' => 'array',
    ];

    // Relationships

    public static function getTeamVsConference(?string $teamFilter = null): array
    {
        $query = self::with(['homeTeam', 'awayTeam'])
            ->whereHas('teamSchedules', function (Builder $query) {
                $query->where('season_type', 'Regular Season');
            })
            ->when($teamFilter, function ($query) use ($teamFilter) {
                $query->where(function ($q) use ($teamFilter) {
                    $q->where('home_team', $teamFilter)
                        ->orWhere('away_team', $teamFilter);
                });
            })
            ->get()
            ->map(function ($boxScore) use ($teamFilter) {
                $team = $boxScore->home_team === $teamFilter ? $boxScore->homeTeam : $boxScore->awayTeam;
                $opponent = $boxScore->home_team === $teamFilter ? $boxScore->awayTeam : $boxScore->homeTeam;

                return [
                    'team_abv' => $team->team_abv,
                    'team_conference_abv' => $team->conference_abv,
                    'opponent_conference_abv' => $opponent->conference_abv,
                    'team_points' => $boxScore->home_team === $teamFilter ? $boxScore->home_points : $boxScore->away_points,
                    'opponent_points' => $boxScore->home_team === $teamFilter ? $boxScore->away_points : $boxScore->home_points,
                    'team_yards' => $boxScore->home_team === $teamFilter ? $boxScore->teamStats->where('team_abv', $teamFilter)->first()->total_yards : $boxScore->teamStats->where('team_abv', $opponent->team_abv)->first()->total_yards,
                ];
            });

        $aggregated = collect($query)->groupBy(['team_abv', 'team_conference_abv', 'opponent_conference_abv'])->map(function ($games) {
            return [
                'games_played' => $games->count(),
                'avg_points_for' => round($games->avg('team_points'), 1),
                'avg_points_against' => round($games->avg('opponent_points'), 1),
                'avg_yards_for' => round($games->avg('team_yards'), 1),
                'avg_margin' => round($games->avg('team_points') - $games->avg('opponent_points'), 1),
                'win_percentage' => round($games->filter(fn($game) => $game['team_points'] > $game['opponent_points'])->count() * 100 / $games->count(), 1),
            ];
        });

        return [
            'data' => $aggregated,
            'headings' => [
                'Team',
                'Own Conf',
                'Vs Conf',
                'Games Played',
                'Avg Points For',
                'Avg Points Against',
                'Avg Yards For',
                'Avg Margin',
                'Win %',
            ]
        ];
    }

    /**
     * Get team vs division statistics using Eloquent
     *
     * @param string|null $teamFilter
     * @return array
     */
    public static function getTeamVsDivision(?string $teamFilter = null): array
    {
        $query = self::with(['homeTeam', 'awayTeam'])
            ->whereHas('teamSchedules', function (Builder $query) {
                $query->where('season_type', 'Regular Season');
            })
            ->when($teamFilter, function ($query) use ($teamFilter) {
                $query->where(function ($q) use ($teamFilter) {
                    $q->where('home_team', $teamFilter)
                        ->orWhere('away_team', $teamFilter);
                });
            })
            ->get()
            ->map(function ($boxScore) use ($teamFilter) {
                $team = $boxScore->home_team === $teamFilter ? $boxScore->homeTeam : $boxScore->awayTeam;
                $opponent = $boxScore->home_team === $teamFilter ? $boxScore->awayTeam : $boxScore->homeTeam;

                return [
                    'team_abv' => $team->team_abv,
                    'team_division' => $team->division,
                    'opponent_division' => $opponent->division,
                    'team_points' => $boxScore->home_team === $teamFilter ? $boxScore->home_points : $boxScore->away_points,
                    'opponent_points' => $boxScore->home_team === $teamFilter ? $boxScore->away_points : $boxScore->home_points,
                    'team_yards' => $boxScore->home_team === $teamFilter ? $boxScore->teamStats->where('team_abv', $teamFilter)->first()->total_yards : $boxScore->teamStats->where('team_abv', $opponent->team_abv)->first()->total_yards,
                ];
            });

        $aggregated = collect($query)->groupBy(['team_abv', 'team_division', 'opponent_division'])->map(function ($games) {
            return [
                'games_played' => $games->count(),
                'avg_points_for' => round($games->avg('team_points'), 1),
                'avg_points_against' => round($games->avg('opponent_points'), 1),
                'avg_yards_for' => round($games->avg('team_yards'), 1),
                'avg_margin' => round($games->avg('team_points') - $games->avg('opponent_points'), 1),
                'win_percentage' => round($games->filter(fn($game) => $game['team_points'] > $game['opponent_points'])->count() * 100 / $games->count(), 1),
            ];
        });

        return [
            'data' => $aggregated,
            'headings' => [
                'Team',
                'Division',
                'Opponent Division',
                'Games Played',
                'Avg Points For',
                'Avg Points Against',
                'Avg Yards For',
                'Avg Margin',
                'Win %',
            ]
        ];
    }

    public function playerStats()
    {
        return $this->hasMany(NflPlayerStat::class, 'game_id', 'game_id');
    }


    // Scope to filter by home team

    public function homeTeam()
    {
        return $this->belongsTo(NflTeam::class, 'home_team', 'team_abv');
    }

    // Scope to filter by away team

    public function awayTeam()
    {
        return $this->belongsTo(NflTeam::class, 'away_team', 'team_abv');
    }

    public function teamSchedules()
    {
        return $this->hasMany(NflTeamSchedule::class, 'game_id', 'game_id');
    }

    public function teamStats()
    {
        return $this->hasMany(NflTeamStat::class, 'game_id', 'game_id');
    }

    // Scope to filter by location (home or away)

    public function scopeFilterByHomeTeam($query, $teamAbv)
    {
        return $teamAbv ? $query->where('home_team', $teamAbv) : $query;
    }

    // Scope to filter by game IDs

    public function scopeFilterByAwayTeam($query, $teamAbv)
    {
        return $teamAbv ? $query->where('away_team', $teamAbv) : $query;
    }

    // Scope to join with teams for home team

    public function scopeFilterByTeam(Builder $query, ?string $teamAbv): Builder
    {
        if ($teamAbv) {
            return $query->where(function ($q) use ($teamAbv) {
                $q->where('home_team', $teamAbv)
                    ->orWhere('away_team', $teamAbv);
            });
        }

        return $query;
    }

    // Scope to join with teams for away team

    public function scopeFilterByGameIds(Builder $query, array $gameIds): Builder
    {
        return $query->whereIn('game_id', $gameIds);
    }

    // Scope to apply the location filter

    public function scopeJoinHomeTeam(Builder $query): Builder
    {
        return $query->join('nfl_teams as home_team', 'nfl_box_scores.home_team', '=', 'home_team.team_abv');
    }

    // In NflBoxScore.php

    public function scopeJoinAwayTeam(Builder $query): Builder
    {
        return $query->join('nfl_teams as away_team', 'nfl_box_scores.away_team', '=', 'away_team.team_abv');
    }

    public function scopeFilterByLocation(Builder $query, ?string $locationFilter): Builder
    {
        if ($locationFilter === 'home') {
            return $query->where('location_type', 'home');
        } elseif ($locationFilter === 'away') {
            return $query->where('location_type', 'away');
        }

        return $query;
    }

    public function scopeSelectHomeTeamColumns(Builder $query): Builder
    {
        return $query->select([
            'nfl_box_scores.home_team as team_abv',
            DB::raw("'home' as location_type"),
            'home_team.conference_abv as conference',
            'home_team.division as division',
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(nfl_box_scores.home_line_score, '$.Q1')) as Q1"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(nfl_box_scores.home_line_score, '$.Q2')) as Q2"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(nfl_box_scores.home_line_score, '$.Q3')) as Q3"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(nfl_box_scores.home_line_score, '$.Q4')) as Q4"),
            'nfl_box_scores.home_points as totalPts'
        ]);
    }

    public function scopeSelectAwayTeamColumns(Builder $query): Builder
    {
        return $query->select([
            'nfl_box_scores.away_team as team_abv',
            DB::raw("'away' as location_type"),
            'away_team.conference_abv as conference',
            'away_team.division as division',
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(nfl_box_scores.away_line_score, '$.Q1')) as Q1"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(nfl_box_scores.away_line_score, '$.Q2')) as Q2"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(nfl_box_scores.away_line_score, '$.Q3')) as Q3"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(nfl_box_scores.away_line_score, '$.Q4')) as Q4"),
            'nfl_box_scores.away_points as totalPts'
        ]);
    }


}
