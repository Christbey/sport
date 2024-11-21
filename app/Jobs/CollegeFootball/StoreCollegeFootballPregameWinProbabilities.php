<?php
//
//namespace App\Jobs\CollegeFootball;
//
//use App\Models\CollegeFootball\CollegeFootballPregame;
//use App\Notifications\DiscordCommandCompletionNotification;
//use Exception;
//use GuzzleHttp\Client;
//use Illuminate\Bus\Queueable;
//use Illuminate\Contracts\Queue\ShouldQueue;
//use Illuminate\Foundation\Bus\Dispatchable;
//use Illuminate\Queue\InteractsWithQueue;
//use Illuminate\Queue\SerializesModels;
//use Illuminate\Support\Facades\Notification;
//
//class StoreCollegeFootballPregameWinProbabilities implements ShouldQueue
//{
//    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
//
//    protected $year;
//    protected $apiUrl;
//    protected $apiKey;
//
//    /**
//     * Create a new job instance.
//     *
//     * @param int $year
//     */
//    public function __construct(int $year)
//    {
//        $this->year = $year;
//        $this->apiUrl = 'https://api.collegefootballdata.com/metrics/wp/pregame';
//        $this->apiKey = config('services.college_football_data.key');
//    }
//
//    /**
//     * Execute the job.
//     *
//     * @return void
//     */
//    public function handle()
//    {
//        try {
//            $client = new Client();
//            $response = $client->request('GET', $this->apiUrl, [
//                'query' => [
//                    'year' => $this->year,
//                ],
//                'headers' => [
//                    'Authorization' => 'Bearer ' . $this->apiKey,
//                    'Accept' => 'application/json',
//                ],
//            ]);
//
//            $winProbabilities = json_decode($response->getBody()->getContents(), true);
//
//            foreach ($winProbabilities as $probability) {
//                CollegeFootballPregame::updateOrCreate(
//                    [
//                        'season' => $this->year,
//                        'week' => $probability['week'],
//                        'game_id' => $probability['game_id'],
//                    ],
//                    [
//                        'season_type' => $probability['season_type'] ?? null,
//                        'home_team' => $probability['home_team'] ?? null,
//                        'away_team' => $probability['away_team'] ?? null,
//                        'spread' => $probability['spread'] ?? null,
//                        'home_win_prob' => $probability['home_win_prob'] ?? null,
//                    ]
//                );
//            }
//            // Send success notification
//            Notification::route('discord', config('services.discord.channel_id'))
//                ->notify(new DiscordCommandCompletionNotification('', 'success'));
//
//        } catch (Exception $e) {
//            // Send failure notification
//            Notification::route('discord', config('services.discord.channel_id'))
//                ->notify(new DiscordCommandCompletionNotification($e->getMessage(), 'error'));
//
//        }
//    }
//}
