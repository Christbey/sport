<?php

namespace App\Jobs\CollegeFootball;

use App\Models\CollegeFootball\CollegeFootballGame;
use App\Models\CollegeFootball\CollegeFootballTeam;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StoreCollegeFootballGames implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $year;
    protected $apiUrl;
    protected $apiKey;

    /**
     * Create a new job instance.
     *
     * @param int $year
     */
    public function __construct(int $year)
    {
        $this->year = $year;
        $this->apiUrl = 'https://api.collegefootballdata.com/games';
        $this->apiKey = env('COLLEGE_FOOTBALL_DATA_API_KEY');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $client = new Client();
            $response = $client->request('GET', $this->apiUrl, [
                'query' => [
                    'year' => $this->year,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $games = json_decode($response->getBody()->getContents(), true);

            foreach ($games as $game) {
                // Ensure the home and away teams are stored first
                $homeTeam = CollegeFootballTeam::updateOrCreate(
                    ['school' => $game['home_team']],
                    [
                        'conference' => $game['home_conference'] ?? null,
                    ]
                );

                $awayTeam = CollegeFootballTeam::updateOrCreate(
                    ['school' => $game['away_team']],
                    [
                        'conference' => $game['away_conference'] ?? null,
                    ]
                );

                // Now store the game data with valid home_id and away_id
                CollegeFootballGame::updateOrCreate(
                    ['id' => $game['id']],
                    [
                        'season' => $game['season'] ?? null,
                        'week' => $game['week'] ?? null,
                        'season_type' => $game['season_type'] ?? null,
                        'start_date' => $game['start_date'] ?? null,
                        'start_time_tbd' => $game['start_time_tbd'] ?? false,
                        'completed' => $game['completed'] ?? false,
                        'neutral_site' => $game['neutral_site'] ?? false,
                        'conference_game' => $game['conference_game'] ?? false,
                        'attendance' => $game['attendance'] ?? null,
                        'venue_id' => $venue->id ?? null,
                        'venue' => $game['venue'] ?? null,
                        'home_id' => $homeTeam->id,
                        'home_team' => $game['home_team'] ?? null,
                        'home_conference' => $game['home_conference'] ?? null,
                        'home_division' => $game['home_division'] ?? null,
                        'home_points' => $game['home_points'] ?? null,
                        'home_line_scores' => json_encode($game['home_line_scores'] ?? []),
                        'home_post_win_prob' => $game['home_post_win_prob'] ?? null,
                        'home_pregame_elo' => $game['home_pregame_elo'] ?? null,
                        'home_postgame_elo' => $game['home_postgame_elo'] ?? null,
                        'away_id' => $awayTeam->id,
                        'away_team' => $game['away_team'] ?? null,
                        'away_conference' => $game['away_conference'] ?? null,
                        'away_division' => $game['away_division'] ?? null,
                        'away_points' => $game['away_points'] ?? null,
                        'away_line_scores' => json_encode($game['away_line_scores'] ?? []),
                        'away_post_win_prob' => $game['away_post_win_prob'] ?? null,
                        'away_pregame_elo' => $game['away_pregame_elo'] ?? null,
                        'away_postgame_elo' => $game['away_postgame_elo'] ?? null,
                        'excitement_index' => $game['excitement_index'] ?? null,
                        'highlights' => $game['highlights'] ?? null,
                        'notes' => $game['notes'] ?? null,
                        'provider' => $game['provider'] ?? null,
                        'spread' => $game['spread'] ?? null,
                        'formatted_spread' => $game['formatted_spread'] ?? null,
                        'spread_open' => $game['spread_open'] ?? null,
                        'over_under' => $game['over_under'] ?? null,
                        'over_under_open' => $game['over_under_open'] ?? null,
                        'home_moneyline' => $game['home_moneyline'] ?? null,
                        'away_moneyline' => $game['away_moneyline'] ?? null,
                        'media_type' => $game['media_type'] ?? null,
                        'outlet' => $game['outlet'] ?? null,
                        'start_time' => $game['start_time'] ?? null,
                        'temperature' => $game['temperature'] ?? null,
                        'dew_point' => $game['dew_point'] ?? null,
                        'humidity' => $game['humidity'] ?? null,
                        'precipitation' => $game['precipitation'] ?? null,
                        'snowfall' => $game['snowfall'] ?? null,
                        'wind_direction' => $game['wind_direction'] ?? null,
                        'wind_speed' => $game['wind_speed'] ?? null,
                        'pressure' => $game['pressure'] ?? null,
                        'weather_condition_code' => $game['weather_condition_code'] ?? null,
                        'weather_condition' => $game['weather_condition'] ?? null,
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to store college football games: ' . $e->getMessage());
        }
    }
}
