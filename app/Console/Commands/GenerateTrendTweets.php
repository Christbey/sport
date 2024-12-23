<?php

namespace App\Console\Commands;

use App\Services\NflTrendsAnalyzer;
use App\Services\OpenAIChatService;
use Exception;
use Illuminate\Console\Command;

class GenerateTrendTweets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:trend-tweets {team : The team abbreviation to analyze trends for} {--season= : The season year to analyze trends for} {--limit=10 : The number of games to analyze}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate tweets and an article based on trends analysis for a given team.';

    protected NflTrendsAnalyzer $trendsAnalyzer;
    protected OpenAIChatService $chatService;

    public function __construct(NflTrendsAnalyzer $trendsAnalyzer, OpenAIChatService $chatService)
    {
        parent::__construct();
        $this->trendsAnalyzer = $trendsAnalyzer;
        $this->chatService = $chatService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $team = $this->argument('team');
        $season = $this->option('season');
        $limit = (int)$this->option('limit');

        $this->info("Analyzing trends for team: {$team}...");

        try {
            // Analyze trends
            $trends = $this->trendsAnalyzer->analyze($team, $season, $limit);

            // Generate tweets
            $this->info('Generating tweets based on trends...');
            $trendSummaries = $this->generateSummaries($trends);

            // Send to ChatGPT for tweet and article generation
            $this->info('Sending data to ChatGPT...');
            [$tweet, $article] = $this->generateContent($team, $trendSummaries);

            // Print results
            $this->info("\nGenerated Tweet:\n{$tweet}");
            $this->info("\nGenerated Article:\n{$article}");
        } catch (Exception $e) {
            $this->error('Error analyzing trends: ' . $e->getMessage());
        }
    }

    /**
     * Generate concise summaries from trends.
     */
    private function generateSummaries(array $trends): string
    {
        $summaries = [];

        // General Trends
        if (!empty($trends['general'])) {
            $general = $trends['general'];
            $summaries[] = "The {$this->argument('team')} have a record of {$general['record']['wins']}-{$general['record']['losses']} ({$general['record']['percentage']}% win rate).";
            $summaries[] = "{$general['ats']['wins']} ATS wins, {$general['ats']['losses']} losses, and {$general['ats']['pushes']} pushes ({$general['ats']['percentage']}%).";
            $summaries[] = "{$general['over_under']['overs']} games went OVER, and {$general['over_under']['unders']} stayed UNDER ({$general['over_under']['percentage']}%).";
        }

        // Other trends
        foreach (['scoring', 'quarters', 'halves', 'margins', 'totals', 'first_score'] as $key) {
            foreach ($trends[$key] as $trend) {
                $summaries[] = ucfirst($key) . " Trend: {$trend}";
            }
        }

        return implode("\n", $summaries);
    }

    /**
     * Generate a tweet and an article using ChatGPT.
     */
    private function generateContent(string $team, string $trendSummaries): array
    {
        $prompt = <<<PROMPT
You are a sports journalist. Analyze the following trends for the team "{$team}":

{$trendSummaries}

1. Write a concise and engaging tweet summarizing these trends.
2. Write a detailed article analyzing these trends, including insights and implications for the team.
PROMPT;

        try {
            $responseData = $this->chatService->getChatCompletion([
                [
                    'role' => 'system',
                    'content' => 'You are a sports journalist assistant. Provide a tweet and an article based on the provided data.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]);

            $tweet = $responseData['choices'][0]['message']['content'] ?? 'Unable to generate tweet';
            $article = $responseData['choices'][1]['message']['content'] ?? 'Unable to generate article';

            return [$tweet, $article];
        } catch (Exception $e) {
            $this->error('Error generating content: ' . $e->getMessage());
            return ['Error generating tweet.', 'Error generating article.'];
        }
    }
}
