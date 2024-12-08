<?php

namespace App\Repositories\Nfl\Interfaces;

interface TeamStatsRepositoryInterface
{

    public function getAveragePoints(string $teamFilter = null, string $locationFilter = null, string $conferenceFilter = null, string $divisionFilter = null): array;

    public function getTableHeadings(string $queryType): array;

    public function getMetaData(): array;

    public function getHalfScoring(?string $teamFilter = null, ?string $locationFilter = null, ?string $conferenceFilter = null, ?string $divisionFilter = null, ?string $opponentFilter = null): array;

    public function getQuarterScoring(?string $teamFilter = null, ?string $locationFilter = null, ?string $conferenceFilter = null, ?string $divisionFilter = null, ?string $opponentFilter = null): array;

    public function getSituationalPerformance(
        ?string $teamFilter = null,
        ?string $locationFilter = null,
        ?string $againstConference = null
    ): array;

    public function getQuarterComebacks(?string $teamFilter = null, ?string $locationFilter = null, ?string $conferenceFilter = null, ?string $divisionFilter = null): array;

    public function getScoringStreaks(?string $teamFilter = null, ?string $locationFilter = null, ?string $conferenceFilter = null, ?string $divisionFilter = null): array;

    public function getBestReceivers(?string $teamFilter = null, ?int $week = null, ?int $startWeek = null, ?int $endWeek = null): array;

    public function getBestRushers(?string $teamFilter = null, ?int $week = null, ?int $startWeek = null, ?int $endWeek = null): array;

    public
    function getBestTacklers(?string $teamFilter = null, ?int $week = null, ?int $startWeek = null, ?int $endWeek = null): array;

    public function getBigPlaymakers(?string $teamFilter = null): array;

    public function getDefensivePlaymakers(?string $teamFilter = null): array;

    public function getDualThreatPlayers(?string $teamFilter = null): array;

    public function getOffensiveConsistency(?string $teamFilter = null): array;

    public function getNflTeamStats(?string $teamFilter = null): array;

    public function getOverUnderAnalysis(?string $teamFilter = null, ?string $locationFilter = null, ?string $conferenceFilter = null, ?string $divisionFilter = null): array;

    public function getTeamVsConference(?string $teamFilter = null, ?string $locationFilter = null, ?string $conferenceFilter = null, ?string $divisionFilter = null): array;

    public function getPlayerVsConference(
        ?string $teamFilter = null,
        ?string $playerFilter = null,
        ?string $conferenceFilter = null
    ): array;

    public function getPlayerVsDivision(?string $teamFilter = null): array;

    public function getConferenceStats(?string $teamFilter = null): array;

    public function getDivisionStats(?string $teamFilter = null): array;

    public function getTeamMatchupEdge(
        ?string $teamFilter = null,
        ?string $teamAbv1 = null,
        ?string $teamAbv2 = null,
        ?int    $week = null,
        ?string $locationFilter = null
    ): array;

    public function getFirstHalfTendencies(
        ?string $teamFilter = null,
        ?string $againstConference = null,
        ?string $locationFilter = null
    ): array;

    public function getOpponentAdjustedStats(int $teamId, int $gamesBack): array;


}