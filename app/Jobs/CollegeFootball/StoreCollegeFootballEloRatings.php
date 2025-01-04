<?php

namespace App\Jobs\CollegeFootball;

use App\Models\CollegeFootball\CollegeFootballElo;
use App\Models\CollegeFootball\CollegeFootballTeam;
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

class StoreCollegeFootballEloRatings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const API_URL = 'https://apinext.collegefootballdata.com/ratings/elo';
    private array $params;
    private array $stats = ['updated' => 0, 'missing' => [], 'changes' => []];

    public function __construct(array $params)
    {
        $this->params = [
            'year' => $params['year'] ?? null,
            'week' => $params['week'] ?? null,
            'seasonType' => $params['seasonType'] ?? 'regular',
            'team' => $params['team'] ?? null,
            'conference' => $params['conference'] ?? null,
        ];
    }

    public function handle(): void
    {
        try {
            Log::info('Fetching ELO data with params:', $this->params);

            $response = (new Client())->request('GET', self::API_URL, [
                'query' => array_filter($this->params),
                'headers' => [
                    'Authorization' => 'Bearer ' . config('services.college_football_data.key'),
                    'Accept' => 'application/json',
                ],
            ]);

            $eloData = json_decode($response->getBody(), true) ?? [];
            if (empty($eloData)) {
                throw new Exception('No ELO data received');
            }

            Log::info('Received ELO data:', ['count' => count($eloData)]);

            $teams = CollegeFootballTeam::pluck('id', 'school');
            Log::info('Found teams:', ['count' => $teams->count()]);

            foreach ($eloData as $elo) {
                if (!$teams->has($elo['team'])) {
                    $this->stats['missing'][] = $elo['team'];
                    continue;
                }

                $previousElo = CollegeFootballElo::where([
                    'team_id' => $teams[$elo['team']],
                    'year' => $elo['year'],
                    'week' => $this->params['week'],
                    'season_type' => $this->params['seasonType']
                ])->value('elo');

                try {
                    CollegeFootballElo::updateOrCreate(
                        [
                            'team_id' => $teams[$elo['team']],
                            'year' => $elo['year'],
                            'week' => $this->params['week'],
                            'season_type' => $this->params['seasonType']
                        ],
                        [
                            'team' => $elo['team'],
                            'conference' => $elo['conference'] ?? null,
                            'elo' => $elo['elo']
                        ]
                    );

                    $this->stats['updated']++;

                    if ($previousElo && abs($elo['elo'] - $previousElo) > 50) {
                        $this->stats['changes'][] = [
                            'team' => $elo['team'],
                            'from' => $previousElo,
                            'to' => $elo['elo']
                        ];
                    }
                } catch (Exception $e) {
                    Log::error('Error storing ELO data', [
                        'team' => $elo['team'],
                        'error' => $e->getMessage(),
                        'data' => $elo
                    ]);
                }
            }

            $message = "Updated ELO ratings for {$this->stats['updated']} teams for Week {$this->params['week']}.";

            if (!empty($this->stats['missing'])) {
                $message .= "\nMissing teams: " . implode(', ', array_slice($this->stats['missing'], 0, 3));
                if (count($this->stats['missing']) > 3) {
                    $message .= ' and ' . (count($this->stats['missing']) - 3) . ' more';
                }
            }

            if (!empty($this->stats['changes'])) {
                $message .= "\nSignificant changes:";
                foreach (array_slice($this->stats['changes'], 0, 3) as $change) {
                    $diff = $change['to'] - $change['from'];
                    $message .= sprintf("\n%s %s: %.0f → %.0f (%+.0f)",
                        $diff > 0 ? '📈' : '📉',
                        $change['team'],
                        $change['from'],
                        $change['to'],
                        $diff
                    );
                }
            }

            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($message, 'success'));

        } catch (Exception $e) {
            Log::error('CFB ELO fetch failed', [
                'error' => $e->getMessage(),
                'params' => $this->params
            ]);

            Notification::route('discord', config('services.discord.channel_id'))
                ->notify(new DiscordCommandCompletionNotification($e->getMessage(), 'failure'));
        }
    }
}