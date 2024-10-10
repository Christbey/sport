<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EspnAthleteEventLogController extends Controller
{
    /**
     * Fetch the event log for a specific athlete and season.
     *
     * @param int $athleteId
     * @param int $season
     * @return JsonResponse
     */
    public function fetchAthleteEventLog($athleteId, $season)
    {
        // Base API URL
        $baseUrl = "https://sports.core.api.espn.com/v2/sports/football/leagues/nfl/seasons/{$season}/athletes/{$athleteId}/eventlog";

        // Make the request to ESPN API
        $response = Http::get($baseUrl);

        // Check if the response is successful
        if ($response->successful()) {
            $eventLogData = $response->json();

            // Return the event log data
            return response()->json($eventLogData);
        }

        // Handle any errors from the API
        return response()->json(['error' => 'Failed to fetch event log data'], 500);
    }

    /**
     * Fetch statistics for a specific event.
     *
     * @param int $athleteId
     * @param int $eventId
     * @param int $teamId
     * @return JsonResponse
     */
    public function fetchAthleteEventStatistics($athleteId, $eventId, $teamId)
    {
        // Base URL for statistics API call
        $statisticsUrl = "https://sports.core.api.espn.com/v2/sports/football/leagues/nfl/events/{$eventId}/competitions/{$eventId}/competitors/{$teamId}/roster/{$athleteId}/statistics/0";

        // Make the request to ESPN API
        $response = Http::get($statisticsUrl);

        // Check if the response is successful
        if ($response->successful()) {
            $statisticsData = $response->json();

            // Return the statistics data
            return response()->json($statisticsData);
        }

        // Handle any errors from the API
        return response()->json(['error' => 'Failed to fetch event statistics'], 500);
    }
}
