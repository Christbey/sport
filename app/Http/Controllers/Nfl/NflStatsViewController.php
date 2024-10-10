<?php

namespace App\Http\Controllers\Nfl;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class NflStatsViewController extends Controller
{
    public function showReceivers()
    {
        // Call the API to get receivers data
        $response = Http::get(url('/api/nfl-player-stats?type=receivers'));

        // Check if the API request was successful
        if ($response->successful()) {
            $data = $response->json(); // Parse the response data

            // Pass data to the view
            return view('nfl.receivers', ['receivers' => $data]);
        } else {
            return view('nfl.receivers', ['error' => 'Failed to fetch data']);
        }
    }

    public function showRushers()
    {
        // Call the API to get rushers data
        $response = Http::get(url('/api/nfl-player-stats?type=rushers'));

        // Check if the API request was successful
        if ($response->successful()) {
            $data = $response->json(); // Parse the response data

            // Pass data to the view
            return view('nfl.rushers', ['rushers' => $data]);
        } else {
            return view('nfl.rushers', ['error' => 'Failed to fetch data']);
        }
    }
}
