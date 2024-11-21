<?php

namespace App\Jobs\CollegeFootball;

use App\Models\CollegeFootball\{CollegeFootballTeam, CollegeFootballTeamAlias, Sagarin};
use App\Notifications\DiscordCommandCompletionNotification;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\{Log, Notification};

class StoreCollegeFootballRankingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const SAGARIN_URL = 'http://sagarin.com/sports/cfsend.htm';
    private const SIGNIFICANT_CHANGE = 0.1;
    private const MAX_CHANGES_TO_DISPLAY = 10;

    private array $ratingChanges = [];

    public function handle(): void
    {
        try {
            $client = new Client();
            $rankings = $this->fetchAndParseRankings($client);
            $this->processRankings($rankings);
            $this->notifyChanges();
        } catch (Exception $e) {
            $this->notifyError($e->getMessage());
            throw $e;
        }
    }

    private function fetchAndParseRankings(Client $client): array
    {
        $response = $client->get(self::SAGARIN_URL);
        $content = $response->getBody()->getContents();

        if (!preg_match_all('/\d+\s+(.+?)\s+A\s+=\s+([\d.]+)/', $content, $matches)) {
            throw new Exception('No rankings found in response');
        }

        return array_map(fn($team, $rating) => ['name' => trim($team), 'rating' => $rating], $matches[1], $matches[2]);
    }

    private function processRankings(array $rankings): void
    {
        foreach ($rankings as $ranking) {
            $team = $this->findTeam($ranking['name']);
            if (!$team) {
                Log::warning("No team found for {$ranking['name']}");
                continue;
            }
            $this->updateTeamRating($team, $ranking);
        }
    }

    private function findTeam(string $name): ?CollegeFootballTeam
    {
        return CollegeFootballTeam::where('school', $name)->first()
            ?? CollegeFootballTeamAlias::where('alias_name', $name)->first()?->team;
    }

    private function updateTeamRating(CollegeFootballTeam $team, array $ranking): void
    {
        $oldRating = Sagarin::where('id', $team->id)->value('rating');

        Sagarin::updateOrCreate(
            ['id' => $team->id],
            [
                'team_name' => $ranking['name'],
                'rating' => $ranking['rating'],
            ]
        );

        $this->trackRatingChange($team, $oldRating, $ranking['rating']);
    }

    private function trackRatingChange(CollegeFootballTeam $team, ?float $oldRating, float $newRating): void
    {
        if (!$oldRating) {
            return;
        }

        $change = $newRating - $oldRating;

        if (abs($change) > self::SIGNIFICANT_CHANGE) {
            $this->ratingChanges[] = [
                'team' => $team->school,
                'old_rating' => $oldRating,
                'new_rating' => $newRating,
                'change' => $change
            ];
        }
    }

    private function notifyChanges(): void
    {
        $message = $this->buildNotificationMessage();
        $this->sendDiscordNotification($message);
    }

    private function buildNotificationMessage(): string
    {
        if (empty($this->ratingChanges)) {
            return "**Sagarin Ratings Update**\nNo significant rating changes detected.";
        }

        usort($this->ratingChanges, fn($a, $b) => abs($b['change']) <=> abs($a['change']));
        $topChanges = array_slice($this->ratingChanges, 0, self::MAX_CHANGES_TO_DISPLAY);

        $message = "**Sagarin Ratings Update**\n\n**Top Rating Changes:**\n";
        foreach ($topChanges as $change) {
            $message .= $this->formatRatingChange($change);
        }

        return $message;
    }

    private function formatRatingChange(array $change): string
    {
        $emoji = $change['change'] > 0 ? '📈' : '📉';
        return sprintf(
            "%s **%s**\n   %.2f → %.2f (%+.2f)\n",
            $emoji,
            $change['team'],
            $change['old_rating'],
            $change['new_rating'],
            $change['change']
        );
    }

    private function sendDiscordNotification(string $message, string $type = 'success'): void
    {
        Notification::route('discord', config('services.discord.channel_id'))
            ->notify(new DiscordCommandCompletionNotification($message, $type));
    }

    private function notifyError(string $message): void
    {
        $this->sendDiscordNotification($message, 'error');
    }
}