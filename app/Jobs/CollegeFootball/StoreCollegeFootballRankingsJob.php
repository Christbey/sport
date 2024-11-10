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

    private array $ratingChanges = [];

    public function handle()
    {
        $client = new Client(['base_uri' => 'http://sagarin.com']);

        try {
            $response = $this->fetchRankings($client);

            if ($response->getStatusCode() === 200) {
                $this->processRankings($response->getBody()->getContents());

                // After processing, send notification with top changes
                $this->sendRatingChangesNotification();
            } else {
                Log::error('Failed to fetch the page. Status code: ' . $response->getStatusCode());
                throw new Exception('Failed to fetch rankings');
            }

        } catch (Exception $e) {
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($e->getMessage(), 'error'));
        }
    }

    private function fetchRankings(Client $client)
    {
        return $client->get('/sports/cfsend.htm');
    }

    private function processRankings($body)
    {
        $teams = $this->extractTeamsFromBody($body);

        if (!empty($teams)) {
            foreach ($teams as $teamData) {
                $this->saveTeamRanking($teamData['name'], $teamData['rating']);
            }
        } else {
            Log::error('No rankings found on the page.');
            throw new Exception('No rankings found');
        }
    }

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

    private function saveTeamRanking($scrapedTeam, $rating)
    {
        $team = $this->findTeamByAlias($scrapedTeam);

        if ($team) {
            // Get previous rating
            $previousRating = Sagarin::where('id', $team->id)->value('rating');

            // Calculate change if there was a previous rating
            if ($previousRating !== null) {
                $change = floatval($rating) - floatval($previousRating);

                // Only track significant changes (e.g., more than 0.1)
                if (abs($change) > 0.1) {
                    $this->ratingChanges[] = [
                        'team' => $team->school,
                        'old_rating' => $previousRating,
                        'new_rating' => $rating,
                        'change' => $change
                    ];
                }
            }

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

    private function findTeamByAlias($scrapedTeam)
    {
        $team = CollegeFootballTeam::where('school', $scrapedTeam)->first();

        if (!$team) {
            $alias = CollegeFootballTeamAlias::where('alias_name', $scrapedTeam)->first();
            $team = $alias?->team;
        }

        return $team;
    }

    private function sendRatingChangesNotification()
    {
        if (empty($this->ratingChanges)) {
            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification(
                    "**Sagarin Ratings Update**\nNo significant rating changes detected.",
                    'success'
                ));
            return;
        }

        // Sort changes by absolute change value
        usort($this->ratingChanges, function ($a, $b) {
            return abs($b['change']) <=> abs($a['change']);
        });

        // Take top 10 changes
        $topChanges = array_slice($this->ratingChanges, 0, 10);

        // Build notification message
        $message = "**Sagarin Ratings Update**\n\n";
        $message .= "**Top Rating Changes:**\n";

        foreach ($topChanges as $change) {
            $direction = $change['change'] > 0 ? '📈' : '📉';
            $message .= sprintf(
                "%s **%s**\n   %.2f → %.2f (%+.2f)\n",
                $direction,
                $change['team'],
                $change['old_rating'],
                $change['new_rating'],
                $change['change']
            );
        }

        $message .= "\n_Powered by Picksports Alerts • " . now()->format('F j, Y g:i A') . '_';

        Notification::route('discord', config('services.discord.channel_id'))
            ->notify(new DiscordCommandCompletionNotification($message, 'success'));
    }
}