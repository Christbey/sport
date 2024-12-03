<?php

namespace App\Jobs\CollegeFootball;

use App\Models\CollegeFootball\{CollegeFootballGame, CollegeFootballTeam};
use App\Notifications\DiscordCommandCompletionNotification;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{DB, Notification};
use Log;

class StoreCollegeFootballGames implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const API_URL = 'https://api.collegefootballdata.com/games';
    private const CHUNK_SIZE = 50;

    public $timeout = 300; // 5 minutes
    public $tries = 3;     // Allow 3 attempts

    public function __construct(
        private ?int    $year = null,
        private ?int    $week = null,
        private ?string $apiKey = null
    )
    {
        $this->year = $year ?? config('college_football.season');
        $this->week = $week ?? $this->getCurrentWeek();
        $this->apiKey = $apiKey ?? config('services.college_football_data.key');
    }

    private function getCurrentWeek(): int
    {
        $today = Carbon::today();
        $weeks = config('college_football.weeks');

        foreach ($weeks as $weekNumber => $dates) {
            $weekStart = Carbon::parse($dates['start']);
            $weekEnd = Carbon::parse($dates['end']);

            if ($today->between($weekStart, $weekEnd)) {
                return $weekNumber;
            }
        }

        // If before season start, return week 1
        if ($today->isBefore(Carbon::parse(config('college_football.season_start')))) {
            return 1;
        }

        // If after season end, return last week
        if ($today->isAfter(Carbon::parse(config('college_football.season_end')))) {
            return count(config('college_football.weeks'));
        }

        // Default to configured week
        return config('college_football.week');
    }

    public function handle(): void
    {
        try {
            $games = $this->fetchGamesData();

            if (empty($games)) {
                $this->sendNotification("No games found for week {$this->week} of {$this->year}");
                return;
            }

            // Process in chunks
            collect($games)
                ->chunk(self::CHUNK_SIZE)
                ->each(function (Collection $chunk) {
                    $this->processGamesChunk($chunk->toArray());
                });

            $this->sendNotification('Successfully processed ' . count($games) . " games for week {$this->week} of {$this->year}");
        } catch (Exception $e) {
            $this->sendNotification("Error: {$e->getMessage()}", 'error');
            throw $e;
        }
    }

    private function fetchGamesData(): array
    {
        $client = new Client([
            'timeout' => 30,
            'connect_timeout' => 10
        ]);

        $response = $client->request('GET', self::API_URL, [
            'query' => [
                'year' => $this->year,
                'week' => $this->week,
                'seasonType' => $this->getSeasonType()
            ],
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Accept' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    private function getSeasonType(): string
    {
        // Championship weeks are typically 15 and 16
        return $this->week >= 17 ? 'postseason' : 'regular';
    }

    private function sendNotification(string $message = '', string $status = 'success'): void
    {
        Notification::route('discord', config('services.discord.channel_id'))
            ->notify(new DiscordCommandCompletionNotification($message, $status));
    }

    private function processGamesChunk(array $games): void
    {
        DB::transaction(function () use ($games) {
            $teamSchools = collect($games)->flatMap(function ($game) {
                return [$game['home_team'], $game['away_team']];
            })->unique()->values();

            $existingTeams = CollegeFootballTeam::whereIn('school', $teamSchools)
                ->get()
                ->keyBy('school');

            $teams = $this->processTeams($games, $existingTeams);
            $this->processGames($games, $teams);
        });
    }

    private function processTeams(array $games, Collection $existingTeams): Collection
    {
        $teamsToUpdate = collect();

        // Prepare bulk data for teams
        $teamsData = [];
        foreach ($games as $game) {
            foreach (['home', 'away'] as $type) {
                $school = $game["{$type}_team"];
                $conference = $game["{$type}_conference"] ?? null;

                if (!$existingTeams->has($school) && !isset($teamsData[$school])) {
                    $teamsData[$school] = [
                        'school' => $school,
                        'conference' => $conference
                    ];
                }
            }
        }

        // Bulk insert new teams
        if (!empty($teamsData)) {
            CollegeFootballTeam::upsert(
                array_values($teamsData),
                ['school'],
                ['conference']
            );
        }

        // Update existing teams if needed
        foreach ($games as $game) {
            foreach (['home', 'away'] as $type) {
                $school = $game["{$type}_team"];
                $conference = $game["{$type}_conference"] ?? null;

                if ($existingTeams->has($school)) {
                    $team = $existingTeams->get($school);
                    if ($team->conference !== $conference) {
                        $team->conference = $conference;
                        $team->save();
                    }
                    $teamsToUpdate->put($school, $team);
                } else {
                    // Fetch the newly created team
                    $team = CollegeFootballTeam::where('school', $school)->first();
                    $teamsToUpdate->put($school, $team);
                }
            }
        }

        return $teamsToUpdate;
    }


    private function processGames(array $games, Collection $teams): void
    {
        Log::info('Processing games:', ['count' => count($games)]);

        $gamesData = collect($games)->map(function ($game) use ($teams) {
            // Format the start_date
            $startDate = null;
            if (!empty($game['start_date'])) {
                $startDate = Carbon::parse($game['start_date'])->format('Y-m-d');
            }

            return [
                'id' => $game['id'],
                'season' => $game['season'] ?? null,
                'week' => $game['week'] ?? null,
                'season_type' => $game['season_type'] ?? null,
                'start_date' => $startDate,
                'start_time_tbd' => $game['start_time_tbd'] ?? false,
                'completed' => $game['completed'] ?? false,
                'neutral_site' => $game['neutral_site'] ?? false,
                'conference_game' => $game['conference_game'] ?? false,
                'attendance' => $game['attendance'] ?? null,
                'venue' => $game['venue'] ?? null,
                'home_id' => $teams->get($game['home_team'])->id,
                'home_team' => $game['home_team'] ?? null,
                'home_conference' => $game['home_conference'] ?? null,
                'home_division' => $game['home_division'] ?? null,
                'home_points' => $game['home_points'] ?? null,
                'home_line_scores' => json_encode($game['home_line_scores'] ?? []),
                'home_post_win_prob' => $game['home_post_win_prob'] ?? null,
                'home_pregame_elo' => $game['home_pregame_elo'] ?? null,
                'home_postgame_elo' => $game['home_postgame_elo'] ?? null,
                'away_id' => $teams->get($game['away_team'])->id,
                'away_team' => $game['away_team'] ?? null,
                'away_conference' => $game['away_conference'] ?? null,
                'away_division' => $game['away_division'] ?? null,
                'away_points' => $game['away_points'] ?? null,
                'away_line_scores' => json_encode($game['away_line_scores'] ?? []),
                'away_post_win_prob' => $game['away_post_win_prob'] ?? null,
                'away_pregame_elo' => $game['away_pregame_elo'] ?? null,
                'away_postgame_elo' => $game['away_postgame_elo'] ?? null,
            ];
        });

        // Add the actual database save operation
        foreach ($gamesData->chunk(100) as $chunk) {
            CollegeFootballGame::upsert(
                $chunk->toArray(),
                ['id'],
                array_keys($chunk->first())
            );
        }

        Log::info('Games processed and saved', ['count' => $gamesData->count()]);
    }
}