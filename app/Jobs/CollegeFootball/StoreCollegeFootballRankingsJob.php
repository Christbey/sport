<?php

namespace App\Jobs\CollegeFootball;

use App\Models\CollegeFootball\CollegeFootballTeam;
use App\Models\CollegeFootball\CollegeFootballTeamAlias;
use App\Models\CollegeFootball\Sagarin;
use App\Notifications\DiscordCommandCompletionNotification;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class StoreCollegeFootballRankingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        // Initialize the Guzzle client here to avoid serialization issues
        $client = new Client(['base_uri' => 'http://sagarin.com']);

        try {
            $response = $this->fetchRankings($client);

            if ($response->getStatusCode() === 200) {
                $this->processRankings($response->getBody()->getContents());
            } else {
                Log::error('Failed to fetch the page. Status code: ' . $response->getStatusCode());
            }
            // Send success notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification('', 'success'));

        } catch (Exception $e) {
            // Send failure notification
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($e->getMessage(), 'error'));

        }
    }

    /**
     * Fetch the rankings from the Sagarin website.
     */
    private function fetchRankings(Client $client)
    {
        return $client->get('/sports/cfsend.htm');
    }

    /**
     * Process the rankings from the HTML body content.
     */
    private function processRankings($body)
    {
        $teams = $this->extractTeamsFromBody($body);

        if (!empty($teams)) {
            foreach ($teams as $teamData) {
                $this->saveTeamRanking($teamData['name'], $teamData['rating']);
            }
        } else {
            Log::error('No rankings found on the page.');
        }
    }

    /**
     * Extract team names and ratings from the HTML body content.
     */
    private function extractTeamsFromBody($body)
    {
        preg_match_all('/\d+\s+(.+?)\s+A\s+=\s+([\d.]+)/', $body, $matches);
        $teams = [];

        if (!empty($matches[1])) {
            foreach ($matches[1] as $index => $teamName) {
                $teams[] = [
                    'name' => trim($teamName),
                    'rating' => $matches[2][$index],
                ];
            }
        }

        return $teams;
    }

    /**
     * Find and save the team ranking in the Sagarin table.
     */
    private function saveTeamRanking($scrapedTeam, $rating)
    {
        $team = $this->findTeamByAlias($scrapedTeam);

        if ($team) {
            Log::info("Scraped Team: {$scrapedTeam} | Matched to: {$team->school} | Rating: {$rating}");

            Sagarin::updateOrCreate(
                ['id' => $team->id],
                [
                    'team_name' => $scrapedTeam,
                    'rating' => $rating,
                ]
            );
        } else {
            Log::warning("Scraped Team: {$scrapedTeam} | No match found | Rating: {$rating}");
        }
    }

    /**
     * Find a team by its name or alias from the alias table.
     */
    private function findTeamByAlias($scrapedTeam)
    {
        $team = CollegeFootballTeam::where('school', $scrapedTeam)->first();

        if (!$team) {
            $alias = CollegeFootballTeamAlias::where('alias_name', $scrapedTeam)->first();
            $team = $alias?->team;
        }

        return $team;
    }
}
