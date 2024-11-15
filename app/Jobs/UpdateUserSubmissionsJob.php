<?php

namespace App\Jobs;

use App\Events\GameResultsProcessed;
use App\Helpers\NflCommandHelper;
use App\Models\Nfl\NflTeamSchedule;
use App\Models\UserSubmission;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateUserSubmissionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $eventId;

    public function __construct($eventId = null)
    {
        $this->eventId = $eventId;
    }

    public function handle()
    {
        try {
            $currentWeek = NflCommandHelper::getCurrentWeek();
            $previousWeek = $currentWeek ? $currentWeek - 1 : null;

            if (is_null($previousWeek) || $previousWeek < 1) {
                NflCommandHelper::sendNotification('No valid previous NFL week found.', 'info');
                return;
            }

            $query = NflTeamSchedule::with(['homeTeam', 'awayTeam'])
                ->whereIn('game_status', ['Completed', 'Final'])
                ->where('game_week', 'Week ' . $previousWeek);

            if ($this->eventId) {
                $query->where('espn_event_id', $this->eventId);
            }

            $updatedGames = collect();

            $query->each(function ($nflTeamSchedule) use ($updatedGames) {
                $winningTeamId = $this->determineWinningTeam($nflTeamSchedule);
                $this->updateSubmissions($nflTeamSchedule, $winningTeamId);
                $updatedGames->push($nflTeamSchedule);
            });

            if ($updatedGames->isNotEmpty()) {
                $this->dispatchGameResultsProcessed($updatedGames);
                NflCommandHelper::sendNotification("Processed games for Week {$previousWeek} successfully.");
            }

        } catch (Exception $e) {
            NflCommandHelper::sendNotification($e->getMessage(), 'error');
        }
    }

    protected function determineWinningTeam($nflTeamSchedule)
    {
        return $nflTeamSchedule->home_pts > $nflTeamSchedule->away_pts
            ? $nflTeamSchedule->home_team_id
            : $nflTeamSchedule->away_team_id;
    }

    protected function updateSubmissions($nflTeamSchedule, $winningTeamId)
    {
        UserSubmission::where('espn_event_id', $nflTeamSchedule->espn_event_id)
            ->with(['team'])
            ->each(function ($submission) use ($winningTeamId) {
                $submission->is_correct = $submission->team_id == $winningTeamId;
                $submission->save();
            });
    }

    protected function dispatchGameResultsProcessed($updatedGames)
    {
        // First ensure we have all relationships loaded when games are queried
        $groupedGames = $updatedGames->groupBy('game_week');

        $groupedGames->each(function ($games, $gameWeek) {
            $gamesArray = $games->map(function ($game) {
                // Assuming $game is already an Eloquent model with relationships loaded
                return [
                    'id' => $game->id,
                    'espn_event_id' => $game->espn_event_id,
                    'game_week' => $game->game_week,
                    'home_team_id' => $game->home_team_id,
                    'away_team_id' => $game->away_team_id,
                    'home_pts' => $game->home_pts,
                    'away_pts' => $game->away_pts,
                    'game_status' => $game->game_status,
                    'home_team' => $game->homeTeam ? [
                        'id' => $game->homeTeam->id,
                        'team_name' => $game->homeTeam->team_name,
                    ] : null,
                    'away_team' => $game->awayTeam ? [
                        'id' => $game->awayTeam->id,
                        'team_name' => $game->awayTeam->team_name,
                    ] : null,
                ];
            })->toArray();

            GameResultsProcessed::dispatch(
                $gamesArray,
                $gameWeek
            );
        });
    }
}