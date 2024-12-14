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

    public static function getPlayerVsConference(?string $teamFilter = null, ?string $playerFilter = null): array
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
            });


        $result = DB::query()
            ->fromSub($conferenceGames, 'cg')
            ->select([
                'player',
                'team_abv',
                'conference',
                'division',
                'location_type',
                // AFC Stats
                DB::raw('COUNT(DISTINCT CASE WHEN opponent_conference = "AFC" THEN game_id END) as afc_games'),
                DB::raw('ROUND(AVG(CASE WHEN opponent_conference = "AFC" THEN receiving_yards END), 1) as afc_receiving_yards'),
                DB::raw('ROUND(AVG(CASE WHEN opponent_conference = "AFC" THEN rushing_yards END), 1) as afc_rushing_yards'),
                DB::raw('SUM(CASE WHEN opponent_conference = "AFC" THEN receiving_tds END) as afc_receiving_tds'),
                DB::raw('SUM(CASE WHEN opponent_conference = "AFC" THEN rushing_tds END) as afc_rushing_tds'),
                DB::raw('ROUND(AVG(CASE WHEN opponent_conference = "AFC" THEN tackles END), 1) as afc_avg_tackles'),
                DB::raw('SUM(CASE WHEN opponent_conference = "AFC" THEN sacks END) as afc_sacks'),
                DB::raw('SUM(CASE WHEN opponent_conference = "AFC" THEN interceptions END) as afc_ints'),
                // NFC Stats
                DB::raw('COUNT(DISTINCT CASE WHEN opponent_conference = "NFC" THEN game_id END) as nfc_games'),
                DB::raw('ROUND(AVG(CASE WHEN opponent_conference = "NFC" THEN receiving_yards END), 1) as nfc_receiving_yards'),
                DB::raw('ROUND(AVG(CASE WHEN opponent_conference = "NFC" THEN rushing_yards END), 1) as nfc_rushing_yards'),
                DB::raw('SUM(CASE WHEN opponent_conference = "NFC" THEN receiving_tds END) as nfc_receiving_tds'),
                DB::raw('SUM(CASE WHEN opponent_conference = "NFC" THEN rushing_tds END) as nfc_rushing_tds'),
                DB::raw('ROUND(AVG(CASE WHEN opponent_conference = "NFC" THEN tackles END), 1) as nfc_avg_tackles'),
                DB::raw('SUM(CASE WHEN opponent_conference = "NFC" THEN sacks END) as nfc_sacks'),
                DB::raw('SUM(CASE WHEN opponent_conference = "NFC" THEN interceptions END) as nfc_ints'),
                // Adding total score calculation as a column for proper ordering
                DB::raw('(
                    COALESCE(SUM(CASE WHEN opponent_conference = "AFC" THEN receiving_tds END), 0) +
                    COALESCE(SUM(CASE WHEN opponent_conference = "AFC" THEN rushing_tds END), 0) +
                    COALESCE(SUM(CASE WHEN opponent_conference = "NFC" THEN receiving_tds END), 0) +
                    COALESCE(SUM(CASE WHEN opponent_conference = "NFC" THEN rushing_tds END), 0) +
                    COALESCE(SUM(CASE WHEN opponent_conference = "AFC" THEN sacks END), 0) +
                    COALESCE(SUM(CASE WHEN opponent_conference = "NFC" THEN sacks END), 0) +
                    COALESCE(SUM(CASE WHEN opponent_conference = "AFC" THEN interceptions END), 0) +
                    COALESCE(SUM(CASE WHEN opponent_conference = "NFC" THEN interceptions END), 0)
                ) as total_score')
            ])
            ->groupBy([
                'player',
                'team_abv',
                'conference',
                'division',
                'location_type'
            ])
            ->having(DB::raw('afc_games + nfc_games'), '>=', 2)
            ->orderByDesc('total_score')
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
                'AFC Rec Yards',
                'AFC Rush Yards',
                'AFC Rec TDs',
                'AFC Rush TDs',
                'AFC Tackles',
                'AFC Sacks',
                'AFC INTs',
                'NFC Games',
                'NFC Rec Yards',
                'NFC Rush Yards',
                'NFC Rec TDs',
                'NFC Rush TDs',
                'NFC Tackles',
                'NFC Sacks',
                'NFC INTs'
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

    // Additional relationships

    public function opponentTeam()
    {
        return $this->belongsTo(NflTeam::class, 'opponent_id', 'team_id');
    }

    // Statistical Methods

    public function player()
    {
        return $this->belongsTo(NflPlayerData::class, 'player_id', 'player_id');
    }

    public function getReceivingStats()
    {
        return DB::table($this->table)
            ->select([
                DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.recYds")) AS UNSIGNED)) as avg_yards'),
                DB::raw('MAX(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.recYds")) AS UNSIGNED)) as max_yards'),
                DB::raw('MIN(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.recYds")) AS UNSIGNED)) as min_yards'),
                DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.recTD")) AS UNSIGNED)) as avg_touchdowns'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.recTD")) AS UNSIGNED)) as total_touchdowns'),
                DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.rec")) AS UNSIGNED)) as avg_receptions'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(receiving, "$.rec")) AS UNSIGNED)) as total_receptions')
            ])
            ->whereNotNull('receiving')
            ->first();
    }

    public function getRushingStats()
    {
        return DB::table($this->table)
            ->select([
                DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.rushYds")) AS UNSIGNED)) as avg_yards'),
                DB::raw('MAX(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.rushYds")) AS UNSIGNED)) as max_yards'),
                DB::raw('MIN(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.rushYds")) AS UNSIGNED)) as min_yards'),
                DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.rushTD")) AS UNSIGNED)) as avg_touchdowns'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.rushTD")) AS UNSIGNED)) as total_touchdowns'),
                DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.rush")) AS UNSIGNED)) as avg_rushes'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(rushing, "$.rush")) AS UNSIGNED)) as total_rushes')
            ])
            ->whereNotNull('rushing')
            ->first();
    }

    public function getDefenseStats()
    {
        return DB::table($this->table)
            ->select([
                DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.totalTackles")) AS UNSIGNED)) as avg_tackles'),
                DB::raw('MAX(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.totalTackles")) AS UNSIGNED)) as max_tackles'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.totalTackles")) AS UNSIGNED)) as total_tackles'),
                DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.sacks")) AS UNSIGNED)) as avg_sacks'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.sacks")) AS UNSIGNED)) as total_sacks'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.defensiveInterceptions")) AS UNSIGNED)) as total_interceptions'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(defense, "$.fumblesForced")) AS UNSIGNED)) as total_forced_fumbles')
            ])
            ->whereNotNull('defense')
            ->first();
    }

    public function getKickingStats()
    {
        return DB::table($this->table)
            ->select([
                DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(kicking, "$.fgMade")) AS UNSIGNED)) as avg_field_goals_made'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(kicking, "$.fgMade")) AS UNSIGNED)) as total_field_goals_made'),
                DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(kicking, "$.fgAtt")) AS UNSIGNED)) as avg_field_goals_attempted'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(kicking, "$.xpMade")) AS UNSIGNED)) as total_extra_points_made'),
                DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(kicking, "$.xpAtt")) AS UNSIGNED)) as avg_extra_points_attempted')
            ])
            ->whereNotNull('kicking')
            ->first();
    }

    // Existing getPlayerVsConference method remains the same

    public function getPuntingStats()
    {
        return DB::table($this->table)
            ->select([
                DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(punting, "$.puntYds")) AS UNSIGNED)) as avg_yards'),
                DB::raw('MAX(CAST(JSON_UNQUOTE(JSON_EXTRACT(punting, "$.puntLng")) AS UNSIGNED)) as longest_punt'),
                DB::raw('AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(punting, "$.punts")) AS UNSIGNED)) as avg_punts'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(punting, "$.punts")) AS UNSIGNED)) as total_punts'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(punting, "$.puntBlk")) AS UNSIGNED)) as total_blocked')
            ])
            ->whereNotNull('punting')
            ->first();
    }
}