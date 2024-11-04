<?php

namespace App\Jobs;

use App\Models\Nfl\DepthChart;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class FetchNflDepthChartJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle()
    {
        $apiKey = config('services.rapidapi.key');
        $apiHost = config('services.rapidapi.host', 'tank01-nfl-live-in-game-real-time-statistics-nfl.p.rapidapi.com');

        if (!$apiKey) {
            throw new Exception('RAPIDAPI_KEY is not set in the .env file.');
        }

        $response = Http::withHeaders([
            'x-rapidapi-host' => $apiHost,
            'x-rapidapi-key' => $apiKey,
        ])->get("https://{$apiHost}/getNFLDepthCharts");

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['body']) && is_array($data['body'])) {
                $this->storeDepthChartData($data['body']);
            } else {
                throw new Exception('Invalid data format received.');
            }
        } else {
            throw new Exception('Failed to fetch NFL depth charts. Status Code: ' . $response->status());
        }
    }

    /**
     * Store the depth chart data in the database.
     */
    protected function storeDepthChartData(array $depthCharts)
    {
        DB::transaction(function () use ($depthCharts) {
            foreach ($depthCharts as $teamData) {
                $teamId = $teamData['teamID'] ?? null;
                $teamAbv = $teamData['teamAbv'] ?? null;

                if (!$teamId || !$teamAbv) {
                    continue;
                }

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
                }
            }
        });
    }
}
