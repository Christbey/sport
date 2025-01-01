<?php

namespace App\Console\Commands;

use App\Models\NbaEvent;
use App\Models\NbaPlayerStat;
use App\Models\NbaTeamStat;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

// Your model for team-level stats

// Your model for player-level stats

class ESPNTeamStatsImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'espn:team-stats 
                        {event_id? : The specific ESPN event ID to fetch stats for} 
                        {team_id? : The specific team ID to fetch stats for} 
                        {--start= : The start date for fetching stats (format: YYYY-MM-DD)} 
                        {--end= : The end date for fetching stats (format: YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch team-level and player-level stats from ESPN. 
                          Supports specific event/team or stats for a date range.';

    public function handle()
    {
        // Validate and parse the date range
        $startDate = $this->option('start') ? Carbon::createFromFormat('Y-m-d', $this->option('start')) : Carbon::now()->toDateString();
        $endDate = $this->option('end') ? Carbon::createFromFormat('Y-m-d', $this->option('end')) : $startDate;

        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            $this->error('Start date cannot be after the end date.');
            return Command::FAILURE;
        }

        // Specific event ID or fetch events within the date range
        $eventId = $this->argument('event_id');
        $eventsQuery = NbaEvent::query();

        if ($eventId) {
            $eventsQuery->where('espn_id', $eventId);
        } else {
            $eventsQuery->whereBetween('date', [$startDate, $endDate]);
        }

        $events = $eventsQuery->get();

        if ($events->isEmpty()) {
            $this->warn('No events found for the specified criteria.');
            return Command::FAILURE;
        }

        $client = new Client();

        foreach ($events as $nbaEvent) {
            $this->processEvent($client, $nbaEvent);
        }

        $this->info("Completed processing events from {$startDate} to {$endDate}.");
        return Command::SUCCESS;
    }

    protected function processEvent(Client $client, $nbaEvent)
    {
        $eventId = $nbaEvent->espn_id;
        $teamId = $nbaEvent->home_team_id; // Adjust this as needed
        $eventDate = $nbaEvent->date;
        $opponentId = $nbaEvent->away_team_id;

        // ESPN API URL for team stats
        $url = "http://sports.core.api.espn.com/v2/sports/basketball/leagues/nba/events/{$eventId}/competitions/{$eventId}/competitors/{$teamId}/statistics?lang=en&region=us";

        try {
            // Fetch team-level stats
            $response = $client->get($url);
            $teamData = json_decode($response->getBody(), true);

            $this->info("Fetched Team Stats for event_id={$eventId}, team_id={$teamId}");

            // Parse and store team-level stats
            $this->storeTeamStats($teamData, $eventId, $teamId, $opponentId, $eventDate);

            // Parse and store player-level stats
            if (!empty($teamData['splits']['categories'])) {
                foreach ($teamData['splits']['categories'] as $category) {
                    $this->processPlayerStats($client, $category, $eventId, $teamId, $opponentId, $eventDate);
                }
            }
        } catch (Exception $e) {
            $this->error("Failed to process event_id={$eventId}: " . $e->getMessage());
        }
    }

    protected function storeTeamStats($teamData, $eventId, $teamId, $opponentId, $eventDate)
    {
        $competitionRef = $teamData['competition']['$ref'] ?? null;
        $teamRef = $teamData['team']['$ref'] ?? null;

        // Initialize the array to hold team stats
        $teamStats = [
            'event_id' => $eventId,
            'team_id' => $teamId,
            'opponent_id' => $opponentId,
            'event_date' => $eventDate,
            'splits_json' => $teamData['splits'] ?? null,
            'team_ref' => $teamRef,
            'competition_ref' => $competitionRef,
        ];

        // Parse the splits JSON for stats
        if (isset($teamData['splits']['categories'])) {
            foreach ($teamData['splits']['categories'] as $category) {
                foreach ($category['stats'] as $stat) {
                    $statName = $stat['name'];
                    $statValue = $stat['value'];

                    // Map stat names to database columns
                    $columnMapping = [
                        'blocks' => 'blocks',
                        'defensiveRebounds' => 'defensive_rebounds',
                        'steals' => 'steals',
                        'turnoverPoints' => 'turnover_points',
                        'avgDefensiveRebounds' => 'avg_defensive_rebounds',
                        'avgBlocks' => 'avg_blocks',
                        'avgSteals' => 'avg_steals',
                        'avg48DefensiveRebounds' => 'avg_48_defensive_rebounds',
                        'avg48Blocks' => 'avg_48_blocks',
                        'avg48Steals' => 'avg_48_steals',
                        'largestLead' => 'largest_lead',
                        'disqualifications' => 'disqualifications',
                        'flagrantFouls' => 'flagrant_fouls',
                        'fouls' => 'fouls',
                        'ejections' => 'ejections',
                        'technicalFouls' => 'technical_fouls',
                        'rebounds' => 'rebounds',
                        'vorp' => 'vorp',
                        'avgMinutes' => 'avg_minutes',
                        'NBARating' => 'nba_rating',
                        'avgRebounds' => 'avg_rebounds',
                        'avgFouls' => 'avg_fouls',
                        'avgFlagrantFouls' => 'avg_flagrant_fouls',
                        'avgTechnicalFouls' => 'avg_technical_fouls',
                        'avgEjections' => 'avg_ejections',
                        'avgDisqualifications' => 'avg_disqualifications',
                        'assistTurnoverRatio' => 'assist_turnover_ratio',
                        'stealFoulRatio' => 'steal_foul_ratio',
                        'blockFoulRatio' => 'block_foul_ratio',
                        'avgTeamRebounds' => 'avg_team_rebounds',
                        'totalRebounds' => 'total_rebounds',
                        'totalTechnicalFouls' => 'total_technical_fouls',
                        'teamAssistTurnoverRatio' => 'team_assist_turnover_ratio',
                        'stealTurnoverRatio' => 'steal_turnover_ratio',
                        'avg48Rebounds' => 'avg_48_rebounds',
                        'avg48Fouls' => 'avg_48_fouls',
                        'avg48FlagrantFouls' => 'avg_48_flagrant_fouls',
                        'avg48TechnicalFouls' => 'avg_48_technical_fouls',
                        'avg48Ejections' => 'avg_48_ejections',
                        'avg48Disqualifications' => 'avg_48_disqualifications',
                        'gamesPlayed' => 'games_played',
                        'gamesStarted' => 'games_started',
                        'doubleDouble' => 'double_double',
                        'tripleDouble' => 'triple_double',
                        'assists' => 'assists',
                        'fieldGoalsMade' => 'field_goals_made',
                        'fieldGoalsAttempted' => 'field_goals_attempted',
                        'fieldGoalPct' => 'field_goal_pct',
                        'freeThrowsMade' => 'free_throws_made',
                        'freeThrowsAttempted' => 'free_throws_attempted',
                        'freeThrowPct' => 'free_throw_pct',
                        'offensiveRebounds' => 'offensive_rebounds',
                        'points' => 'points',
                        'turnovers' => 'turnovers',
                        'threePointPct' => 'three_point_pct',
                        'threePointFieldGoalsAttempted' => 'three_point_field_goals_attempted',
                        'threePointFieldGoalsMade' => 'three_point_field_goals_made',
                        'teamTurnovers' => 'team_turnovers',
                        'totalTurnovers' => 'total_turnovers',
                        'pointsInPaint' => 'points_in_paint',
                        'brickIndex' => 'brick_index',
                        'fastBreakPoints' => 'fast_break_points',
                        'avgFieldGoalsMade' => 'avg_field_goals_made',
                        'avgFieldGoalsAttempted' => 'avg_field_goals_attempted',
                        'avgThreePointFieldGoalsMade' => 'avg_three_point_field_goals_made',
                        'avgThreePointFieldGoalsAttempted' => 'avg_three_point_field_goals_attempted',
                        'avgFreeThrowsMade' => 'avg_free_throws_made',
                        'avgFreeThrowsAttempted' => 'avg_free_throws_attempted',
                        'avgPoints' => 'avg_points',
                        'avgOffensiveRebounds' => 'avg_offensive_rebounds',
                        'avgAssists' => 'avg_assists',
                        'avgTurnovers' => 'avg_turnovers',
                        'offensiveReboundPct' => 'offensive_rebound_pct',
                        'estimatedPossessions' => 'estimated_possessions',
                        'avgEstimatedPossessions' => 'avg_estimated_possessions',
                        'pointsPerEstimatedPossessions' => 'points_per_estimated_possession',
                        'avgTeamTurnovers' => 'avg_team_turnovers',
                        'avgTotalTurnovers' => 'avg_total_turnovers',
                        'threePointFieldGoalPct' => 'three_point_field_goal_pct',
                        'twoPointFieldGoalsMade' => 'two_point_field_goals_made',
                        'twoPointFieldGoalsAttempted' => 'two_point_field_goals_attempted',
                        'avgTwoPointFieldGoalsMade' => 'avg_two_point_field_goals_made',
                        'avgTwoPointFieldGoalsAttempted' => 'avg_two_point_field_goals_attempted',
                        'twoPointFieldGoalPct' => 'two_point_field_goal_pct',
                        'shootingEfficiency' => 'shooting_efficiency',
                        'scoringEfficiency' => 'scoring_efficiency',
                        'avg48FieldGoalsMade' => 'avg_48_field_goals_made',
                        'avg48FieldGoalsAttempted' => 'avg_48_field_goals_attempted',
                        'avg48ThreePointFieldGoalsMade' => 'avg_48_three_point_field_goals_made',
                        'avg48ThreePointFieldGoalsAttempted' => 'avg_48_three_point_field_goals_attempted',
                        'avg48FreeThrowsMade' => 'avg_48_free_throws_made',
                        'avg48FreeThrowsAttempted' => 'avg_48_free_throws_attempted',
                        'avg48Points' => 'avg_48_points',
                        'avg48OffensiveRebounds' => 'avg_48_offensive_rebounds',
                        'avg48Assists' => 'avg_48_assists',
                        'avg48Turnovers' => 'avg_48_turnovers',
                    ];

                    if (array_key_exists($statName, $columnMapping)) {
                        $teamStats[$columnMapping[$statName]] = $statValue;
                    }
                }
            }
        }

        // Save or update the team stats in the database
        NbaTeamStat::updateOrCreate(
            [
                'event_id' => $eventId,
                'team_id' => $teamId,
            ],
            $teamStats
        );

        $this->info("Stored Team Stats for event_id={$eventId}, team_id={$teamId}.");
    }

    protected function processPlayerStats(Client $client, $category, $eventId, $teamId, $opponentId, $eventDate)
    {
        if (!empty($category['athletes'])) {
            foreach ($category['athletes'] as $athleteItem) {
                $statsRef = $athleteItem['statistics']['$ref'] ?? null;

                if ($statsRef) {
                    try {
                        $response = $client->get($statsRef);
                        $playerJson = json_decode($response->getBody(), true);

                        $this->storePlayerStats($playerJson, $eventId, $teamId, $opponentId, $eventDate);
                    } catch (Exception $e) {
                        $this->error("Failed to fetch stats for statsRef={$statsRef}: " . $e->getMessage());
                    }
                }
            }
        }
    }

    protected function storePlayerStats($playerJson, $eventId, $teamId, $opponentId, $eventDate)
    {
        $athleteLink = $playerJson['athlete']['$ref'] ?? null;
        $playerId = null;

        if ($athleteLink && preg_match('/athletes\/(\d+)\?/', $athleteLink, $m)) {
            $playerId = $m[1];
        }

        if (!$playerId) {
            $this->warn('Invalid player ID in stats JSON.');
            return;
        }

        $playerStats = [
            'event_id' => $eventId,
            'player_id' => $playerId,
            'team_id' => $teamId,
            'opponent_id' => $opponentId,
            'event_date' => $eventDate,
            'competition_ref' => $playerJson['competition']['$ref'] ?? null,
            'athlete_ref' => $athleteLink,
        ];

        foreach ($playerJson['splits']['categories'] as $category) {
            foreach ($category['stats'] as $stat) {
                $statName = $stat['name'] ?? null;
                $statValue = $stat['value'] ?? null;

                if ($statName && $statValue !== null) {
                    $columnName = $this->mapStatToColumn($statName);
                    if ($columnName) {
                        $playerStats[$columnName] = $statValue;
                    }
                }
            }
        }

        NbaPlayerStat::updateOrCreate(
            ['event_id' => $eventId, 'player_id' => $playerId],
            $playerStats
        );

        $this->info("Stored stats for player_id={$playerId}, event_id={$eventId}.");
    }

    protected function mapStatToColumn(string $statName): ?string
    {
        // Define the mapping from API stat names to your database columns
        $map = [
            'blocks' => 'blocks',
            'defensiveRebounds' => 'defensive_rebounds',
            'steals' => 'steals',
            'avgDefensiveRebounds' => 'avg_defensive_rebounds',
            'avgBlocks' => 'avg_blocks',
            'avgSteals' => 'avg_steals',
            'avg48DefensiveRebounds' => 'avg_48_defensive_rebounds',
            'avg48Blocks' => 'avg_48_blocks',
            'avg48Steals' => 'avg_48_steals',
            'largestLead' => 'largest_lead',
            'disqualifications' => 'disqualifications',
            'flagrantFouls' => 'flagrant_fouls',
            'fouls' => 'fouls',
            'ejections' => 'ejections',
            'technicalFouls' => 'technical_fouls',
            'rebounds' => 'rebounds',
            'minutes' => 'minutes',
            'avgMinutes' => 'avg_minutes',
            'NBARating' => 'nba_rating',
            'plusMinus' => 'plus_minus',
            'avgRebounds' => 'avg_rebounds',
            'avgFouls' => 'avg_fouls',
            'avgFlagrantFouls' => 'avg_flagrant_fouls',
            'avgTechnicalFouls' => 'avg_technical_fouls',
            'avgEjections' => 'avg_ejections',
            'avgDisqualifications' => 'avg_disqualifications',
            'assistTurnoverRatio' => 'assist_turnover_ratio',
            'stealFoulRatio' => 'steal_foul_ratio',
            'blockFoulRatio' => 'block_foul_ratio',
            'avgTeamRebounds' => 'avg_team_rebounds',
            'totalRebounds' => 'total_rebounds',
            'totalTechnicalFouls' => 'total_technical_fouls',
            'teamAssistTurnoverRatio' => 'team_assist_turnover_ratio',
            'stealTurnoverRatio' => 'steal_turnover_ratio',
            'avg48Rebounds' => 'avg_48_rebounds',
            'avg48Fouls' => 'avg_48_fouls',
            'avg48FlagrantFouls' => 'avg_48_flagrant_fouls',
            'avg48TechnicalFouls' => 'avg_48_technical_fouls',
            'avg48Ejections' => 'avg_48_ejections',
            'avg48Disqualifications' => 'avg_48_disqualifications',
            'r40' => 'r40',
            'gamesPlayed' => 'games_played',
            'gamesStarted' => 'games_started',
            'doubleDouble' => 'double_double',
            'tripleDouble' => 'triple_double',
            'assists' => 'assists',
            'fieldGoals' => 'field_goals',
            'fieldGoalsAttempted' => 'field_goals_attempted',
            'fieldGoalsMade' => 'field_goals_made',
            'fieldGoalPct' => 'field_goal_pct',
            'freeThrows' => 'free_throws',
            'freeThrowPct' => 'free_throw_pct',
            'freeThrowsAttempted' => 'free_throws_attempted',
            'freeThrowsMade' => 'free_throws_made',
            'offensiveRebounds' => 'offensive_rebounds',
            'points' => 'points',
            'turnovers' => 'turnovers',
            'threePointPct' => 'three_point_pct',
            'threePointFieldGoalsAttempted' => 'three_point_field_goals_attempted',
            'threePointFieldGoalsMade' => 'three_point_field_goals_made',
            'totalTurnovers' => 'total_turnovers',
            'pointsInPaint' => 'points_in_paint',
            'brickIndex' => 'brick_index',
            'avgFieldGoalsMade' => 'avg_field_goals_made',
            'avgFieldGoalsAttempted' => 'avg_field_goals_attempted',
            'avgThreePointFieldGoalsMade' => 'avg_three_point_field_goals_made',
            'avgThreePointFieldGoalsAttempted' => 'avg_three_point_field_goals_attempted',
            'avgFreeThrowsMade' => 'avg_free_throws_made',
            'avgFreeThrowsAttempted' => 'avg_free_throws_attempted',
            'avgPoints' => 'avg_points',
            'avgOffensiveRebounds' => 'avg_offensive_rebounds',
            'avgAssists' => 'avg_assists',
            'avgTurnovers' => 'avg_turnovers',
            'offensiveReboundPct' => 'offensive_rebound_pct',
            'estimatedPossessions' => 'estimated_possessions',
            'avgEstimatedPossessions' => 'avg_estimated_possessions',
            'pointsPerEstimatedPossession' => 'points_per_estimated_possession',
            'avgTeamTurnovers' => 'avg_team_turnovers',
            'avgTotalTurnovers' => 'avg_total_turnovers',
            'threePointFieldGoalPct' => 'three_point_field_goal_pct',
            'twoPointFieldGoalsMade' => 'two_point_field_goals_made',
            'twoPointFieldGoalsAttempted' => 'two_point_field_goals_attempted',
            'avgTwoPointFieldGoalsMade' => 'avg_two_point_field_goals_made',
            'avgTwoPointFieldGoalsAttempted' => 'avg_two_point_field_goals_attempted',
            'twoPointFieldGoalPct' => 'two_point_field_goal_pct',
            'shootingEfficiency' => 'shooting_efficiency',
            'scoringEfficiency' => 'scoring_efficiency',
            'avg48FieldGoalsMade' => 'avg_48_field_goals_made',
            'avg48FieldGoalsAttempted' => 'avg_48_field_goals_attempted',
            'avg48ThreePointFieldGoalsMade' => 'avg_48_three_point_field_goals_made',
            'avg48ThreePointFieldGoalsAttempted' => 'avg_48_three_point_field_goals_attempted',
            'avg48FreeThrowsMade' => 'avg_48_free_throws_made',
            'avg48FreeThrowsAttempted' => 'avg_48_free_throws_attempted',
            'avg48Points' => 'avg_48_points',
            'avg48OffensiveRebounds' => 'avg_48_offensive_rebounds',
            'avg48Assists' => 'avg_48_assists',
            'avg48Turnovers' => 'avg_48_turnovers',
            'p40' => 'p40',
            'a40' => 'a40',
        ];

        return $map[$statName] ?? null; // Return null if the stat is not mapped
    }

}
