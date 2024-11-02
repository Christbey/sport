<?php

namespace App\Jobs\Nfl;

use App\Services\EloRatingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CalculateTeamElo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $team;
    protected $year;
    protected $weeks;
    protected $today; // Pass today directly as a property

    /**
     * Create a new job instance.
     *
     * @param string $team
     * @param int $year
     * @param array $weeks
     */
    public function __construct($team, $year, $weeks)
    {
        $this->team = $team;
        $this->year = $year;
        $this->weeks = $weeks;
        $this->today = Carbon::now(); // Ensure today is Carbon instance
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(EloRatingService $eloService)
    {
        Log::info("Calculating Elo, expected wins, and spreads for team: {$this->team}");

        // Process team predictions using the Elo service
        $finalElo = $eloService->processTeamPredictions($this->team, $this->year, $this->weeks, $this->today);

        Log::info("Team: {$this->team} | Final Elo for {$this->year} season: {$finalElo}");
    }
}
