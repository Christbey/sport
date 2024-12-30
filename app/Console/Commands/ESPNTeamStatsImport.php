<?php

namespace App\Console\Commands;

use App\Models\NbaEvent;
use App\Models\NbaPlayerStat;
use App\Models\NbaTeamStat;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class ESPNTeamStatsImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Example usage:
     *   php artisan espn:team-stats
     *   php artisan espn:team-stats --espn_id=401584703
     *   php artisan espn:team-stats --start=2024-01-01 --end=2024-01-31
     */
    protected $signature = 'espn:team-stats 
                            {--espn_id= : (Optional) Filter only a single event_id}
                            {--start= : Start date in Y-m-d format (e.g., 2024-01-01)}
                            {--end= : End date in Y-m-d format (e.g., 2024-01-31)}';

    /**
     * The console command description.
     */
    protected $description = 'Fetch team-level stats (and player stats) from ESPN for events within a date range.';

    public function handle()
    {
        // Parse date inputs
        $startDate = $this->option('start') ? Carbon::createFromFormat('Y-m-d', $this->option('start')) : null;
        $endDate = $this->option('end') ? Carbon::createFromFormat('Y-m-d', $this->option('end')) : null;

        // Validate dates
        if ($startDate && !$startDate->isValid()) {
            $this->error('Invalid start date format. Please use Y-m-d (e.g., 2024-01-01)');
            return Command::FAILURE;
        }

        if ($endDate && !$endDate->isValid()) {
            $this->error('Invalid end date format. Please use Y-m-d (e.g., 2024-01-31)');
            return Command::FAILURE;
        }

        if ($startDate && $endDate && $startDate->gt($endDate)) {
            $this->error('Start date cannot be after end date');
            return Command::FAILURE;
        }

        // Get the filter espn_id if provided
        $filterEspnId = $this->option('espn_id');

        // Build the query
        $query = NbaEvent::query();

        // Apply filters
        if ($filterEspnId) {
            $this->info("Filtering by espn_id={$filterEspnId}...");
            $query->where('espn_id', $filterEspnId);
        }

        if ($startDate) {
            $query->whereDate('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('date', '<=', $endDate);
        }

        // Get all matching events
        $events = $query->orderBy('date')->get();

        if ($events->isEmpty()) {
            $this->warn('No matching NbaEvent records found for the specified criteria.');
            return Command::SUCCESS;
        }

        $this->info(sprintf(
            'Found %d events between %s and %s',
            $events->count(),
            $startDate ? $startDate->format('Y-m-d') : 'any date',
            $endDate ? $endDate->format('Y-m-d') : 'any date'
        ));

        $client = new Client();

        foreach ($events as $event) {
            $eventId = $event->espn_id;
            $eventDate = $event->date ? $event->date->format('Y-m-d H:i:s') : null;

            $this->info("\nProcessing Event: {$eventId} ({$eventDate})");

            // We'll handle both home_team_id and away_team_id
            $teamsToFetch = [$event->home_team_id, $event->away_team_id];
            foreach ($teamsToFetch as $teamId) {
                if (empty($teamId)) {
                    $this->warn("Event espn_id={$eventId} is missing a team_id. Skipping...");
                    continue;
                }

                $opponentId = ($teamId === $event->home_team_id)
                    ? $event->away_team_id
                    : $event->home_team_id;

                $this->info("Fetching Team Stats for team_id={$teamId}...");

                $url = "http://sports.core.api.espn.com/v2/sports/basketball/leagues/nba/events/{$eventId}/competitions/{$eventId}/competitors/{$teamId}/statistics?lang=en&region=us";

                try {
                    // Fetch team-level stats
                    $resp = $client->get($url);
                    $teamData = json_decode($resp->getBody(), true);

                    $competitionRef = $teamData['competition']['$ref'] ?? null;
                    $teamRef = $teamData['team']['$ref'] ?? null;
                    $splits = $teamData['splits'] ?? null;

                    // Store team stats
                    NbaTeamStat::updateOrCreate(
                        [
                            'event_id' => $eventId,
                            'team_id' => $teamId,
                        ],
                        [
                            'opponent_id' => $opponentId,
                            'event_date' => $eventDate,
                            'splits_json' => $splits,
                            'team_ref' => $teamRef,
                            'competition_ref' => $competitionRef,
                        ]
                    );

                    $this->info('✓ Stored Team Stats');

                    // Process player stats
                    if (!empty($splits['categories']) && is_array($splits['categories'])) {
                        foreach ($splits['categories'] as $category) {
                            if (!empty($category['athletes']) && is_array($category['athletes'])) {
                                foreach ($category['athletes'] as $athleteItem) {
                                    $statsRef = $athleteItem['statistics']['$ref'] ?? null;
                                    if ($statsRef) {
                                        try {
                                            $playerResp = $client->get($statsRef);
                                            $playerJson = json_decode($playerResp->getBody(), true);

                                            $playerSplits = $playerJson['splits'] ?? null;
                                            $competitionLink = $playerJson['competition']['$ref'] ?? null;
                                            $athleteLink = $playerJson['athlete']['$ref'] ?? null;

                                            // Extract player ID
                                            $playerId = null;
                                            if (preg_match('/athletes\/(\d+)\?/', $athleteLink, $m)) {
                                                $playerId = $m[1];
                                            }
                                            if (!$playerId) {
                                                $playerId = 'unknown';
                                            }

                                            // Store player stats
                                            NbaPlayerStat::updateOrCreate(
                                                [
                                                    'event_id' => $eventId,
                                                    'player_id' => $playerId,
                                                ],
                                                [
                                                    'team_id' => $teamId,
                                                    'opponent_id' => $opponentId,
                                                    'event_date' => $eventDate,
                                                    'competition_ref' => $competitionLink,
                                                    'athlete_ref' => $athleteLink,
                                                    'splits_json' => $playerSplits,
                                                ]
                                            );

                                            $this->line("  ✓ Stored stats for player_id={$playerId}");
                                        } catch (Exception $e) {
                                            $this->error('  × Failed to fetch player stats: ' . $e->getMessage());
                                        }
                                    }
                                }
                            }
                        }
                    }

                } catch (Exception $e) {
                    $this->error('× Failed to fetch team stats: ' . $e->getMessage());
                }

                $this->line('----------------------');
            }
        }

        $this->info("\nDone processing all events!");
        return Command::SUCCESS;
    }
}