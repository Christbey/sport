<?php

namespace App\Console\Commands;

use App\Models\Nfl\DepthChart;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class FetchNflDepthChart extends Command
{
    protected $signature = 'nfl:fetch-depth-chart';
    protected $description = 'Fetch NFL depth charts and store them in the database';

    public function handle()
    {
        $apiKey = config('services.rapidapi.key');
        $apiHost = config('services.rapidapi.host');

        if (!$apiKey) {
            $this->error('RAPIDAPI_KEY is not set in the .env file.');
            return 1;
        }

        try {
            $response = Http::withHeaders([
                'x-rapidapi-host' => $apiHost,
                'x-rapidapi-key' => $apiKey,
            ])->get("https://{$apiHost}/getNFLDepthCharts");

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['body']) && is_array($data['body'])) {
                    $this->storeDepthChartData($data['body']);
                    $this->info('NFL Depth Charts fetched and stored successfully.');
                } else {
                    $this->error('Invalid data format received.');
                }
            } else {
                $this->error('Failed to fetch NFL depth charts.');
                $this->error('Status Code: ' . $response->status());
                $this->error('Response Body: ' . $response->body());
            }
        } catch (Exception $e) {
            $this->error('An error occurred while fetching NFL depth charts: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function storeDepthChartData(array $depthCharts)
    {
        // The $depthCharts is an array of teams with numeric keys
        DB::transaction(function () use ($depthCharts) {
            foreach ($depthCharts as $teamData) {
                // Extract team information
                $teamId = $teamData['teamID'] ?? null;
                $teamAbv = $teamData['teamAbv'] ?? null;

                if (!$teamId || !$teamAbv) {
                    $this->warn('Team ID or abbreviation not found. Skipping team.');
                    continue;
                }

                // Positions and players
                if (isset($teamData['depthChart']) && is_array($teamData['depthChart'])) {
                    $positions = $teamData['depthChart'];

                    foreach ($positions as $position => $players) {
                        if (!is_array($players)) {
                            continue;
                        }
                        foreach ($players as $playerData) {
                            $depthPositionStr = $playerData['depthPosition'] ?? null;
                            $playerId = $playerData['playerID'] ?? null;
                            $playerName = $playerData['longName'] ?? 'Unknown';

                            // Extract numeric depth order from depthPosition (e.g., 'RB1' -> 1)
                            $depthOrder = null;
                            if ($depthPositionStr) {
                                $depthOrder = intval(preg_replace('/\D/', '', $depthPositionStr));
                            }

                            if ($playerId && $position) {
                                DepthChart::updateOrCreate(
                                    [
                                        'team_id' => $teamId,
                                        'position' => $position,
                                        'player_id' => $playerId,
                                    ],
                                    [
                                        'team_name' => $teamAbv,
                                        'player_name' => $playerName,
                                        'depth_order' => $depthOrder,
                                    ]
                                );
                            }
                        }
                    }
                } else {
                    $this->warn("Positions not found for team {$teamAbv}. Skipping team.");
                }
            }
        });
    }
}
