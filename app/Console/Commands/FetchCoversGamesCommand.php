<?php
//
//namespace App\Console\Commands;
//
//use Exception;
//use GuzzleHttp\Client;
//use Illuminate\Console\Command;
//use Illuminate\Support\Facades\Log;
//use Symfony\Component\DomCrawler\Crawler;
//
//class FetchCoversGamesCommand extends Command
//{
//    protected $signature = 'covers:fetch-games {game_id? : Specific game ID to fetch trends for}';
//    protected $description = 'Fetch NFL games from Covers.com';
//    protected $client;
//
//    public function __construct()
//    {
//        parent::__construct();
//
//        $this->client = new Client([
//            'base_uri' => 'https://www.covers.com',
//            'timeout' => 30.0, // Increased timeout
//            'headers' => [
//                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
//                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
//                'Accept-Language' => 'en-US,en;q=0.9',
//                'Accept-Encoding' => 'gzip, deflate, br',
//                'Connection' => 'keep-alive',
//                'Cache-Control' => 'max-age=0',
//                'Sec-Ch-Ua' => '"Not_A Brand";v="8", "Chromium";v="120"',
//                'Sec-Ch-Ua-Mobile' => '?0',
//                'Sec-Ch-Ua-Platform' => '"Windows"',
//                'Sec-Fetch-Dest' => 'document',
//                'Sec-Fetch-Mode' => 'navigate',
//                'Sec-Fetch-Site' => 'none',
//                'Sec-Fetch-User' => '?1',
//                'Upgrade-Insecure-Requests' => '1',
//            ],
//            'verify' => false, // Skip SSL verification if needed
//            'allow_redirects' => true,
//            'http_errors' => false, // Don't throw exceptions for HTTP errors
//        ]);
//    }
//
//    public function handle()
//    {
//        try {
//            $gameId = $this->argument('game_id');
//
//            if ($gameId) {
//                $this->fetchGameTrends($gameId);
//            } else {
//                $this->fetchAllGames();
//            }
//
//            return 0;
//        } catch (Exception $e) {
//            $this->error('Fatal error: ' . $e->getMessage());
//            Log::error('FetchCoversGames command failed: ' . $e->getMessage());
//            return 1;
//        }
//    }
//
//    protected function fetchGameTrends($gameId)
//    {
//        $this->info("Fetching trends for game ID: {$gameId}");
//
//        try {
//            $url = "/sport/football/nfl/matchup/{$gameId}/trends";
//            $response = $this->client->request('GET', $url);
//
//            // Debug response
//            $statusCode = $response->getStatusCode();
//            $this->info("Response status code: {$statusCode}");
//
//            if ($statusCode !== 200) {
//                $this->error("Failed to fetch trends. Status code: {$statusCode}");
//                return;
//            }
//
//            $html = $response->getBody()->getContents();
//
//            // Debug HTML
//            if (empty($html)) {
//                $this->error('Received empty HTML response');
//                return;
//            }
//
//            $this->info('Parsing HTML content...');
//
//            $crawler = new Crawler($html);
//
//            // Try different selectors as the site structure might have changed
//            $trendSelectors = [
//                'h4.High',
//                '.covers-MatchupsTrends-item',
//                '.covers-MatchupsTrends-trend',
//                '.covers-CoversMatchupsTrends-trends div'
//            ];
//
//            $trends = collect();
//
//            foreach ($trendSelectors as $selector) {
//                $this->info("Trying selector: {$selector}");
//
//                $found = $crawler->filter($selector)->each(function (Crawler $node) {
//                    return trim($node->text());
//                });
//
//                if (!empty($found)) {
//                    $trends = $trends->merge($found);
//                    $this->info('Found ' . count($found) . " trends with selector {$selector}");
//                }
//            }
//
//            if ($trends->isEmpty()) {
//                $this->warn('No trends found for this game.');
//                // Save HTML for debugging
//                $debugFile = storage_path("logs/covers_debug_{$gameId}.html");
//                file_put_contents($debugFile, $html);
//                $this->info("Saved HTML response to: {$debugFile}");
//                return;
//            }
//
//            $this->info('Game Trends:');
//            $trends->unique()->values()->each(function ($trend, $index) {
//                $this->line(sprintf('%d. %s', $index + 1, $trend));
//            });
//
//        } catch (Exception $e) {
//            $this->error('Error fetching game trends: ' . $e->getMessage());
//            Log::error('Error fetching game trends: ' . $e->getMessage());
//        }
//    }
//
//    protected function fetchAllGames()
//    {
//        $this->info('Fetching NFL games from Covers.com...');
//
//        try {
//            $response = $this->client->request('GET', '/sports/nfl/matchups');
//            $statusCode = $response->getStatusCode();
//
//            $this->info("Response status code: {$statusCode}");
//
//            if ($statusCode !== 200) {
//                $this->error("Failed to fetch games. Status code: {$statusCode}");
//                return;
//            }
//
//            $html = $response->getBody()->getContents();
//
//            // Debug HTML
//            if (empty($html)) {
//                $this->error('Received empty HTML response');
//                return;
//            }
//
//            $crawler = new Crawler($html);
//
//            // Try different selectors
//            $gameSelectors = [
//                'article.covers-CoversScoreboard-gameBox',
//                '.covers-MatchupsScoreboard-game',
//                '.covers-GameBox'
//            ];
//
//            $games = collect();
//
//            foreach ($gameSelectors as $selector) {
//                $this->info("Trying selector: {$selector}");
//
//                $found = $crawler->filter($selector)->each(function (Crawler $node) {
//                    try {
//                        return [
//                            'covers_game_id' => basename($node->attr('data-url') ?? ''),
//                            'away_team' => $node->attr('data-away-team-fullname') ??
//                                $node->filter('.away-team')->text(),
//                            'home_team' => $node->attr('data-home-team-fullname') ??
//                                $node->filter('.home-team')->text(),
//                            'game_time' => $node->filter('.game-time, .status')->text(),
//                        ];
//                    } catch (Exception $e) {
//                        $this->warn('Failed to parse game: ' . $e->getMessage());
//                        return null;
//                    }
//                });
//
//                $found = array_filter($found);
//
//                if (!empty($found)) {
//                    $games = $games->merge($found);
//                    $this->info('Found ' . count($found) . " games with selector {$selector}");
//                    break;
//                }
//            }
//
//            if ($games->isEmpty()) {
//                $this->warn('No games found.');
//                $debugFile = storage_path('logs/covers_debug_games.html');
//                file_put_contents($debugFile, $html);
//                $this->info("Saved HTML response to: {$debugFile}");
//                return;
//            }
//
//            $headers = ['Game ID', 'Away Team', 'Home Team', 'Game Time'];
//            $rows = $games->map(function ($game) {
//                return [
//                    $game['covers_game_id'],
//                    $game['away_team'],
//                    $game['home_team'],
//                    $game['game_time'],
//                ];
//            })->toArray();
//
//            $this->table($headers, $rows);
//            $this->info(sprintf('Found %d games', count($games)));
//
//        } catch (Exception $e) {
//            $this->error('Error fetching games: ' . $e->getMessage());
//            Log::error('Error fetching games: ' . $e->getMessage());
//        }
//    }
//}