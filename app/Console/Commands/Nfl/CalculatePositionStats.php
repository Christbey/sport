<?php

namespace App\Console\Commands\Nfl;

use App\Models\Nfl\DepthChart;
use App\Models\Nfl\NflPlayerStat;
use App\Models\Nfl\NflTeamSchedule;
use Illuminate\Console\Command;

class CalculatePositionStats extends Command
{
    protected $signature = 'nfl:calculate-position-stats';
    protected $description = 'Calculate the stats of players against each opposing team, considering their depth chart positions.';

    public function handle()
    {
        // Initialize an array to collect stats by team and position
        $groupedStats = [];

        // Fetch all player stats
        $playerStats = NflPlayerStat::all();

        foreach ($playerStats as $stat) {
            $this->processPlayerStat($stat, $groupedStats);
        }

        // Display the grouped stats and collect averages
        $averages = $this->displayGroupedStats($groupedStats);

        // Display the summary of average yards given up per team per position group
        $this->displaySummary($averages);

        $this->info('Player stats calculation completed.');
    }

    private function processPlayerStat($stat, &$groupedStats)
    {
        // Identify the game and opponent
        $game = NflTeamSchedule::where('espn_event_id', $stat->espn_event_id)->first();
        if (!$game) {
            $this->error("No game found for game_id: {$stat->game_id}");
            return; // Skip if no game data found
        }

        // Ensure team IDs are comparable (cast to integers if necessary)
        $statTeamId = (int)$stat->team_id;
        $homeTeamId = (int)$game->home_team_id;
        $awayTeamId = (int)$game->away_team_id;

        // Determine the opponent team ID and abbreviation
        $isPlayerHomeTeam = ($statTeamId === $homeTeamId);
        $opponentId = $isPlayerHomeTeam ? $awayTeamId : $homeTeamId;
        $opponentAbv = $isPlayerHomeTeam ? $game->away_team : $game->home_team;

        // Find player's depth chart information
        $depthChart = DepthChart::where('player_id', $stat->player_id)
            ->where('team_id', $statTeamId)
            ->first();

        if (!$depthChart) {
            $this->warn("No depth chart found for player_id: {$stat->player_id}, team_id: {$stat->team_id}");
            return; // Skip if no depth chart data found
        }

        $positionDepth = "{$depthChart->position}{$depthChart->depth_order}";
        $positionGroup = $depthChart->position; // For summary

        // Decode JSON stats fields
        $statArray = $this->getStatArrayByPosition($depthChart->position, $stat);

        if (!$statArray || !is_array($statArray) || empty(array_filter($statArray))) {
            $this->warn("No relevant stats data for player_id: {$stat->player_id}, position: {$depthChart->position}");
            return; // Skip if no relevant stats data
        }

        // Determine relevant yards or other metrics by position type
        $yardsLabel = $this->getYardsLabelByPosition($depthChart->position);
        $yards = $this->getYardsByPosition($depthChart->position, $statArray);

        if ($yards === null) {
            $this->warn("No yards data found for player_id: {$stat->player_id}, position: {$depthChart->position}");
            return; // Skip if no yards data
        }

        // Group stats by opponent team abbreviation and position group
        $playerName = $stat->long_name ?? 'Unknown';
        $groupedStats[$opponentAbv][$positionGroup][] = [
            'player_name' => $playerName,
            'position_depth' => $positionDepth,
            'yards' => $yards,
            'yards_label' => $yardsLabel,
        ];
    }

    private function getStatArrayByPosition($position, $stat)
    {
        // Decode the JSON fields if they are not already arrays
        $statFields = [
            'receiving' => $stat->receiving,
            'rushing' => $stat->rushing,
            'kicking' => $stat->kicking,
            'punting' => $stat->punting,
            'defense' => $stat->defense,
        ];

        foreach ($statFields as $key => $value) {
            if (is_string($value)) {
                $statFields[$key] = json_decode($value, true);
            }
        }

        return match ($position) {
            'WR', 'TE' => $statFields['receiving'],
            'RB' => $statFields['rushing'],
            'K' => $statFields['kicking'],
            'P' => $statFields['punting'],
            'DB', 'DL', 'LB' => $statFields['defense'],
            default => null,
        };
    }

    private function getYardsLabelByPosition($position)
    {
        return match ($position) {
            'WR', 'TE' => 'receiving yards',
            'RB' => 'rushing yards',
            'K' => 'kicking points',
            'P' => 'punting yards',
            'DB', 'DL', 'LB' => 'tackles',
            default => 'yards',
        };
    }

    private function getYardsByPosition($position, $statArray)
    {
        return match ($position) {
            'WR', 'TE' => isset($statArray['recYds']) ? (float)$statArray['recYds'] : null,
            'RB' => isset($statArray['rushYds']) ? (float)$statArray['rushYds'] : null,
            'K' => isset($statArray['kickingPts']) ? (float)$statArray['kickingPts'] : null,
            'P' => isset($statArray['puntYds']) ? (float)$statArray['puntYds'] : null,
            'DB', 'DL', 'LB' => isset($statArray['totalTackles']) ? (float)$statArray['totalTackles'] : null,
            default => null,
        };
    }

    private function displayGroupedStats($groupedStats)
    {
        $averages = []; // To collect averages per team and position group

        foreach ($groupedStats as $teamAbv => $positions) {
            $this->info("\nStats against team: {$teamAbv}");

            foreach ($positions as $positionGroup => $stats) {
                $totalYards = array_sum(array_column($stats, 'yards'));
                $totalPlayers = count($stats);
                $averageYards = $totalPlayers > 0 ? $totalYards / $totalPlayers : 0;
                $averageYards = round($averageYards, 2);
                $yardsLabel = $stats[0]['yards_label'];

                $this->info("Position: {$positionGroup} gives up an average of {$averageYards} {$yardsLabel}");

                foreach ($stats as $stat) {
                    $this->info("{$stat['player_name']} ({$stat['position_depth']}) has {$stat['yards']} {$stat['yards_label']} against {$teamAbv}");
                }

                // Store the average for the summary
                $averages[$teamAbv][$positionGroup] = [
                    'average_yards' => $averageYards,
                    'yards_label' => $yardsLabel,
                ];
            }
        }

        return $averages;
    }

    private function displaySummary($averages)
    {
        $this->info("\nSummary of Average Yards Given Up Per Team Per Position Group:");

        foreach ($averages as $teamAbv => $positions) {
            $this->info("Team: {$teamAbv}");
            foreach ($positions as $positionGroup => $data) {
                $this->info("  Position: {$positionGroup} - Average: {$data['average_yards']} {$data['yards_label']}");
            }
        }
    }
}
