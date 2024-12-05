<?php

namespace App\Console\Commands\Nfl;

use App\Models\Nfl\NflEloPrediction;
use App\Notifications\NflEloUpdateNotification;
use App\Repositories\Nfl\Interfaces\NflEloPredictionRepositoryInterface;
use App\Repositories\Nfl\Interfaces\NflTeamScheduleRepositoryInterface;
use App\Services\EloRatingService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Log, Notification};

class UpdateNflEloRatings extends Command
{

    protected $signature = 'nfl:calculate-team-elo 
        {year? : The year to calculate ELO ratings for} 
        {--force : Force update even if already run today}';

    protected $description = 'Calculate Elo ratings, expected wins, and spreads for all NFL teams for a given season';


    public function __construct(
        protected EloRatingService                    $eloService,
        protected NflEloPredictionRepositoryInterface $eloPredictionRepo,
        protected NflTeamScheduleRepositoryInterface  $scheduleRepo
    )
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            if (!$this->shouldProcess()) {
                return self::SUCCESS;
            }

            $year = (int)($this->argument('year') ?? config('nfl.seasonYear'));
            $this->info("Processing ELO ratings for year: {$year}");

            $weeks = config('nfl.weeks');
            $today = Carbon::now();

            $initialPredictions = $this->getExistingPredictions();

            // Process all teams in a single batch
            $result = $this->processSeason($year);

            $this->processResults($result, $initialPredictions, $year);

            return self::SUCCESS;

        } catch (Exception $e) {
            $this->handleError($e, (string)($year ?? 'Unknown'));
            return self::FAILURE;
        }
    }

    protected function shouldProcess(): bool
    {
        if ($this->option('force')) {
            return true;
        }

        if ($this->eloPredictionRepo->hasUpdatedToday()) {
            $this->warn('ELO ratings have already been updated today. Use --force to override.');
            return false;
        }

        return true;
    }

    protected function getExistingPredictions(): Collection
    {
        return $this->eloPredictionRepo->getPredictions(null)
            ->keyBy('game_id');
    }

    protected function processSeason(int $year): array
    {
        $teams = $this->eloService->fetchTeams();

        if ($teams->isEmpty()) {
            throw new Exception('No teams found to process');
        }

        $progressBar = $this->output->createProgressBar($teams->count());
        $progressBar->start();

        try {
            $ratings = $this->eloService->processBatch($teams->toArray(), $year);
            $progressBar->finish();
            $this->newLine(2);

            return [
                'teams' => $teams,
                'ratings' => $ratings
            ];
        } catch (Exception $e) {
            Log::error('Error processing batch', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function processResults(array $result, Collection $initialPredictions, int $year): void
    {
        // Get updated predictions after processing
        $updatedPredictions = $this->getExistingPredictions();

        // Calculate significant changes
        $significantChanges = $this->calculateSignificantChanges(
            $initialPredictions,
            $updatedPredictions
        );

        // Log results
        $this->logResults($result['teams'], $result['ratings']);

        // Send notification
        $this->sendNotification($result['teams'], $year, $significantChanges);
    }

    protected function calculateSignificantChanges(
        Collection $initial,
        Collection $updated,
        float      $threshold = 0.5
    ): Collection
    {
        return $updated->map(function ($newPred) use ($initial, $threshold) {
            $oldPred = $initial->get($newPred->game_id);
            if (!$oldPred) return null;

            $spreadChange = abs($newPred->predicted_spread - $oldPred->predicted_spread);

            if ($spreadChange < $threshold) return null;

            return [
                'game_id' => $newPred->game_id,
                'week' => str_replace('Week ', '', $newPred->week),
                'team' => $newPred->team,
                'opponent' => $newPred->opponent,
                'old_spread' => round($oldPred->predicted_spread, 1),
                'new_spread' => round($newPred->predicted_spread, 1),
                'change' => round($spreadChange, 1)
            ];
        })
            ->filter()
            ->values();
    }

    protected function logResults(Collection $teams, array $ratings): void
    {
        foreach ($teams as $team) {
            $rating = $ratings[$team] ?? 'N/A';
            $this->info("Team: {$team} - Final ELO: {$rating}");
            Log::info('Processed ELO rating', [
                'team' => $team,
                'rating' => $rating
            ]);
        }
    }

    protected function sendNotification(Collection $teams, int $year, Collection $changes): void
    {
        Notification::route('discord', config('services.discord.channel_id'))
            ->notify(new NflEloUpdateNotification(
                teams: $teams,
                year: $year,
                spreadChanges: $changes,
                type: 'success'
            ));
    }

    protected function handleError(Exception $e, string $year): void
    {
        Log::error('Error in UpdateNflEloRatings command', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'year' => $year
        ]);

        $this->error("Failed to process ELO ratings: {$e->getMessage()}");

        Notification::route('discord', config('services.discord.channel_id'))
            ->notify(new NflEloUpdateNotification(
                teams: collect(),
                year: $year,
                type: 'error'
            ));
    }

    protected function hasRunToday(): bool
    {
        return NflEloPrediction::whereDate('created_at', today())->exists();
    }
}