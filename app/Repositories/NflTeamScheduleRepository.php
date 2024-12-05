<?php

namespace App\Repositories;

use App\Models\Nfl\NflTeam;
use App\Models\Nfl\NflTeamSchedule;
use App\Repositories\Nfl\Interfaces\NflTeamScheduleRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class NflTeamScheduleRepository implements NflTeamScheduleRepositoryInterface
{

    private const DEFAULT_COLUMNS = [
        'game_id',
        'season',
        'season_type',
        'game_week',
        'home_team',
        'away_team',
        'home_team_id',
        'away_team_id',
        'game_date',
        'game_status',
        'game_status_code',
        'home_pts',
        'away_pts',
        'home_result',
        'away_result',
        'game_time',
        'status_type_detail',
        'home_team_record',
        'away_team_record'
    ];
    /**
     * Cache for team conferences to reduce database queries.
     *
     * @var array
     */
    private array $teamConferencesCache = [];

    /**
     * Update or create a game record using Rapid API data.
     *
     * @param array $gameData Rapid API game data.
     * @param string $season Season year.
     */
    public function updateOrCreateFromRapidApi(array $gameData, string $season): void
    {
        // Extract week number from 'gameWeek'
        $weekNumber = $this->extractWeekNumber($gameData['gameWeek'] ?? '');

        // Extract team abbreviations
        $homeTeamAbv = $gameData['home'] ?? null;
        $awayTeamAbv = $gameData['away'] ?? null;

        // Fetch team IDs
        $homeTeamId = $gameData['teamIDHome'] ?? null;
        $awayTeamId = $gameData['teamIDAway'] ?? null;

        // Fetch conferences using caching
        $homeTeamConference = $this->getTeamConference($homeTeamId);
        $awayTeamConference = $this->getTeamConference($awayTeamId);

        // Determine if it's a conference competition
        $conferenceCompetition = ($homeTeamConference && $awayTeamConference && $homeTeamConference === $awayTeamConference);

        // Game date
        $gameDate = isset($gameData['gameDate']) ? Carbon::createFromFormat('Ymd', $gameData['gameDate'])->toDateString() : null;

        // Calculate team records
        $homeTeamRecord = null;
        $awayTeamRecord = null;

        if ($gameDate) {
            $homeTeamRecord = $this->calculateTeamRecord($homeTeamAbv, $season, $gameDate);
            $awayTeamRecord = $this->calculateTeamRecord($awayTeamAbv, $season, $gameDate);
        }

        // UID
        $espnEventId = $gameData['espnID'] ?? null;
        $uid = $espnEventId ? "s:20~l:28~e:$espnEventId" : null;

        // Status type detail
        $gameTimeEpoch = isset($gameData['gameTime_epoch']) ? (int)$gameData['gameTime_epoch'] : null;
        if ($gameTimeEpoch) {
            $gameTime = Carbon::createFromTimestamp($gameTimeEpoch);
            if ($gameTime->isPast()) {
                $statusTypeDetail = 'Completed';
            } else {
                $statusTypeDetail = $gameTime->format('l, F j, Y g:i:s A');
            }
        } else {
            $statusTypeDetail = null;
        }

        // Map data to model attributes
        $data = [
            'game_id' => $gameData['gameID'] ?? null,
            'season' => $season,
            'season_type' => $gameData['seasonType'] ?? null,
            'game_week' => $weekNumber,
            'home_team' => $homeTeamAbv,
            'away_team' => $awayTeamAbv,
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'game_date' => $gameDate,
            'game_status' => $gameData['gameStatus'] ?? null,
            'game_status_code' => $gameData['gameStatusCode'] ?? null,
            'home_pts' => isset($gameData['homePts']) ? (int)$gameData['homePts'] : null,
            'away_pts' => isset($gameData['awayPts']) ? (int)$gameData['awayPts'] : null,
            'home_result' => $gameData['homeResult'] ?? null,
            'away_result' => $gameData['awayResult'] ?? null,
            'attendance' => isset($gameData['attendance']) ? (int)str_replace(',', '', $gameData['attendance']) : null,
            'game_time' => $gameData['gameTime'] ?? null,
            'game_time_epoch' => $gameTimeEpoch,
            'neutral_site' => isset($gameData['neutralSite']) ? filter_var($gameData['neutralSite'], FILTER_VALIDATE_BOOLEAN) : null,
            'espn_event_id' => $espnEventId,
            'uid' => $uid,
            'status_type_detail' => $statusTypeDetail,
            'team_abv' => $homeTeamAbv,
            'home_team_record' => $homeTeamRecord,
            'away_team_record' => $awayTeamRecord,
            'short_name' => isset($awayTeamAbv, $homeTeamAbv) ? "{$awayTeamAbv} @ {$homeTeamAbv}" : null,
            'conference_competition' => $conferenceCompetition,
            'referees' => isset($gameData['Referees']) ? $this->parseReferees($gameData['Referees']) : null,

        ];

        // Remove null values
        $data = array_filter($data, fn($value) => !is_null($value));

        // Update or create the record
        NflTeamSchedule::updateOrCreate(
            ['game_id' => $data['game_id']],
            $data
        );
    }

    /**
     * Extract the week number from the game week string.
     *
     * @param string $gameWeek Game week string.
     * @return int
     */
    private function extractWeekNumber(string $gameWeek): int
    {
        preg_match('/\d+/', $gameWeek, $matches);
        return isset($matches[0]) ? (int)$matches[0] : 0;
    }

    /**
     * Get the conference abbreviation for a team, using caching to minimize database queries.
     *
     * @param int|null $teamId Team ID.
     * @return string|null
     */
    private function getTeamConference(?int $teamId): ?string
    {
        if (!$teamId) {
            return null;
        }

        if (isset($this->teamConferencesCache[$teamId])) {
            return $this->teamConferencesCache[$teamId];
        }

        $team = NflTeam::where('team_id', $teamId)->first(['conference_abv']);
        $conference = $team->conference_abv ?? null;

        $this->teamConferencesCache[$teamId] = $conference;

        return $conference;
    }

    /**
     * Calculate the team record up to a specific game date.
     *
     * @param string $teamAbv Team abbreviation.
     * @param string $season Season year.
     * @param string $gameDate Game date.
     * @return string Team record in "W - L" format.
     */
    private function calculateTeamRecord(string $teamAbv, string $season, string $gameDate): string
    {
        $games = NflTeamSchedule::where('season', $season)
            ->where('season_type', 'Regular Season')
            ->where('game_date', '<', $gameDate)
            ->where(function ($query) use ($teamAbv) {
                $query->where('home_team', $teamAbv)
                    ->orWhere('away_team', $teamAbv);
            })
            ->get(['home_team', 'away_team', 'home_result', 'away_result']);

        $wins = 0;
        $losses = 0;

        foreach ($games as $game) {
            if ($game->home_team == $teamAbv && $game->home_result == 'W') {
                $wins++;
            } elseif ($game->away_team == $teamAbv && $game->away_result == 'W') {
                $wins++;
            } elseif ($game->home_team == $teamAbv && $game->home_result == 'L') {
                $losses++;
            } elseif ($game->away_team == $teamAbv && $game->away_result == 'L') {
                $losses++;
            }
        }

        return "{$wins} - {$losses}";
    }

    private function parseReferees(string $referees): ?array
    {
        if (empty($referees)) {
            return null;
        }

        // Split the string by commas and trim whitespace
        $refereesArray = array_map('trim', explode(',', $referees));
        return $refereesArray;
    }

    /**
     * Find a game by its game ID.
     *
     * @param string $gameId Game ID.
     * @return NflTeamSchedule|null
     */
    public function findByGameId(string $gameId): ?NflTeamSchedule
    {
        return NflTeamSchedule::where('game_id', $gameId)->first();
    }

    /**
     * Get the schedule for a specific team.
     *
     * @param string $teamId Team ID.
     * @return array
     */
    public function getScheduleByTeam(string $teamId): array
    {
        return NflTeamSchedule::where('home_team_id', $teamId)
            ->orWhere('away_team_id', $teamId)
            ->get()
            ->toArray();
    }

    /**
     * Get all schedules.
     *
     * @return array
     */
    public function getAllSchedules(): array
    {
        return NflTeamSchedule::all()->toArray();
    }

    /**
     * Get the schedule for a team within a date range.
     *
     * @param string $teamId Team ID.
     * @param array $range Date range.
     * @return array
     */
    public function getScheduleByDateRange(string $teamId, array $range): array
    {
        return NflTeamSchedule::where('team_id', $teamId)
            ->whereBetween('game_date', $range)
            ->get()
            ->toArray();
    }

    /**
     * Get recent games for a team.
     *
     * @param string $teamId Team ID.
     * @param int $limit Number of games to retrieve.
     * @return Collection
     */
    public function getRecentGames(string $teamId, int $limit = 5): Collection
    {
        return NflTeamSchedule::where(function ($query) use ($teamId) {
            $query->where('home_team_id', $teamId)
                ->orWhere('away_team_id', $teamId);
        })
            ->whereDate('game_date', '<', now())
            ->orderBy('game_date', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getSchedulesByGameIds(Collection $gameIds): Collection
    {
        return NflTeamSchedule::whereIn('game_id', $gameIds)->get()->keyBy('game_id');
    }

    public function getTeamLast3Games(int $teamId, string $currentGameId): Collection
    {
        return NflTeamSchedule::where(function ($query) use ($teamId) {
            $query->where('home_team_id', $teamId)
                ->orWhere('away_team_id', $teamId);
        })
            ->where('game_id', '<', $currentGameId)
            ->orderBy('game_date', 'desc')
            ->limit(3)
            ->get();
    }

    public function findByGameIds(Collection $gameIds): Collection
    {
        return NflTeamSchedule::whereIn('game_id', $gameIds)
            ->select(self::DEFAULT_COLUMNS)
            ->get();
    }
}
