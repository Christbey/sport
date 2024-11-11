<?php

namespace App\Jobs\Nfl;

use App\Events\BoxScoreFetched;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchNflBoxScoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $gameID;

    /**
     * Create a new job instance.
     *
     * @param string $gameID
     */
    public function __construct($gameID)
    {
        $this->gameID = $gameID;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            Log::info("Fetching box score for game: {$this->gameID}");

            $response = Http::get(route('nfl.boxscore'), ['gameID' => $this->gameID]);

            if ($response->successful()) {
                $data = $response->json();

                // Dispatch the BoxScoreFetched event
                event(new BoxScoreFetched($this->gameID, $data));

            } else {
                Log::error("Failed to fetch box score for game {$this->gameID}");
                // Optionally, dispatch a failure event here
            }

        } catch (Exception $e) {
            // Log the error
            Log::error("Error fetching box score for game {$this->gameID}: " . $e->getMessage());

            // Optionally, dispatch a failure event here
        }
    }
}
