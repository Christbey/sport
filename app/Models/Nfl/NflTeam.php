<?php

namespace App\Models\Nfl;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NflTeam extends Model
{
    protected $fillable = [
        'team_abv',
        'team_city',
        'team_name',
        'team_id',
        'division',
        'conference_abv',
        'conference',
        'nfl_com_logo1',
        'espn_logo1',
        'espn_id',
        'uid',
        'slug',
        'color',
        'alternate_color',
        'is_active',
        'wins',
        'loss',
        'tie',
        'pf',
        'pa',
        'current_streak',
    ];

    protected $casts = [
        'current_streak' => 'array',
    ];

    // Relationships

    // Filter application method

    public static function getTeamVsConference(?string $teamFilter = null, ?string $locationFilter = null, ?string $conferenceFilter = null, ?string $divisionFilter = null): array
    {
        // Build the main subquery for games
        $conferenceGames = DB::table('nfl_box_scores as b')
            ->join('nfl_team_schedules as s', 'b.game_id', '=', 's.game_id')
            ->join('nfl_teams as t1', function ($join) {
                $join->where(function ($query) {
                    $query->whereRaw('b.home_team = t1.team_abv')
                        ->orWhereRaw('b.away_team = t1.team_abv');
                });
            })
            ->join('nfl_teams as t2', function ($join) {
                $join->whereRaw('CASE 
                WHEN b.home_team = t1.team_abv THEN b.away_team
                ELSE b.home_team
            END = t2.team_abv');
            })
            ->join('nfl_team_stats as ts1', function ($join) {
                $join->on('b.game_id', '=', 'ts1.game_id')
                    ->whereRaw('ts1.team_abv = t1.team_abv');
            })
            ->join('nfl_team_stats as ts2', function ($join) {
                $join->on('b.game_id', '=', 'ts2.game_id')
                    ->whereRaw('ts2.team_abv = CASE 
                WHEN b.home_team = t1.team_abv THEN b.away_team
                ELSE b.home_team
            END');
            })
            ->select([
                'b.game_id',
                't1.team_abv',
                't1.conference_abv as own_conference',
                't1.division as team_division', // Team division
                't2.conference_abv as vs_conference', // Opponent conference
                't2.division as opponent_division', // Opponent division
                DB::raw('CASE 
                WHEN b.home_team = t1.team_abv THEN b.home_points
                ELSE b.away_points
            END as team_points'),
                DB::raw('CASE 
                WHEN b.home_team = t1.team_abv THEN b.away_points
                ELSE b.home_points
            END as opponent_points'),
                DB::raw('CASE 
                WHEN b.home_team = t1.team_abv THEN ts1.total_yards
                ELSE ts2.total_yards
            END as team_yards'),
                DB::raw('CASE 
                WHEN b.home_team = t1.team_abv THEN ts2.total_yards
                ELSE ts1.total_yards
            END as opponent_yards'),
                DB::raw('CASE 
                WHEN b.home_team = t1.team_abv THEN "home"
                ELSE "away"
            END as location_type'),
            ])
            ->where('s.season_type', 'Regular Season')
            ->when($teamFilter, function ($query) use ($teamFilter) {
                $query->where('t1.team_abv', $teamFilter);
            })
            ->when($conferenceFilter, function ($query) use ($conferenceFilter) {
                $query->where('t2.conference_abv', $conferenceFilter);
            })
            ->when($divisionFilter, function ($query) use ($divisionFilter) {
                // Filter the opponent's division
                $query->where('t2.division', $divisionFilter);
            });

        // Build the main query from the subquery
        $result = DB::query()
            ->fromSub($conferenceGames, 'conference_games')
            ->select([
                'team_abv',
                'own_conference as conference', // Alias to match what is expected in the view
                'team_division as division', // Include team division
                'vs_conference',
                'opponent_division', // Include opponent division
                'location_type',
                DB::raw('COUNT(*) as games_played'),
                DB::raw('ROUND(AVG(team_points), 1) as avg_points_for'),
                DB::raw('ROUND(AVG(opponent_points), 1) as avg_points_against'),
                DB::raw('ROUND(AVG(team_yards), 1) as avg_yards_for'),
                DB::raw('ROUND(AVG(opponent_yards), 1) as avg_yards_against'),
                DB::raw('SUM(CASE WHEN team_points > opponent_points THEN 1 ELSE 0 END) as wins'),
                DB::raw('SUM(CASE WHEN team_points < opponent_points THEN 1 ELSE 0 END) as losses'),
                DB::raw('ROUND(AVG(team_points - opponent_points), 1) as avg_margin'),
                DB::raw('ROUND(
                SUM(CASE WHEN team_points > opponent_points THEN 1 ELSE 0 END) * 100.0 / COUNT(*),
                1
            ) as win_percentage'),
                DB::raw('ROUND(STDDEV(team_points), 1) as points_stddev'),
                DB::raw('ROUND(STDDEV(team_yards), 1) as yards_stddev')
            ])
            ->groupBy([
                'team_abv',
                'conference',
                'division',
                'vs_conference',
                'opponent_division',
                'location_type'
            ])
            ->orderBy('team_abv')
            ->orderBy('vs_conference')
            ->orderByDesc('win_percentage')
            ->orderByDesc('avg_margin');

        return [
            'data' => $result->get(),
            'headings' => [
                'Team',
                'Conference',
                'Division',
                'Vs Conference',
                'Opponent Division',
                'Location Type',
                'Games Played',
                'Avg Points For',
                'Avg Points Against',
                'Avg Yards For',
                'Avg Yards Against',
                'Wins',
                'Losses',
                'Avg Margin',
                'Win %',
                'Points StdDev',
                'Yards StdDev'
            ]
        ];
    }

    public static function applyFilters($query, ?string $teamFilter, ?string $locationFilter, ?string $conferenceFilter, ?string $divisionFilter)
    {
        // Apply team filter
        if ($teamFilter) {
            $query->where(function ($q) use ($teamFilter) {
                $q->where('b.home_team', $teamFilter)
                    ->orWhere('b.away_team', $teamFilter);
            });
        }

        // Apply location filter if provided
        if ($locationFilter && $teamFilter) {
            if ($locationFilter === 'home') {
                $query->where('b.home_team', $teamFilter);
            } elseif ($locationFilter === 'away') {
                $query->where('b.away_team', $teamFilter);
            }
        }

        // Apply conference and division filters
        if ($conferenceFilter || $divisionFilter) {
            $teamIds = self::query()
                ->when($conferenceFilter, fn($q) => $q->where('conference_abv', $conferenceFilter))
                ->when($divisionFilter, fn($q) => $q->where('division', $divisionFilter))
                ->pluck('team_abv');

            if ($teamIds->isNotEmpty()) {
                $query->where(function ($q) use ($teamIds) {
                    $q->whereIn('b.home_team', $teamIds)
                        ->orWhereIn('b.away_team', $teamIds);
                });
            } else {
                // If no teams match the filters, return an empty result
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
    }

    public function players()
    {
        return $this->hasMany(NflPlayerStat::class, 'team_abv', 'team_abv');
    }

    // Scope to filter by conference

    public function homeGames()
    {
        return $this->hasMany(NflBoxScore::class, 'home_team', 'team_abv');
    }

    // Scope to filter by division

    public function awayGames()
    {
        return $this->hasMany(NflBoxScore::class, 'away_team', 'team_abv');
    }

    // Scope to filter by team abbreviation

    public function scopeFilterByConference($query, $conference)
    {
        return $conference ? $query->where('conference_abv', $conference) : $query;
    }

    public function scopeFilterByDivision($query, $division)
    {
        return $division ? $query->where('division', $division) : $query;
    }

    public function scopeFilterByTeamAbv($query, $teamAbv)
    {
        return $teamAbv ? $query->where('team_abv', $teamAbv) : $query;
    }
}
