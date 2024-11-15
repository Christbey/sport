<?php

namespace App\Console\Commands\Nfl;

use App\Events\Nfl\CalculateTeamEloEvent;
use App\Models\Nfl\NflEloPrediction;
use App\Notifications\NflEloUpdateNotification;
use App\Services\EloRatingService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Log, Notification};

class UpdateNflEloRatings extends Command
{
    protected $signature = 'nfl:calculate-team-elo {year?} {--force : Force update even if already run today}';
    protected $description = 'Calculate Elo rating, expected wins, and spreads for all NFL teams for a given season';

    protected EloRatingService $eloService;

    public function __construct(EloRatingService $eloService)
    {
        parent::__construct();
        $this->eloService = $eloService;
    }

    public function handle()
    {
        try {
            $year = $this->argument('year') ?? config('nfl.seasonYear');

            // Check if already run today unless force option is used
            if (!$this->option('force') && $this->hasRunToday()) {
                $this->warn('ELO ratings have already been updated today. Use --force to override.');
                return;
            }

            $weeks = config('nfl.weeks');
            $teams = collect($this->eloService->fetchTeams());
            $today = Carbon::now();

            // Get initial predictions for comparison
            $initialPredictions = $this->getExistingPredictions();

            // Process teams
            $processedTeams = $this->processTeams($teams, $year, $weeks, $today);

            // Get updated predictions
            $updatedPredictions = $this->getExistingPredictions();

            // Calculate significant changes
            $significantChanges = $this->calculateSignificantChanges(
                $initialPredictions,
                $updatedPredictions
            );

            $this->info('Elo calculation events for all teams have been dispatched.');
            Log::info('Elo calculation events for all teams have been dispatched.');

            // Send success notification with changes
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new NflEloUpdateNotification(
                    teams: $processedTeams,
                    year: $year,
                    spreadChanges: $significantChanges));

        } catch (Exception $e) {
            $this->handleError($e, $year ?? 'Unknown');
        }
    }

    protected function hasRunToday(): bool
    {
        return NflEloPrediction::whereDate('created_at', today())->exists();
    }

    protected function getExistingPredictions(): Collection
    {
        return NflEloPrediction::select([
            'team',
            'opponent',
            'week',
            'team_elo',
            'opponent_elo',
            'expected_outcome',
            'predicted_spread',
            'game_id'
        ])->get()->keyBy('game_id');
    }

    protected function processTeams(Collection $teams, $year, $weeks, $today): Collection
    {
        $processedTeams = collect();

        foreach ($teams as $team) {
            if (!is_string($team)) {
                if ($team !== '') {  // Only log if it's not an empty string
                    Log::warning("Invalid team name encountered: {$team}");
                    $this->error("Invalid team name encountered: {$team}");
                }
                continue;
            }

            event(new CalculateTeamEloEvent($team, $year, $weeks, $today));
            $processedTeams->push($team);

            $this->info("CalculateTeamEloEvent dispatched for team: {$team}");
            Log::info("CalculateTeamEloEvent dispatched for team: {$team}");
        }

        return $processedTeams;
    }

    protected function calculateSignificantChanges(
        Collection $initial,
        Collection $updated,
        float      $threshold = 0.5  // Only show changes of 0.5 or more points
    ): Collection
    {
        return $updated->map(function ($newPred) use ($initial, $threshold) {
            $oldPred = $initial->get($newPred->game_id);
            if (!$oldPred) return null;

            $spreadChange = abs($newPred->predicted_spread - $oldPred->predicted_spread);

            // Only include significant spread changes
            if ($spreadChange < $threshold) return null;

            return [
                'game_id' => $newPred->game_id,
                'week' => str_replace('Week ', '', $newPred->week),
                'old_spread' => $oldPred->predicted_spread,
                'new_spread' => $newPred->predicted_spread
            ];
        })
            ->filter()
            ->values();
    }

    protected function handleError(Exception $e, string $year): void
    {
        Notification::route('discord', config('services.discord.channel_id'))
            ->notify(new NflEloUpdateNotification(
                teams: collect(),
                year: $year,
                type: 'error'
            ));

        Log::error('Error in UpdateNflEloRatings command: ' . $e->getMessage());
        $this->error('An error occurred: ' . $e->getMessage());
    }
}
