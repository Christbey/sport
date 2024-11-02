<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Log;

class ForgeApiController extends Controller
{
    protected $baseUri;
    protected $token;

    public function __construct()
    {
        $this->baseUri = config('services.forge.base_uri');
        $this->token = config('services.forge.token');
    }

    /**
     * List all servers.
     */
    public function listServers()
    {
        $response = Http::withToken($this->token)->get("{$this->baseUri}servers");

        if ($response->successful()) {
            // Access the 'servers' key in the API response
            $servers = $response->json('servers', []);

            return view('forge.servers.index', compact('servers'));
        } else {
            $status = $response->status();
            Log::error('Error fetching servers from Forge API:', ['status' => $status, 'response' => $response->body()]);
            return back()->withErrors(['error' => 'Failed to fetch servers from the API.']);
        }
    }

    /**
     * List all sites on a given server.
     */
    public function listSites($serverId)
    {
        $response = Http::withToken($this->token)->get("{$this->baseUri}servers/{$serverId}/sites");

        if ($response->successful()) {
            // Retrieve sites from the API response, with a default empty array if 'sites' key is missing
            $sites = $response->json('sites', []);

            return view('forge.sites.index', compact('sites', 'serverId'));
        } else {
            $status = $response->status();
            Log::error('Error fetching sites from Forge API:', ['status' => $status, 'response' => $response->body()]);
            return back()->withErrors(['error' => 'Failed to fetch sites from the API.']);
        }
    }


    /**
     * Run a command on a specific site.
     * @throws ConnectionException
     */
    public function runSiteCommand($serverId, $siteId, Request $request)
    {
        $request->validate([
            'command' => 'required|string',
        ]);

        $commandText = $request->input('command'); // Retrieve the command text from the request

        // Send the command request to Forge
        $response = Http::withToken($this->token)->post("{$this->baseUri}servers/{$serverId}/sites/{$siteId}/commands", [
            'command' => $commandText,
        ]);

        // Extract the command ID from the response
        $commandData = $response->json();
        $commandId = $commandData['command']['id'] ?? null; // Get the command ID

        // Check if command ID is present, otherwise return an error message
        if (!$commandId) {
            return back()->withErrors('Failed to retrieve the command ID.');
        }

        return view('forge.commands.run', [
            'commandText' => $commandText,
            'commandId' => $commandId,
            'serverId' => $serverId,
            'siteId' => $siteId,
            'commandStatus' => $commandData['command'], // Pass the initial command status
        ]);
    }


    /**
     * Deploy a specific site.
     */
    public function deploySite($serverId, $siteId)
    {
        $response = Http::withToken($this->token)->post("{$this->baseUri}servers/{$serverId}/sites/{$siteId}/deployment/deploy");

        return response()->json($response->json(), $response->status());
    }

    public function listCommands($serverId, $siteId)
    {
        $response = Http::withToken($this->token)->get("{$this->baseUri}servers/{$serverId}/sites/{$siteId}/commands");
        $commands = $response->json()['commands'] ?? [];

        return view('forge.commands.run', compact('commands'));
    }


    public function listCommandHistory($serverId, $siteId)
    {
        $response = Http::withToken($this->token)->get("{$this->baseUri}servers/{$serverId}/sites/{$siteId}/commands");

        return view('forge.commands.run', compact('response'));
    }

    public function getCommandOutput($serverId, $eventId)
    {
        $response = Http::withToken($this->token)->get("{$this->baseUri}servers/{$serverId}/events/{$eventId}");

        return response()->json($response->json(), $response->status());
    }

    public function getCommandStatus($serverId, $siteId, $commandId)
    {
        $response = Http::withToken($this->token)->get("{$this->baseUri}servers/{$serverId}/sites/{$siteId}/commands/{$commandId}");

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Command status not found'], 404);
    }


}
