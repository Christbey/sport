<?php

namespace App\Console\Commands;

use App\Repositories\Nfl\NflBettingOddsRepository;
use App\Services\OpenAIChatService;
use Exception;
use Illuminate\Console\Command;

class GenerateTweetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:tweets {week : The NFL week to retrieve odds for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate tweets based on odds data for a specific week';

    protected OpenAIChatService $chatService;
    protected NflBettingOddsRepository $oddsRepository;

    public function __construct(OpenAIChatService $chatService, NflBettingOddsRepository $oddsRepository)
    {
        parent::__construct();
        $this->chatService = $chatService;
        $this->oddsRepository = $oddsRepository;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $week = (int)$this->argument('week');

        // Fetch odds using the repository
        $odds = $this->oddsRepository->getOddsByWeek($week);

        if ($odds->isEmpty()) {
            $this->warn("No odds data found for week {$week}.");
            return;
        }

        $this->info("Fetched odds data for week {$week}. Generating tweets...");

        foreach ($odds as $game) {
            // Prepare data for the AI prompt
            $data = [
                'game_date' => $game->game_date,
                'home_team' => $game->home_team,
                'away_team' => $game->away_team,
                'moneyline_home' => $game->moneyline_home,
                'moneyline_away' => $game->moneyline_away,
                'spread_home' => $game->spread_home,
                'spread_away' => $game->spread_away,
                'total_over' => $game->total_over,
                'total_under' => $game->total_under,
            ];

            $prompt = 'Generate a tweet about the upcoming game based on the following data: ' . json_encode($data);

            $messages = [
                [
                    'role' => 'system',
                    'content' => 'You are a social media assistant. Create concise and engaging tweets based on the provided game data.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ];

            try {
                $responseData = $this->chatService->getChatCompletion($messages, [
                    'temperature' => 0.8,
                    'max_tokens' => 100,
                ]);

                $tweet = $responseData['choices'][0]['message']['content'] ?? 'Unable to generate tweet';

                $this->info("Generated Tweet: $tweet");
            } catch (Exception $e) {
                $this->error('Error generating tweet for game: ' . $e->getMessage());
            }
        }
    }
}
