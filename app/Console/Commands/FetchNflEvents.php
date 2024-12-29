<?php

namespace App\Console\Commands;

use App\Models\Nfl\OddsApiNfl;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchNflEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'odds-api:fetch-nfl-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch NFL events from The Odds API and store in the database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $apiKey = env('ODDS_API_KEY'); // Ensure you add your API key to the .env file
        $url = 'https://api.the-odds-api.com/v4/sports/americanfootball_nfl/events';

        $this->info('Fetching NFL events from The Odds API...');

        try {
            $response = Http::get($url, [
                'apiKey' => $apiKey,
            ]);

            if ($response->failed()) {
                $this->error('Failed to fetch data: ' . $response->body());
                return Command::FAILURE;
            }

            $events = $response->json();

            foreach ($events as $event) {
                OddsApiNfl::updateOrCreate(
                    ['event_id' => $event['id']],
                    [
                        'sport' => $event['sport_title'],
                        'datetime' => $event['commence_time'],
                        'home_team' => $event['home_team'],
                        'away_team' => $event['away_team'],
                        'source' => json_encode($event), // Optional: Store raw API data
                    ]
                );
            }

            $this->info('NFL events fetched and stored successfully.');
            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
