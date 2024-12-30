<?php

namespace App\Console\Commands;

use App\Models\NbaEvent;
use App\Models\NbaOdds;
use App\Models\NbaPlayer;
use App\Models\NbaPropBet;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class ESPNOddsImport extends Command
{
    protected $signature = 'espn:odds-import 
        {event_id? : Specific event ID to import (optional)}
        {--start= : Start date for filtering events (Y-m-d)}
        {--end= : End date for filtering events (Y-m-d)}';

    protected $description = 'Fetches NBA odds data for events, stores in nba_odds and nba_prop_bets tables.';

    public function handle()
    {
        // Date range validation
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

        // Specific event ID from argument, or fetch all events
        $specificEventId = $this->argument('event_id');

        // Query events with optional date filtering
        $eventsQuery = NbaEvent::query();

        if ($specificEventId) {
            $eventsQuery->where('espn_id', $specificEventId);
        }

        if ($startDate) {
            $eventsQuery->where('date', '>=', $startDate);
        }

        if ($endDate) {
            $eventsQuery->where('date', '<=', $endDate);
        }

        $events = $eventsQuery->get();
        $client = new Client();

        // Iterate through events
        foreach ($events as $nbaEvent) {
            $eventId = $nbaEvent->espn_id;
            $eventDate = $nbaEvent->date;
            $opponentId = $nbaEvent->away_team_id;

            try {
                // Fetch odds URL
                $url = "http://sports.core.api.espn.com/v2/sports/basketball/leagues/nba/events/{$eventId}/competitions/{$eventId}/odds?lang=en&region=us";

                $resp = $client->get($url);
                $data = json_decode($resp->getBody(), true);

                $this->info("Fetched Odds for event_id={$eventId}");
                $items = $data['items'] ?? [];
                if (empty($items)) {
                    $this->warn("No odds items found for this event: {$eventId}.");
                    continue;
                }

                // Loop over each odds item
                foreach ($items as $item) {
                    $oddsRef = $item['$ref'] ?? '[No ref]';
                    $providerName = $item['provider']['name'] ?? null;
                    $details = $item['details'] ?? null;
                    $overUnder = $item['overUnder'] ?? null;
                    $spread = $item['spread'] ?? null;

                    // awayTeamOdds
                    $awayML = $item['awayTeamOdds']['moneyLine'] ?? null;
                    $awaySpread = $item['awayTeamOdds']['spreadOdds'] ?? null;
                    // homeTeamOdds
                    $homeML = $item['homeTeamOdds']['moneyLine'] ?? null;
                    $homeSpread = $item['homeTeamOdds']['spreadOdds'] ?? null;

                    // Store in nba_odds with our newly determined $eventDate + $opponentId
                    NbaOdds::updateOrCreate(
                        ['odds_ref' => $oddsRef],
                        [
                            'event_id' => $eventId,
                            'opponent_id' => $opponentId,
                            'event_date' => $eventDate,
                            'provider_name' => $providerName,
                            'details' => $details,
                            'over_under' => $overUnder,
                            'spread' => $spread,
                            'away_money_line' => (string)$awayML,
                            'away_spread_odds' => (string)$awaySpread,
                            'home_money_line' => (string)$homeML,
                            'home_spread_odds' => (string)$homeSpread,
                        ]
                    );

                    $this->info("Stored Odds (odds_ref={$oddsRef}, provider={$providerName})");

                    // Check for propBets link
                    $propBetsLink = $item['propBets']['$ref'] ?? null;
                    if ($propBetsLink) {
                        try {
                            $propResp = $client->get($propBetsLink);
                            $propData = json_decode($propResp->getBody(), true);

                            $propItems = $propData['items'] ?? [];
                            foreach ($propItems as $prop) {
                                $propType = $prop['type']['name'] ?? null;  // e.g. "Total Points"
                                $athleteRef = $prop['athlete']['$ref'] ?? null;
                                $athleteId = null;
                                if ($athleteRef && preg_match('/athletes\/(\d+)\?/', $athleteRef, $m)) {
                                    $athleteId = $m[1];
                                }

                                // (Optional) If you have a local NbaPlayer table to lookup name:
                                $athleteName = null;
                                if ($athleteId) {
                                    $playerRow = NbaPlayer::where('espn_id', $athleteId)->first();
                                    if ($playerRow) {
                                        $athleteName = $playerRow->display_name;
                                    }
                                }

                                $total = $prop['odds']['total']['value'] ?? null;
                                $currentOver = $prop['current']['over']['alternateDisplayValue']
                                    ?? $prop['current']['over']['value']
                                    ?? null;
                                $currentTarget = $prop['current']['target']['value'] ?? null;
                                $propRef = $prop['$ref'] ?? null;

                                // Save in nba_prop_bets with the same $eventDate and $opponentId
                                NbaPropBet::updateOrCreate([
                                    'event_id' => $eventId,
                                    'opponent_id' => $opponentId,
                                    'event_date' => $eventDate,
                                    'athlete_id' => $athleteId,
                                    'athlete_name' => $athleteName,
                                    'prop_type' => $propType,
                                    'total' => $total,
                                    'current_over' => $currentOver,
                                    'current_target' => $currentTarget,
                                    'prop_ref' => $propRef,
                                ]);
                            }
                        } catch (Exception $pe) {
                            $this->error("Failed to fetch prop bets from {$propBetsLink}: " . $pe->getMessage());
                        }
                    }
                }

                $this->info("Done fetching & storing odds and prop bets for event_id={$eventId}!");
            } catch (Exception $e) {
                $this->error("Failed to fetch odds for event={$eventId}: " . $e->getMessage());
                continue;
            }
        }

        $this->info("Completed importing odds and prop bets!");
        return Command::SUCCESS;
    }
}
