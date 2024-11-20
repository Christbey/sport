<?php

namespace App\Jobs\Nfl;

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

        $this->validateApiKey($apiKey);

        $depthCharts = $this->fetchDepthCharts($apiHost, $apiKey);
        $this->storeDepthChartData($depthCharts);
    }

    /**
     * Validate the RapidAPI key.
     *
     * @param string $apiKey
     * @throws Exception
     */
    private function validateApiKey(string $apiKey): void
    {
        if (!$apiKey) {
            throw new Exception('RAPIDAPI_KEY is not set in the .env file.');
        }
    }

    /**
     * Fetch the NFL depth charts from the RapidAPI.
     *
     * @param string $apiHost
     * @param string $apiKey
     * @return array
     * @throws Exception
     */
    private function fetchDepthCharts(string $apiHost, string $apiKey): array
    {
        $response = Http::withHeaders([
            'x-rapidapi-host' => $apiHost,
            'x-rapidapi-key' => $apiKey,
        ])->get("https://{$apiHost}/getNFLDepthCharts");

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['body']) && is_array($data['body'])) {
                return $data['body'];
            } else {
                throw new Exception('Invalid data format received.');
            }
        } else {
            throw new Exception('Failed to fetch NFL depth charts. Status Code: ' . $response->status());
        }
    }

    /**
     * Store the depth chart data in the database.
     *
     * @param array $depthCharts
     */
    protected function storeDepthChartData(array $depthCharts): void
    {
        DB::transaction(function () use ($depthCharts) {
            foreach ($depthCharts as $teamData) {
                $this->storeTeamDepthChart($teamData);
            }
        });
    }

    /**
     * Store the depth chart data for a single team.
     *
     * @param array $teamData
     */
    private function storeTeamDepthChart(array $teamData): void
    {
        $teamId = $teamData['teamID'] ?? null;
        $teamAbv = $teamData['teamAbv'] ?? null;

        if ($teamId && $teamAbv && isset($teamData['depthChart']) && is_array($teamData['depthChart'])) {
            foreach ($teamData['depthChart'] as $position => $players) {
                $this->storePositionPlayers($teamId, $teamAbv, $position, $players);
            }
        }
    }

    /**
     * Store the depth chart data for a single position.
     *
     * @param int $teamId
     * @param string $teamName
     * @param string $position
     * @param array $players
     */
    private function storePositionPlayers(int $teamId, string $teamName, string $position, array $players): void
    {
        foreach ($players as $playerData) {
            $playerId = $playerData['playerID'] ?? null;
            $playerName = $playerData['longName'] ?? 'Unknown';
            $depthOrder = $this->extractDepthOrder($playerData['depthPosition']);

            if ($playerId) {
                DepthChart::updateOrCreate(
                    [
                        'team_id' => $teamId,
                        'position' => $position,
                        'player_id' => $playerId,
                    ],
                    [
                        'team_name' => $teamName,
                        'player_name' => $playerName,
                        'depth_order' => $depthOrder,
                    ]
                );
            }
        }
    }

    /**
     * Extract the numeric depth order from the depthPosition string (e.g., 'RB1' -> 1).
     *
     * @param string|null $depthPositionStr
     * @return int|null
     */
    private function extractDepthOrder(?string $depthPositionStr): ?int
    {
        if ($depthPositionStr) {
            $matches = [];
            if (preg_match('/\d+/', $depthPositionStr, $matches)) {
                return (int)$matches[0];
            }
        }
        return null;
    }
}