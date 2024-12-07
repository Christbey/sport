<?php

namespace App\Models\Nfl;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NflPlayerStat extends Model
{
    use HasFactory;

    protected $table = 'nfl_player_stats';

    protected $fillable = [
        'game_id',
        'player_id',
        'team_id',
        'team_abv',
        'receiving',
        'long_name',
        'rushing',
        'opponent_id',
        'kicking',
        'punting',
        'defense',
    ];

    protected $casts = [
        'receiving' => 'array',
        'rushing' => 'array',
        'kicking' => 'array',
        'punting' => 'array',
        'defense' => 'array',
    ];

    public static function getPlayerVsConference(
        ?string $teamFilter = null,
        ?string $playerFilter = null,
        ?string $conferenceFilter = null
    ): array
    {
        $conferenceGames = DB::table('nfl_player_stats as ps')
            ->join('nfl_box_scores as b', 'ps.game_id', '=', 'b.game_id')
            ->join('nfl_team_schedules as s', 'b.game_id', '=', 's.game_id')
            ->join('nfl_teams as t1', 'ps.team_abv', '=', 't1.team_abv')
            ->join('nfl_teams as t2', function ($join) {
                $join->on('t2.team_abv', '=', DB::raw('CASE WHEN b.home_team = ps.team_abv THEN b.away_team ELSE b.home_team END'));
            })
            ->select([
                'ps.long_name as player',
                'ps.team_abv',
                't1.conference_abv as conference',
                't1.division',
                DB::raw('CASE WHEN b.home_team = ps.team_abv THEN "home" ELSE "away" END as location_type'),
                't2.conference_abv as opponent_conference',
                'ps.game_id',
                DB::raw('CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.recYds")) AS UNSIGNED) as receiving_yards'),
                DB::raw('CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.rushYds")) AS UNSIGNED) as rushing_yards'),
                DB::raw('CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.recTD")) AS UNSIGNED) as receiving_tds'),
                DB::raw('CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.rushTD")) AS UNSIGNED) as rushing_tds'),
                DB::raw('CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.totalTackles")) AS UNSIGNED) as tackles'),
                DB::raw('CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.sacks")) AS UNSIGNED) as sacks'),
                DB::raw('CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.defensiveInterceptions")) AS UNSIGNED) as interceptions')
            ])
            ->where('s.season_type', 'Regular Season')
            ->whereRaw('(receiving IS NOT NULL OR rushing IS NOT NULL OR defense IS NOT NULL)')
            ->when($teamFilter, function ($query) use ($teamFilter) {
                $query->where('ps.team_abv', $teamFilter);
            })
            ->when($playerFilter, function ($query) use ($playerFilter) {
                $query->where('ps.long_name', $playerFilter);
            })
            ->when($conferenceFilter, function ($query) use ($conferenceFilter) {
                $query->where('t2.conference_abv', $conferenceFilter);
            });

        // Perform further operations on $conferenceGames, such as aggregations or groupings.

        $result = DB::query()
            ->fromSub($conferenceGames, 'cg')
            ->select([
                'player',
                'team_abv',
                'conference',
                'division',
                'location_type',
                DB::raw('COUNT(DISTINCT CASE WHEN opponent_conference = "AFC" THEN game_id END) as afc_games'),
                DB::raw('ROUND(AVG(CASE WHEN opponent_conference = "AFC" THEN receiving_yards END), 1) as afc_receiving_yards'),
                DB::raw('ROUND(AVG(CASE WHEN opponent_conference = "NFC" THEN receiving_yards END), 1) as nfc_receiving_yards'),
                DB::raw('SUM(CASE WHEN opponent_conference = "AFC" THEN receiving_tds END) as afc_receiving_tds'),
                DB::raw('SUM(CASE WHEN opponent_conference = "NFC" THEN receiving_tds END) as nfc_receiving_tds'),
            ])
            ->groupBy([
                'player',
                'team_abv',
                'conference',
                'division',
                'location_type'
            ])
            ->orderByDesc(DB::raw('afc_receiving_yards + nfc_receiving_yards'))
            ->limit(50);

        return [
            'data' => $result->get(),
            'headings' => [
                'Player',
                'Team Abv',
                'Conference',
                'Division',
                'Location Type',
                'AFC Games',
                'AFC Receiving Yards',
                'NFC Receiving Yards',
                'AFC Receiving TDs',
                'NFC Receiving TDs'
            ]
        ];
    }

    public function game()
    {
        return $this->belongsTo(NflBoxScore::class, 'game_id', 'game_id');
    }

    public function team()
    {
        return $this->belongsTo(NflTeam::class, 'team_abv', 'team_abv');
    }

    public function opponentTeam()
    {
        return $this->belongsTo(NflTeam::class, 'opponent_id', 'team_id');
    }
}