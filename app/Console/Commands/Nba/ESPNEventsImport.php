<?php

namespace App\Console\Commands\Nba;

use App\Models\NbaEvent;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class ESPNEventsImport extends Command
{
    protected $signature = 'espn:events-import 
                          {--start= : Start date in Y-m-d format (e.g., 2024-01-01)} 
                          {--end= : End date in Y-m-d format (e.g., 2024-01-31)}';

    protected $description = 'Imports NBA events (type=2) for the 2024 season from ESPN API within a specified date range.';

    public function handle()
    {
        // Validate and parse date inputs
        $startDate = $this->option('start') ? Carbon::createFromFormat('Y-m-d', $this->option('start')) : null;
        $endDate = $this->option('end') ? Carbon::createFromFormat('Y-m-d', $this->option('end')) : null;

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

        // Build the endpoint URL with date filters if provided
        $endpoint = 'https://sports.core.api.espn.com/v2/sports/basketball/leagues/nba/seasons/2025/types/2/events';
        $queryParams = [];

        if ($startDate) {
            $queryParams['dates'] = $startDate->format('Ymd');
            if ($endDate) {
                $queryParams['dates'] .= '-' . $endDate->format('Ymd');
            }
        }

        $client = new Client();
        $currentPage = 1;

        do {
            // Add pagination and date filters to URL
            $queryParams['page'] = $currentPage;
            $paginatedUrl = $endpoint . '?' . http_build_query($queryParams);

            $this->info("Fetching page {$currentPage}: {$paginatedUrl}");

            try {
                $response = $client->get($paginatedUrl);
                $data = json_decode($response->getBody(), true);
            } catch (Exception $e) {
                $this->error('Failed to fetch events list: ' . $e->getMessage());
                return Command::FAILURE;
            }

            $pageCount = $data['pageCount'] ?? 1;
            $items = $data['items'] ?? [];

            foreach ($items as $item) {
                if (empty($item['$ref'])) {
                    continue;
                }

                $eventUrl = $item['$ref'];

                try {
                    $eventResponse = $client->get($eventUrl);
                    $eventData = json_decode($eventResponse->getBody(), true);

                    // Skip events outside the date range if dates are provided
                    if (!empty($eventData['date'])) {
                        $eventDate = Carbon::parse($eventData['date']);
                        if (($startDate && $eventDate->lt($startDate)) ||
                            ($endDate && $eventDate->gt($endDate))) {
                            $this->info("Skipping event outside date range: {$eventData['id']}");
                            continue;
                        }
                    }

                    // Initialize variables for event data
                    $venueName = null;
                    $venueCity = null;
                    $venueState = null;
                    $homeTeamId = null;
                    $awayTeamId = null;
                    $homeScore = null;
                    $awayScore = null;
                    $homeResult = false;
                    $awayResult = false;
                    $homeLinescores = [];
                    $awayLinescores = [];
                    $predictorJson = null;

                    if (!empty($eventData['competitions'])) {
                        foreach ($eventData['competitions'] as $competition) {
                            // Process venue information
                            if (!empty($competition['venue'])) {
                                $venue = $competition['venue'];
                                $venueName = $venue['fullName'] ?? null;
                                $venueCity = $venue['address']['city'] ?? null;
                                $venueState = $venue['address']['state'] ?? null;
                            }

                            // Process competitors
                            if (!empty($competition['competitors'])) {
                                foreach ($competition['competitors'] as $competitor) {
                                    $teamId = $competitor['id'] ?? null;
                                    $homeAway = $competitor['homeAway'] ?? null;
                                    $winner = !empty($competitor['winner']);

                                    // Get score
                                    $finalScore = null;
                                    if (!empty($competitor['score']['$ref'])) {
                                        $scoreUrl = $competitor['score']['$ref'];
                                        try {
                                            $scoreResp = $client->get($scoreUrl);
                                            $scoreData = json_decode($scoreResp->getBody(), true);
                                            $finalScore = (int)($scoreData['value'] ?? 0);
                                        } catch (Exception $e) {
                                            $this->error("Failed to fetch score from {$scoreUrl}: {$e->getMessage()}");
                                        }
                                    }

                                    // Get linescores
                                    $linescoresArray = [];
                                    if (!empty($competitor['linescores']['$ref'])) {
                                        $linescoreUrl = $competitor['linescores']['$ref'];
                                        try {
                                            $linescoreResp = $client->get($linescoreUrl);
                                            $linescoreData = json_decode($linescoreResp->getBody(), true);

                                            if (!empty($linescoreData['items'])) {
                                                foreach ($linescoreData['items'] as $periodData) {
                                                    $periodNum = $periodData['period'] ?? null;
                                                    $periodPts = (int)($periodData['value'] ?? 0);

                                                    $linescoresArray[] = [
                                                        'period' => $periodNum,
                                                        'points' => $periodPts
                                                    ];
                                                }
                                            }
                                        } catch (Exception $e) {
                                            $this->error("Failed to fetch linescores from {$linescoreUrl}: {$e->getMessage()}");
                                        }
                                    }

                                    // Assign data based on home/away
                                    if ($homeAway === 'home') {
                                        $homeTeamId = $teamId;
                                        $homeScore = $finalScore;
                                        $homeResult = $winner;
                                        $homeLinescores = $linescoresArray;
                                    } else {
                                        $awayTeamId = $teamId;
                                        $awayScore = $finalScore;
                                        $awayResult = $winner;
                                        $awayLinescores = $linescoresArray;
                                    }
                                }
                            }

                            // Get predictor data
                            if (!empty($competition['predictor']['$ref'])) {
                                $predictorUrl = $competition['predictor']['$ref'];
                                try {
                                    $predictorResp = $client->get($predictorUrl);
                                    $predictorJson = json_decode($predictorResp->getBody(), true);
                                } catch (Exception $e) {
                                    $this->error("Failed to fetch predictor from {$predictorUrl}: {$e->getMessage()}");
                                }
                            }
                        }
                    }

                    // Save or update the event
                    NbaEvent::updateOrCreate(
                        ['espn_id' => $eventData['id']],
                        [
                            'uid' => $eventData['uid'] ?? null,
                            'date' => !empty($eventData['date'])
                                ? date('Y-m-d H:i:s', strtotime($eventData['date']))
                                : null,
                            'name' => $eventData['name'] ?? null,
                            'short_name' => $eventData['shortName'] ?? null,
                            'venue_name' => $venueName,
                            'venue_city' => $venueCity,
                            'venue_state' => $venueState,
                            'home_team_id' => $homeTeamId,
                            'away_team_id' => $awayTeamId,
                            'home_score' => $homeScore,
                            'away_score' => $awayScore,
                            'home_result' => $homeResult,
                            'away_result' => $awayResult,
                            'home_linescores' => $homeLinescores,
                            'away_linescores' => $awayLinescores,
                            'predictor_json' => $predictorJson,
                        ]
                    );

                    $this->info("Saved event: {$eventData['id']} / {$eventData['name']}");
                    $this->info('Date: ' . ($eventData['date'] ?? 'N/A'));
                    $this->line('----------------------------');

                } catch (Exception $e) {
                    $this->error("Failed to fetch event details from {$eventUrl}: " . $e->getMessage());
                    continue;
                }
            }

            $currentPage++;
        } while ($currentPage <= $pageCount);

        $this->info('All events within specified date range fetched and saved successfully!');
        return Command::SUCCESS;
    }
}