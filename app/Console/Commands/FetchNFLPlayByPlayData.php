<?php
//
//namespace App\Console\Commands;
//
//use App\Models\Nfl\NflDrive;
//use App\Models\Nfl\NflPlay;
//use App\Models\Nfl\NflPlayPlayer;
//use App\Models\Nfl\NflTeamSchedule;
//use App\Services\NflEpaCalculator;
//use Carbon\Carbon;
//use Exception;
//use GuzzleHttp\Client;
//use Illuminate\Console\Command;
//use Illuminate\Support\Facades\DB;
//use Illuminate\Support\Facades\Log;
//
//class FetchNFLPlayByPlayData extends Command
//{
//    protected $signature = 'nfl:fetch-play-by-play
//                          {type=current : Options are current, week, game, or all}
//                          {value? : Week number or game ID}';
//    protected $description = 'Fetch NFL play-by-play data with EPA (current week, specific week, specific game, or all games)';
//    private NFLEpaCalculator $epaCalculator;
//
//    public function __construct(NFLEpaCalculator $epaCalculator)
//    {
//        parent::__construct();
//        $this->epaCalculator = $epaCalculator;
//    }
//
//    public function handle()
//    {
//        $type = $this->argument('type');
//        $value = $this->argument('value');
//
//        $games = collect(match ($type) {
//            'week' => $this->getGamesForWeek($value),
//            'game' => $this->getSpecificGame($value),
//            'all' => $this->getAllGames(),
//            default => $this->getCurrentWeekGames(),
//        });
//
//        if ($games->isEmpty()) {
//            $this->error('No games found.');
//            return 1;
//        }
//
//        $bar = $this->output->createProgressBar($games->count());
//        $bar->start();
//
//        foreach ($games as $game) {
//            $this->processGame($game->espn_event_id);
//            $bar->advance();
//        }
//
//        $bar->finish();
//        $this->newLine();
//        $this->info('All games processed successfully.');
//        return 0;
//    }
//
//    private function getGamesForWeek($week)
//    {
//        return NflTeamSchedule::where('game_week', $week)
//            ->where('season', 2024)
//            ->get();
//    }
//
//    private function getSpecificGame($gameId)
//    {
//        return NflTeamSchedule::where('espn_event_id', $gameId)->get();
//    }
//
//    private function getAllGames()
//    {
//        return NflTeamSchedule::where('season', 2024)->get();
//    }
//
//    private function getCurrentWeekGames()
//    {
//        $currentWeek = $this->getCurrentWeek();
//        return $this->getGamesForWeek($currentWeek);
//    }
//
//    private function getCurrentWeek()
//    {
//        $season_start = Carbon::create(2024, 9, 5); // NFL 2024 season start
//        $current_date = Carbon::now();
//
//        $diff = $current_date->diffInWeeks($season_start);
//        return min(max(1, $diff + 1), 18); // Ensures week is between 1 and 18
//    }
//
//    private function processGame($gameId)
//    {
//        // Get team information from the schedule
//        $game = NflTeamSchedule::where('espn_event_id', $gameId)->first();
//        if (!$game) {
//            $this->error("Game not found: {$gameId}");
//            return 0;
//        }
//
//        $homeTeamId = $game->home_team_id;
//        $awayTeamId = $game->away_team_id;
//
//        try {
//            $response = (new Client())->get("https://cdn.espn.com/core/nfl/playbyplay?xhr=1&gameId={$gameId}");
//            $data = json_decode($response->getBody()->getContents(), true);
//
//            if (!isset($data['gamepackageJSON']['drives']['previous'])) {
//                $this->error("No drive data found for game: {$gameId}");
//                return 0;
//            }
//
//            DB::transaction(function () use ($data, $gameId, $homeTeamId, $awayTeamId) {
//                foreach ($data['gamepackageJSON']['drives']['previous'] as $drive) {
//                    $possessionTeamId = $this->determinePossessionTeam($drive, $homeTeamId, $awayTeamId);
//
//                    try {
//                        $driveModel = NflDrive::updateOrCreate(
//                            ['drive_number' => $drive['id']],
//                            [
//                                'game_id' => $gameId,
//                                'drive_number' => $drive['id'],
//                                'team_id' => $possessionTeamId,
//                                'start_quarter' => $drive['start']['period']['number'] ?? null,
//                                'start_time' => $drive['start']['clock']['displayValue'] ?? null,
//                                'start_yard_line' => $drive['start']['yardLine'] ?? null,
//                                'end_quarter' => $drive['end']['period']['number'] ?? null,
//                                'end_time' => $drive['end']['clock']['displayValue'] ?? '0:00',
//                                'end_yard_line' => $drive['end']['yardLine'] ?? null,
//                                'plays' => count($drive['plays'] ?? []),
//                                'yards' => $drive['yards'] ?? 0,
//                                'drive_result' => $drive['displayResult'] ?? '',
//                                'scoring_drive' => $drive['isScore'] ?? false,
//                            ]);
//
//                        foreach ($drive['plays'] ?? [] as $play) {
//                            // Get team ID for this specific play
//                            $playTeamId = $this->getPlayTeamId($play) ?? $possessionTeamId;
//
//                            // Debug logging
//                            $this->info("Play sequence: {$play['sequenceNumber']}");
//                            $this->info("Play team ID: {$playTeamId}");
//                            $this->info("Play text: {$play['text']}");
//
//                            $playModel = NflPlay::firstOrCreate(
//                                ['play_id' => $play['sequenceNumber']],
//                                [
//                                    'game_id' => $gameId,
//                                    'team_id' => $playTeamId, // Use play-specific team ID
//                                    'drive_id' => $driveModel->id,
//                                    'play_id' => $play['sequenceNumber'],
//                                    'quarter' => $play['period']['number'] ?? null,
//                                    'time' => $play['clock']['displayValue'] ?? null,
//                                    'down' => $play['start']['down'] ?? null,
//                                    'distance' => $play['start']['distance'] ?? null,
//                                    'yard_line' => $play['start']['yardLine'] ?? null,
//                                    'description' => $play['text'] ?? '',
//                                    'play_type' => $play['type']['text'] ?? '',
//                                    'yards_gained' => $play['statYardage'] ?? 0,
//                                    'first_down' => $play['firstDown'] ?? false,
//                                    'touchdown' => $play['scoringPlay'] ?? false,
//                                    'turnover' => $play['turnover'] ?? false,
//                                    'epa' => $this->calculateEPA($play),
//                                ]);
//
//                            $this->processPlayPlayers($playModel, $play, $playTeamId);
//                        }
//                    } catch (Exception $e) {
//                        Log::error("Error processing drive {$drive['id']}", [
//                            'error' => $e->getMessage(),
//                            'trace' => $e->getTraceAsString()
//                        ]);
//                        continue;
//                    }
//                }
//            });
//
//            $this->info('Play-by-play data stored successfully.');
//            return 0;
//
//        } catch (Exception $e) {
//            $this->error('Error fetching data: ' . $e->getMessage());
//            Log::error('NFL Play-by-Play Error', [
//                'error' => $e->getMessage(),
//                'trace' => $e->getTraceAsString()
//            ]);
//            return 1;
//        }
//    }
//
//    private function determinePossessionTeam(array $drive, string $homeTeamId, string $awayTeamId): string
//    {
//        // First try to get from the drive's team data
//        if (!empty($drive['team']['id'])) {
//            return $drive['team']['id'];
//        }
//
//        // Try to determine from the first play's possession
//        if (!empty($drive['plays'][0]['start']['team']['id'])) {
//            return $drive['plays'][0]['start']['team']['id'];
//        }
//
//        // Default to home team if we can't determine
//        return $homeTeamId;
//    }
//
//    private function getPlayTeamId(array $play): ?string
//    {
//        if (!empty($play['start']['team']['id'])) {
//            return $play['start']['team']['id'];
//        }
//
//        return null;
//    }
//
//    private function calculateEPA(array $play): float
//    {
//        return $this->epaCalculator->calculateEPA($play);
//    }
//
//    private function processPlayPlayers(NflPlay $play, array $playData, string $teamId): void
//    {
//        $playType = $playData['type']['text'] ?? '';
//        $description = $playData['text'] ?? '';
//
//        try {
//            switch ($playType) {
//                case 'Pass Reception':
//                case 'Pass Incompletion':
//                    if (preg_match('/^([A-Z]\.[A-Za-z-]+).*?to ([A-Z]\.[A-Za-z-]+)/', $description, $matches)) {
//                        $this->attachPlayerToPlay($play, $matches[1], 'passer', $teamId);
//                        $this->attachPlayerToPlay($play, $matches[2], 'receiver', $teamId);
//                    }
//                    break;
//
//                case 'Rush':
//                    if (preg_match('/^([A-Z]\.[A-Za-z-]+)/', $description, $matches)) {
//                        $this->attachPlayerToPlay($play, $matches[1], 'rusher', $teamId);
//                    }
//                    break;
//            }
//
//            // Extract tacklers
//            if (preg_match('/\(([A-Z]\.[A-Za-z-]+(?:, [A-Z]\.[A-Za-z-]+)*)\)/', $description, $matches)) {
//                foreach (explode(', ', $matches[1]) as $tacklerName) {
//                    $this->attachPlayerToPlay($play, $tacklerName, 'tackler', $teamId);
//                }
//            }
//        } catch (Exception $e) {
//            Log::error("Error processing players for play {$play->id}", [
//                'error' => $e->getMessage(),
//                'description' => $description,
//                'play_type' => $playType
//            ]);
//        }
//    }
//
//    private function attachPlayerToPlay(NflPlay $play, string $playerName, string $role, string $teamId): void
//    {
//        try {
//            NflPlayPlayer::firstOrCreate(
//                ['play_id' => $play->id,
//                    'player_name' => $playerName,
//                    'role' => $role],
//                [
//                    'team_id' => $teamId,
//                    'player_id' => ($playerName . $teamId), // Use a consistent ID generation
//                    'created_at' => now(),
//                    'updated_at' => now(),
//                ]);
//        } catch (Exception $e) {
//            Log::error('Error in attachPlayerToPlay', [
//                'player' => $playerName,
//                'role' => $role,
//                'play_id' => $play->id,
//                'error' => $e->getMessage()
//            ]);
//        }
//    }
//
//}