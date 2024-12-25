<?php

namespace App\Console\Commands;

use App\Models\Nfl\NflTeamSchedule;
use App\Repositories\Nfl\NflBettingOddsRepository;
use App\Repositories\Nfl\NflEloPredictionRepository;
use App\Repositories\Nfl\NflPlayerDataRepository;
use App\Repositories\Nfl\NflPlayerStatRepository;
use App\Repositories\Nfl\NflTeamStatRepository;
use App\Repositories\Nfl\TeamStatsRepository;
use App\Services\NflTrendsAnalyzer;
use App\Services\OpenAIBatchService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SubmitBatchAnalysis extends Command
{
    protected $signature = 'batch:submit {week} {--season=}';
    protected $description = 'Submit a batch of NFL game analyses to OpenAI';

    private NflTrendsAnalyzer $trendsAnalyzer;
    private NflBettingOddsRepository $oddsRepository;
    private NflTeamStatRepository $nflTeamStatRepository;
    private NflPlayerStatRepository $playerStatRepository;
    private TeamStatsRepository $teamStatRepository;
    private NflPlayerDataRepository $nflPlayerRepository;

    private NflEloPredictionRepository $eloPredictionRepository;

    private OpenAIBatchService $openAIBatchService;

    public function __construct(
        NflTrendsAnalyzer          $trendsAnalyzer,
        NflBettingOddsRepository   $oddsRepository,
        NflTeamStatRepository      $nflTeamStatRepository,
        NflPlayerStatRepository    $playerStatRepository,
        TeamStatsRepository        $teamStatRepository,
        NflPlayerDataRepository    $nflPlayerRepository,
        NflEloPredictionRepository $eloPredictionRepository,
        OpenAIBatchService         $openAIBatchService
    )
    {
        parent::__construct();
        $this->trendsAnalyzer = $trendsAnalyzer;
        $this->oddsRepository = $oddsRepository;
        $this->nflTeamStatRepository = $nflTeamStatRepository;
        $this->playerStatRepository = $playerStatRepository;
        $this->teamStatRepository = $teamStatRepository;
        $this->nflPlayerRepository = $nflPlayerRepository;
        $this->eloPredictionRepository = $eloPredictionRepository;
        $this->openAIBatchService = $openAIBatchService;
    }

    public function handle(): int
    {
        try {
            $week = (int)$this->argument('week');
            $season = $this->option('season') ?? now()->year;

            $this->info("Fetching games for game_week {$week}, season {$season}...");
            $games = NflTeamSchedule::where('game_week', $week)
                ->where('season', $season)
                ->get();

            if ($games->isEmpty()) {
                $this->error("No games found for game_week {$week}, season {$season}.");
                return 1;
            }

            $this->info("Found {$games->count()} games. Preparing analysis requests...");

            // Prepare requests
            $requests = [];
            foreach ($games as $game) {
                $request = $this->prepareAnalysisRequest($game, $week, $season);
                if ($request) {
                    $requests[] = $request;
                }
            }

            if (empty($requests)) {
                $this->error('No valid analysis requests could be prepared.');
                return 1;
            }

            // Process batch using the service
            $results = $this->openAIBatchService->submitBatch($requests);

// Store batch information for later retrieval
            Storage::put(
                "batch_info_{$week}_{$season}.json",
                json_encode([
                    'batch_id' => $results['batch_id'],
                    'file_id' => $results['file_id'],
                    'week' => $week,
                    'season' => $season,
                    'created_at' => $results['created_at'],
                    'game_count' => $results['request_count']
                ])
            );

            return 0;

        } catch (Exception $e) {
            $this->error("Error processing batch: {$e->getMessage()}");
            Log::error('Batch Processing Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    // Reuse your existing methods
    private function prepareAnalysisRequest($game, int $week, int $season): ?array
    {
        $awayTeam = $game->away_team;
        $homeTeam = $game->home_team;
        $awayRecord = $game->away_team_record;
        $homeRecord = $game->home_team_record;
        $gameId = $game->game_id;

        $this->info("Preparing analysis for: {$awayTeam} vs {$homeTeam}");

        try {
            // Define the range of weeks for trend analysis
            $startWeek = max(1, $week - 4);
            $endWeek = $week - 1;

            // Analyze trends for both teams
            $awayTrends = $this->trendsAnalyzer->analyze($awayTeam, $season, $endWeek);
            $homeTrends = $this->trendsAnalyzer->analyze($homeTeam, $season, $endWeek);

            // Fetch betting odds
            $odds = $this->oddsRepository->findByGameId($gameId);
            $bettingSummary = $this->generateBettingSummary($odds);

            // Fetch summed statistics
            $awayStatsResponse = $this->nflTeamStatRepository->getSummedTeamStats($awayTeam, $startWeek, $endWeek, $season);
            $homeStatsResponse = $this->nflTeamStatRepository->getSummedTeamStats($homeTeam, $startWeek, $endWeek, $season);

            if (!$awayStatsResponse['success']) {
                $this->error("Error fetching stats for {$awayTeam}: " . ($awayStatsResponse['message'] ?? 'Unknown error.'));
                $awayStats = [];
            } else {
                $awayStats = $awayStatsResponse['data'];
            }

            if (!$homeStatsResponse['success']) {
                $this->error("Error fetching stats for {$homeTeam}: " . ($homeStatsResponse['message'] ?? 'Unknown error.'));
                $homeStats = [];
            } else {
                $homeStats = $homeStatsResponse['data'];
            }

            // Fetch all additional statistics
            $awayScoreMargins = $this->teamStatRepository->getScoreMargins($awayTeam, null, null, null);
            $homeScoreMargins = $this->teamStatRepository->getScoreMargins($homeTeam, null, null, null);
            $awayQuarterComebacks = $this->teamStatRepository->getQuarterComebacks($awayTeam, null, null, null);
            $homeQuarterComebacks = $this->teamStatRepository->getQuarterComebacks($homeTeam, null, null, null);
            $awayScoringStreaks = $this->teamStatRepository->getScoringStreaks($awayTeam, null, null, null);
            $homeScoringStreaks = $this->teamStatRepository->getScoringStreaks($homeTeam, null, null, null);
            $awaySituationalPerformance = $this->teamStatRepository->getSituationalPerformance($awayTeam, null, null);
            $homeSituationalPerformance = $this->teamStatRepository->getSituationalPerformance($homeTeam, null, null);
            $awayQuarterScoring = $this->teamStatRepository->getQuarterScoring($awayTeam, null, null, null, null);
            $homeQuarterScoring = $this->teamStatRepository->getQuarterScoring($homeTeam, null, null, null, null);
            $awayHalfScoring = $this->teamStatRepository->getHalfScoring($awayTeam, null, null, null, null);
            $homeHalfScoring = $this->teamStatRepository->getHalfScoring($homeTeam, null, null, null, null);
            $awayAveragePoints = $this->teamStatRepository->getAveragePoints($awayTeam, null, null, null);
            $homeAveragePoints = $this->teamStatRepository->getAveragePoints($homeTeam, null, null, null);
            $awayOffensiveConsistency = $this->teamStatRepository->getOffensiveConsistency($awayTeam);
            $homeOffensiveConsistency = $this->teamStatRepository->getOffensiveConsistency($homeTeam);

            // Generate statistical comparison
            $statComparisonAnalysis = $this->generateStatComparisonAnalysis(
                $awayTeam,
                $homeTeam,
                $awayStats,
                $homeStats,
                $week,
                $awayScoreMargins,
                $homeScoreMargins,
                $awayQuarterComebacks,
                $homeQuarterComebacks,
                $awayScoringStreaks,
                $homeScoringStreaks,
                $awaySituationalPerformance,
                $homeSituationalPerformance,
                $awayQuarterScoring,
                $homeQuarterScoring,
                $awayHalfScoring,
                $homeHalfScoring,
                $awayAveragePoints,
                $homeAveragePoints,
                $awayOffensiveConsistency,
                $homeOffensiveConsistency
            );

            // Get impact players and injury reports
            $awayImpactPlayers = $this->formatImpactPlayers($this->getImpactPlayers($awayTeam, $startWeek, $endWeek, $season));
            $homeImpactPlayers = $this->formatImpactPlayers($this->getImpactPlayers($homeTeam, $startWeek, $endWeek, $season));
            $awayTeamInjuries = $this->nflPlayerRepository->getTeamInjuries($awayTeam);
            $homeTeamInjuries = $this->nflPlayerRepository->getTeamInjuries($homeTeam);
            $awayInjuryReport = $this->formatInjuryReport($awayTeam, $awayTeamInjuries);
            $homeInjuryReport = $this->formatInjuryReport($homeTeam, $homeTeamInjuries);

            // Generate summaries
            $awaySummary = $this->generateSummary($awayTeam, $awayTrends, $awayRecord, $awayStats);
            $homeSummary = $this->generateSummary($homeTeam, $homeTrends, $homeRecord, $homeStats);

            // Get ELO prediction
            $eloPrediction = $this->eloPredictionRepository->getTeamPrediction(
                teamAbv: $homeTeam,
                week: $week,
                includeStats: true
            );

            // Return the prepared request data
            return [
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a professional sports journalist with extensive experience in statistical analysis, betting trends, and creating engaging narratives.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->createAnalysisPrompt(
                            $awayTeam,
                            $homeTeam,
                            $awaySummary,
                            $homeSummary,
                            $bettingSummary,
                            $statComparisonAnalysis,
                            $awayImpactPlayers,
                            $homeImpactPlayers,
                            $awayInjuryReport,
                            $homeInjuryReport,
                            $eloPrediction
                        )
                    ]
                ],
                'options' => [
                    'temperature' => 0.7,
                    'max_tokens' => 2000,
                    'presence_penalty' => 0.3,
                    'frequency_penalty' => 0.3,
                ],
                'metadata' => [
                    'game_id' => $game->game_id,
                    'away_team' => $awayTeam,
                    'home_team' => $homeTeam,
                    'game_date' => $game->game_date,
                    'game_time' => $game->game_time,
                    'elo_prediction' => $eloPrediction // Store ELO prediction in metadata
                ]
            ];

        } catch (Exception $e) {
            $this->error("Error preparing analysis for {$awayTeam} vs {$homeTeam}: {$e->getMessage()}");
            Log::error('Analysis Preparation Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }


    // All other helper methods stay the same
    private function generateBettingSummary($odds): string
    {
        if (!$odds) {
            return 'No betting data available for this matchup.';
        }

        return "Betting Line: Home spread ({$odds->spread_home}), Away spread ({$odds->spread_away}), " .
            "Over/Under ({$odds->total_over}). Moneylines: Home ({$odds->moneyline_home}), Away ({$odds->moneyline_away}).";
    }


    /**
     * Generate a textual statistical comparison analysis between two NFL teams.
     *
     * @param string $awayTeam Name of the away team.
     * @param string $homeTeam Name of the home team.
     * @param array $awayStats Associative array of the away team's statistics.
     * @param array $homeStats Associative array of the home team's statistics.
     * @param int $week The current week number.
     * @param array $awayScoreMargins Score margins data for the away team.
     * @param array $homeScoreMargins Score margins data for the home team.
     * @param array $awayQuarterComebacks Quarter comebacks data for the away team.
     * @param array $homeQuarterComebacks Quarter comebacks data for the home team.
     * @param array $awayScoringStreaks Scoring streaks data for the away team.
     * @param array $homeScoringStreaks Scoring streaks data for the home team.
     * @param array $awaySituationalPerformance Situational performance data for the away team.
     * @param array $homeSituationalPerformance Situational performance data for the home team.
     * @param array $awayQuarterScoring Quarter scoring data for the away team.
     * @param array $homeQuarterScoring Quarter scoring data for the home team.
     * @param array $awayHalfScoring Half scoring data for the away team.
     * @param array $homeHalfScoring Half scoring data for the home team.
     * @param array $awayAveragePoints Average points data for the away team.
     * @param array $homeAveragePoints Average points data for the home team.
     * @param array $awayOffensiveConsistency Offensive consistency data for the away team.
     * @param array $homeOffensiveConsistency Offensive consistency data for the home team.
     * @return string The markdown-formatted analysis.
     */
    private function generateStatComparisonAnalysis(
        string $awayTeam,
        string $homeTeam,
        array  $awayStats,
        array  $homeStats,
        int    $week,
        array  $awayScoreMargins,
        array  $homeScoreMargins,
        array  $awayQuarterComebacks,
        array  $homeQuarterComebacks,
        array  $awayScoringStreaks,
        array  $homeScoringStreaks,
        array  $awaySituationalPerformance,
        array  $homeSituationalPerformance,
        array  $awayQuarterScoring,
        array  $homeQuarterScoring,
        array  $awayHalfScoring,
        array  $homeHalfScoring,
        array  $awayAveragePoints,
        array  $homeAveragePoints,
        array  $awayOffensiveConsistency,
        array  $homeOffensiveConsistency
    ): string
    {
        $weeksAnalyzed = $week - 1;

        // Log the stats being used
        Log::info("Generating stat comparison analysis for {$awayTeam} vs {$homeTeam}", [
            'awayStats' => $awayStats,
            'homeStats' => $homeStats,
            'awayScoreMargins' => $awayScoreMargins,
            'homeScoreMargins' => $homeScoreMargins,
            'awayQuarterComebacks' => $awayQuarterComebacks,
            'homeQuarterComebacks' => $homeQuarterComebacks,
            'awayScoringStreaks' => $awayScoringStreaks,
            'homeScoringStreaks' => $homeScoringStreaks,
            'awaySituationalPerformance' => $awaySituationalPerformance,
            'homeSituationalPerformance' => $homeSituationalPerformance,
            'awayQuarterScoring' => $awayQuarterScoring,
            'homeQuarterScoring' => $homeQuarterScoring,
            'awayHalfScoring' => $awayHalfScoring,
            'homeHalfScoring' => $homeHalfScoring,
            'awayAveragePoints' => $awayAveragePoints,
            'homeAveragePoints' => $homeAveragePoints,
            'awayOffensiveConsistency' => $awayOffensiveConsistency,
            'homeOffensiveConsistency' => $homeOffensiveConsistency,
        ]);

        // Start the analysis
        $analysis = "### Summed Statistics Comparison (Last {$weeksAnalyzed} Weeks)\n\n";

        // Introduction
        $analysis .= "In the past {$weeksAnalyzed} weeks, both **{$awayTeam}** and **{$homeTeam}** have shown varying performances across several key statistics. Here's a detailed analysis of their performances:\n\n";

        // Define the statistics and their descriptions
        $statsDescriptions = [
            'total_yards' => 'Total Yards',
            'rushing_yards' => 'Rushing Yards',
            'passing_yards' => 'Passing Yards',
            'points_allowed' => 'Points Allowed',
            'rushing_attempts' => 'Rushing Attempts',
            'fumbles_lost' => 'Fumbles Lost',
            'penalties' => 'Penalties',
            'total_plays' => 'Total Plays',
            'possession' => 'Possession Time',
            'safeties' => 'Safeties',
            'pass_completions_and_attempts' => 'Pass Completions and Attempts',
            'passing_first_downs' => 'Passing First Downs',
            'interceptions_thrown' => 'Interceptions Thrown',
            'sacks_and_yards_lost' => 'Sacks and Yards Lost',
            'third_down_efficiency' => 'Third Down Efficiency',
            'yards_per_play' => 'Yards per Play',
            'red_zone_scored_and_attempted' => 'Red Zone Scored and Attempted',
            'defensive_interceptions' => 'Defensive Interceptions',
            'defensive_or_special_teams_tds' => 'Defensive or Special Teams Touchdowns',
            'total_drives' => 'Total Drives',
            'rushing_first_downs' => 'Rushing First Downs',
            'first_downs' => 'First Downs',
            'first_downs_from_penalties' => 'First Downs from Penalties',
            'fourth_down_efficiency' => 'Fourth Down Efficiency',
            'yards_per_rush' => 'Yards per Rush',
            'turnovers' => 'Turnovers',
            'yards_per_pass' => 'Yards per Pass',
            // Add more stats as needed
        ];

        // Iterate through each statistic and append analysis
        foreach ($statsDescriptions as $statKey => $statName) {
            $awayValue = $awayStats[$statKey] ?? 0;
            $homeValue = $homeStats[$statKey] ?? 0;

            // Format numerical values
            if (is_numeric($awayValue)) {
                $awayValue = number_format((float)$awayValue, 2, '.', '');
            }

            if (is_numeric($homeValue)) {
                $homeValue = number_format((float)$homeValue, 2, '.', '');
            }

            // Generate comparative sentences
            $analysis .= "- **{$statName}:** **{$awayTeam}** recorded **{$awayValue}**, while **{$homeTeam}** achieved **{$homeValue}**. ";

            // Provide insights based on the comparison
            if ($awayValue > $homeValue) {
                $analysis .= "**{$awayTeam}** outperformed **{$homeTeam}** in this category, indicating a stronger performance.";
            } elseif ($awayValue < $homeValue) {
                $analysis .= "**{$homeTeam}** surpassed **{$awayTeam}** here, showcasing better execution.";
            } else {
                $analysis .= 'Both teams are evenly matched in this aspect.';
            }

            $analysis .= "\n\n";
        }

        // Integrate Additional Stats

        // 1. Score Margins
        $analysis .= "### Score Margins\n\n";
        $avgAwayMargin = $awayScoreMargins['data'][0]->avg_total_points ?? 'N/A';
        $avgHomeMargin = $homeScoreMargins['data'][0]->avg_total_points ?? 'N/A';
        $analysis .= "- **{$awayTeam}** has an average score margin of **{$avgAwayMargin}** points over the last {$weeksAnalyzed} weeks.\n";
        $analysis .= "- **{$homeTeam}** has an average score margin of **{$avgHomeMargin}** points over the last {$weeksAnalyzed} weeks.\n\n";

        // 2. Quarter Comebacks
        $analysis .= "### Quarter Comebacks\n\n";
        $awayQuarterComebacksCount = $awayQuarterComebacks['data'][0]->comebacks ?? 0;
        $homeQuarterComebacksCount = $homeQuarterComebacks['data'][0]->comebacks ?? 0;
        $analysis .= "- **{$awayTeam}** has achieved **{$awayQuarterComebacksCount}** quarter comebacks in the last {$weeksAnalyzed} weeks.\n";
        $analysis .= "- **{$homeTeam}** has achieved **{$homeQuarterComebacksCount}** quarter comebacks in the last {$weeksAnalyzed} weeks.\n\n";

        // 3. Scoring Streaks
        $analysis .= "### Scoring Streaks\n\n";
        $awayCurrentStreak = $awayScoringStreaks['data'][0]->current_streak ?? 'N/A';
        $homeCurrentStreak = $homeScoringStreaks['data'][0]->current_streak ?? 'N/A';
        $analysis .= "- **{$awayTeam}** is currently on a **{$awayCurrentStreak}** scoring streak.\n";
        $analysis .= "- **{$homeTeam}** is currently on a **{$homeCurrentStreak}** scoring streak.\n\n";

        // 4. Situational Performance
        $analysis .= "### Situational Performance\n\n";
        $awaySituationalRating = $awaySituationalPerformance['data'][0]->performance_rating ?? 'N/A';
        $homeSituationalRating = $homeSituationalPerformance['data'][0]->performance_rating ?? 'N/A';
        $analysis .= "- **{$awayTeam}**'s situational performance against conferences: **{$awaySituationalRating}**.\n";
        $analysis .= "- **{$homeTeam}**'s situational performance against conferences: **{$homeSituationalRating}**.\n\n";

        // 5. Quarter Scoring
        $analysis .= "### Quarter Scoring\n\n";
        $awayQuarterPoints = $awayQuarterScoring['data'][0]->avg_quarter_points ?? 'N/A';
        $homeQuarterPoints = $homeQuarterScoring['data'][0]->avg_quarter_points ?? 'N/A';
        $analysis .= "- **{$awayTeam}** averages **{$awayQuarterPoints}** points per quarter.\n";
        $analysis .= "- **{$homeTeam}** averages **{$homeQuarterPoints}** points per quarter.\n\n";

        // 6. Half Scoring
        $analysis .= "### Half Scoring\n\n";
        $awayHalfPoints = $awayHalfScoring['data'][0]->avg_half_points ?? 'N/A';
        $homeHalfPoints = $homeHalfScoring['data'][0]->avg_half_points ?? 'N/A';
        $analysis .= "- **{$awayTeam}** averages **{$awayHalfPoints}** points per half.\n";
        $analysis .= "- **{$homeTeam}** averages **{$homeHalfPoints}** points per half.\n\n";

        // 7. Average Points
        $analysis .= "### Average Points\n\n";
        $awayAvgPoints = $awayAveragePoints['data'][0]->avg_total_points ?? 'N/A';
        $homeAvgPoints = $homeAveragePoints['data'][0]->avg_total_points ?? 'N/A';
        $analysis .= "- **{$awayTeam}** averages **{$awayAvgPoints}** points per game.\n";
        $analysis .= "- **{$homeTeam}** averages **{$homeAvgPoints}** points per game.\n\n";

        // 8. Offensive Consistency
        $analysis .= "### Offensive Consistency\n\n";
        $awayConsistency = $awayOffensiveConsistency['data'][0]->consistency_rating ?? 'N/A';
        $homeConsistency = $homeOffensiveConsistency['data'][0]->consistency_rating ?? 'N/A';
        $analysis .= "- **{$awayTeam}** has an offensive consistency rating of **{$awayConsistency}**.\n";
        $analysis .= "- **{$homeTeam}** has an offensive consistency rating of **{$homeConsistency}**.\n\n";

        // Conclusion
        $analysis .= "Overall, the statistical analysis reveals the strengths and weaknesses of both teams, providing valuable insights for the upcoming matchup.\n";

        return $analysis;
    }

    private function formatImpactPlayers(array $impactPlayers): string
    {
        if (empty($impactPlayers)) {
            return 'No significant impact players identified.';
        }

        $formatted = '';

        foreach ($impactPlayers as $player) {
            $formatted .= "\n### {$player['category']}:\n";
            $formatted .= "Player: **{$player['name']}** ({$player['team']})\n";

            foreach ($player['stats'] as $key => $value) {
                $formatted .= '- **' . ucfirst(str_replace('_', ' ', $key)) . "**: {$value}\n";
            }
        }

        return $formatted;
    }


    private function getImpactPlayers(string $teamAbv, int $startWeek, int $endWeek, int $season): array
    {
        // Fetch top rushers and receivers
        $topRushers = $this->teamStatRepository->getBestRushers(
            $teamAbv,
            null,
            $startWeek,
            $endWeek,
            null,
            50, // Example threshold for rushing yards
            $season
        );

        $topReceivers = $this->teamStatRepository->getBestReceivers(
            $teamAbv,
            null,
            $startWeek,
            $endWeek,
            null,
            50, // Example threshold for receiving yards
            $season
        );

        // Log fetched players
        Log::info("Top Rushers for {$teamAbv}: ", $topRushers);
        Log::info("Top Receivers for {$teamAbv}: ", $topReceivers);

        $impactPlayers = [];

        // Format rushing stats
        if (!empty($topRushers['data'])) {
            foreach ($topRushers['data'] as $rusher) {
                $impactPlayers[] = [
                    'category' => 'Rushing',
                    'name' => $rusher->long_name,
                    'team' => $rusher->team_abv,
                    'stats' => [
                        'Total Rushing Yards' => $rusher->total_rushing_yards,
                        'Total Attempts' => $rusher->total_attempts,
                        'Rushing TDs' => $rusher->total_rushing_TDs,
                        'Average Yards Per Attempt' => $rusher->average_yards_per_attempt,
                        'Average Yards Per Game' => $rusher->avg_yards_per_game,
                        'Games Over Threshold' => $rusher->games_with_over_threshold,
                        'Percentage Over Threshold' => $rusher->percentage_over_threshold,
                    ],
                ];
            }
        }

        // Format receiving stats
        if (!empty($topReceivers['data'])) {
            foreach ($topReceivers['data'] as $receiver) {
                $impactPlayers[] = [
                    'category' => 'Receiving',
                    'name' => $receiver->long_name,
                    'team' => $receiver->team_abv,
                    'stats' => [
                        'Total Receiving Yards' => $receiver->total_receiving_yards,
                        'Average Yards Per Game' => $receiver->avg_yards_per_game,
                        'Games Over Threshold' => $receiver->games_with_over_threshold,
                        'Percentage Over Threshold' => $receiver->percentage_over_threshold,
                    ],
                ];
            }
        }

        // Log impact players
        Log::info("Impact Players for {$teamAbv}: ", $impactPlayers);

        return $impactPlayers;
    }


    private function formatInjuryReport(string $team, Collection $injuries): string
    {
        if ($injuries->isEmpty()) {
            return "No current injuries reported for {$team}.";
        }

        $report = [];

        // Group injuries by designation
        $groupedInjuries = $injuries->groupBy('injury_designation');

        foreach ($groupedInjuries as $designation => $players) {
            $playerList = $players->map(function ($player) {
                $status = '';
                if ($player->injury_return_date) {
                    $returnDate = Carbon::parse($player->injury_return_date);
                    if ($returnDate->isToday()) {
                        $status = ' (expected to return today)';
                    } elseif ($returnDate->isFuture()) {
                        $status = " (expected return: {$returnDate->format('M j')})";
                    }
                }

                return sprintf(
                    '%s (%s)%s%s',
                    $player->longName,
                    $player->pos,
                    $player->injury_description ? " - {$player->injury_description}" : '',
                    $status
                );
            })->join("\n");

            $report[] = sprintf("**%s**:\n%s", ucfirst($designation), $playerList);
        }

        return implode("\n\n", $report);
    }


    private function generateSummary(string $team, array $trends, string $record, array $stats): string
    {
        $totalGames = $this->trendsAnalyzer->games->count();
        $summary = ["The {$team} have played {$totalGames} games so far this season."];

        if (!empty($trends['general'])) {
            $general = $trends['general'];
            $summary[] = "They have a record of {$record}.";
            $summary[] = "{$general['ats']['wins']} ATS wins, {$general['ats']['losses']} losses, and {$general['ats']['pushes']} pushes ({$general['ats']['percentage']}% ATS).";
            $summary[] = "{$general['over_under']['overs']} games went OVER, and {$general['over_under']['unders']} stayed UNDER ({$general['over_under']['percentage']}%).";
        }

        // Add stats to summary
        $formattedStats = $this->formatStatsForSummary($stats);
        $summary[] = $formattedStats;

        return implode("\n", $summary);
    }


    private function formatStatsForSummary(array $stats): string
    {
        $statLines = [];
        foreach ($stats as $key => $value) {
            $statLines[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $value;
        }
        return implode(', ', $statLines);
    }


    /**
     * Generate the comparison analysis using OpenAI ChatGPT.
     *
     * @param string $team1 Name of the first team.
     * @param string $team2 Name of the second team.
     * @param string $team1Summary Summary of the first team's performance.
     * @param string $team2Summary Summary of the second team's performance.
     * @param string $bettingSummary Betting summary.
     * @param string $statComparisonAnalysis Statistical comparison analysis.
     * @param string $team1ImpactPlayers Impact players for the first team.
     * @param string $team2ImpactPlayers Impact players for the second team.
     * @return string The generated analysis content.
     */
    private function createAnalysisPrompt(
        string $team1,
        string $team2,
        string $team1Summary,
        string $team2Summary,
        string $bettingSummary,
        string $statComparisonAnalysis,
        string $team1ImpactPlayers,
        string $team2ImpactPlayers,
        string $awayInjuryReport,
        string $homeInjuryReport,
        array  $eloPrediction
    ): string
    {
        $week = (int)$this->argument('week');

        // Prepare ELO section
        $eloSection = '';
        if (!empty($eloPrediction) && isset($eloPrediction['prediction'])) {
            $pred = $eloPrediction['prediction'];
            $winProb = $pred['win_probability'] ?? 'N/A';
            $pointDiff = $pred['point_differential'] ?? 'N/A';
            $confidence = $pred['confidence_level'] ?? 'N/A';
            $team1Score = $pred['predicted_score'][$team1] ?? 'N/A';
            $team2Score = $pred['predicted_score'][$team2] ?? 'N/A';

            $eloSection = '';
            if (!empty($eloPrediction) && isset($eloPrediction['prediction'])) {
                $pred = $eloPrediction['prediction'];
                $winProb = $pred['win_probability'] ?? 'N/A';
                $pointDiff = $pred['point_differential'] ?? 'N/A';
                $confidence = $pred['confidence_level'] ?? 'N/A';
                $team1Score = $pred['predicted_score'][$team1] ?? 'N/A';
                $team2Score = $pred['predicted_score'][$team2] ?? 'N/A';

                $eloSection = "ELO Model Prediction:
- Win Probability: {$winProb}%
- Projected Point Differential: {$pointDiff} points
- Model Confidence: {$confidence}
- Projected Score: {$team1} {$team1Score} - {$team2} {$team2Score}\n";
            }

            // Create the full prompt
            return <<<PROMPT
You are a seasoned sports journalist with extensive expertise in statistical analysis, betting trends, and uncovering the underlying narratives that shape NFL games. Craft a **comprehensive**, **data-driven** article comparing **{$team1}** and **{$team2}**. Your analysis should appeal to both casual fans and seasoned bettors, blending insightful statistics with an engaging narrative that includes a slight edge of controversy to spark reader interest and debate.

### System Context:
- **Professional, journalistic tone**—maintain objectivity while allowing for bold or provocative insights where appropriate.
- Use **specific data points** to support all claims.
- **No emojis**—ensure the content remains professional and text-focused.
- Present key statistics and comparisons in a manner that is **insightful and easily digestible**.
- **Word Count**: Aim for **1000-1500 words** to ensure depth and thorough coverage.

---

## Team Analysis: Required Sections

### 1. **Picksports Playbook Week {$week} Analysis: {$team1} vs {$team2}**
Craft a compelling introduction that:
- **Immediately engages bettors** by citing the **spread** and **over/under**.
- **Highlights rivalry tensions**, hot streaks, or playoff implications.
- Introduces a **controversial or buzzworthy angle** (e.g., “Are the Patriots’ defensive stats overrated?”).
- **Sets the stage** with a strong narrative hook that questions prevailing opinions or betting lines.


**Key Elements to Cover:**
- **Current betting line** and significant **line movements**.
- Recent **ATS (Against The Spread) performance** for both teams.
- **Head-to-head betting history** and notable trends.
- **Injury updates** affecting the spread.
- **Weather conditions** impacting the over/under.
- **Public vs. sharp money splits** (if relevant).
- Any **controversial betting trends** or **market anomalies**.

---

### 2. **Head-to-Head Battle Analysis**
Deliver a gripping statistical narrative that delves deep into the matchup:

#### A) **Team Overview**
- **Season narrative** and current momentum for each team.
- Highlight **ATS records** and significant **betting trends**.
- Summarize **key statistical trends** that either support or challenge the spread.

#### B) **Statistical Showdown**
- Present **key stats** as direct advantages or disadvantages, linking them to **betting implications**.
- Focus on **stats that correlate with covering the spread** or affecting totals.
- Emphasize **dramatic statistical disparities** that could influence the game.

**Instructions for Presenting Stats:**
- **Transform statistical data into analytical paragraphs** rather than listing them.
- **Omit any statistics** that have a value of **0**.
- Provide **contextual analysis** to explain the significance of each relevant statistic.

**Guidelines:**
- **Connect statistics** to betting implications seamlessly.
- **Narrative-driven insights** over mere number presentation.
- Prioritize **matchups within the matchup** and **hidden advantages**.
- Consider **weather, venue, and recency** over full-season averages when relevant.

**Include:**
- **{$team1Summary}**
- **{$team2Summary}**
- **{$statComparisonAnalysis}**

---

### 3. **Critical Matchup Breakdown**
Analyze the key on-field battles that could sway the game and betting outcomes:

#### A) **Game-Breaking Matchups**
- Identify **2-3 crucial one-on-one or positional clashes**.
- Highlight **player props** influenced by these matchups.
- Incorporate **historical performance data** that adds a controversial or unexpected angle.

#### B) **Prop Bet Spotlight**
- Focus on **individual matchups** that present **undervalued prop opportunities**.
- Link these to **team totals** and broader betting markets.
- Address any **weather or venue impacts** on player performance.

**Instructions for Impact Players:**
- Present **impact players' statistics as comprehensive paragraphs** with analysis.
- **Exclude any statistics** that are **0**.
- Provide **insights based on the stats**, explaining their relevance to the game's outcome and betting implications.

**Example Format:**
> **LAR Impact Players:**
> Kyren Williams has been a pivotal force in **{$team1}**'s rushing game, accumulating **304 total rushing yards** on **81 attempts**, averaging **3.75 yards per attempt**, and scoring **2 rushing touchdowns**. Despite a solid performance, his average yards per attempt suggests that while he is effective in gaining short-yardage situations, there is room for explosive plays that could challenge **{$team2}**'s defense.

> Cooper Kupp has been instrumental in **{$team1}**'s passing game, amassing **681 total receiving yards** with an **average of 61.91 yards per game**. His ability to stretch the field and consistently find open receivers makes him a key target for deep passes, posing significant challenges for **{$team2}**'s secondary.

**Prop Betting Opportunities:**
- **Kyren Williams Rushing Yards:** Consider betting under due to Maye’s strong defense.
- **Cooper Kupp Receiving Yards:** Bet over based on matchup advantages.

**Include:**
- **{$team1ImpactPlayers}**
- **{$team2ImpactPlayers}**
- **Prop Betting Opportunities** with narrative-driven insights.

---

### 4. **Sharp Money Guide**
Provide bettors with advanced insights based on sharp money trends:

#### A) **Line Evolution & Sharp Action**
- Track **opening lines**, **notable movements**, and **key numbers**.
- Compare **Public vs. Sharp** money splits.
- Identify **reverse line movement** or **steam moves** that could indicate sharp action.

#### B) **Situational Trends & Edges**
**{$bettingSummary}**
- **Division/Conference trends** that defy expectations.
- **Time slot performances** and their betting implications.
- **Weather impacts** on scoring and totals.
- **Rest advantages** or **disadvantages** influencing outcomes.
- **Historical precedents** that challenge conventional betting wisdom.

**Example Format:**
> **BETTING BREAKDOWN**
> - **Opening Line:** NE -4 → **Movement:** NE remains favored as sharp money flows their way.
> - **Sharp Action:** 70% sharp vs. 30% public money indicates professional confidence in NE covering the spread.
> 
> **PROFITABLE ANGLES:**
> 1. **Situational Edge**
>    - **NE** is **6-4 ATS** in divisional games.
>    - **Trend:** NE performs well under high-pressure playoff-like scenarios.
> 
> 2. **Total Analysis**
>    - **Impact:** Clear weather supports the over, as both teams can fully execute their offensive plays.
> 
> 3. **Live Betting Strategy**
>    - **Potential Angles:** Early first-quarter momentum could set the tone for the spread movement.

**Guidelines:**
- Focus on **actionable betting angles** grounded in data.
- Highlight **historically profitable situations** and **key numbers**.
- Emphasize **sharp vs. public money** dynamics.
- Identify **live betting opportunities** connected to matchup analysis.

---

### 5. **Strategic Intelligence Report**
Unveil hidden edges and strategic elements that influence betting outcomes:

#### A) **Critical Strategic Factors**
- **Market-Moving Injuries** and their impact on schemes.
- **Scheme mismatches** that could tilt the game.
- **Weather/travel impacts** affecting team performance.
- **Rest advantages** or **short-week disadvantages**.
- **Depth chart changes** that alter game plans.

**Example Format:**
> **STRATEGIC EDGE ANALYSIS**
> 
> **INJURY IMPACT MATRIX**
> - **{$team1} Key Losses:**
>    - **Joey Porter Jr.:** His absence weakens the Rams' secondary significantly.
>    - **Ripple Effect:** Opposing teams can exploit weaker cornerback coverage.
>    - **Betting Impact:** Potential undervaluation of NE covering due to Rams' defensive vulnerabilities.
> 
> - **{$team2} Health Report:**
>    - **Chris Jones:** Out with a calf strain, limiting their defensive pressure.
>    - **Scheme Advantages:** Without Jones, NE might adjust their defensive schemes, potentially affecting their run defense.
> 
> **SCHEME WARFARE**
> - **Offensive Gameplan:** NE’s conservative play-calling vs. LA’s aggressive passing.
> - **Defensive Counter Moves:** NE’s blitz-heavy defense could pressure LA’s QB, reducing third-down efficiency.
> 
> **EXTERNAL FACTORS**
> - **Weather Impact:** Clear conditions favor LA’s aerial attack, but NE’s defense is equipped to handle it.
> - **Travel/Rest Dynamics:** NE has home-field advantage with minimal travel fatigue, enhancing their defensive performance.

**Include:**
- **{$homeInjuryReport}**
- **{$awayInjuryReport}**

**Guidelines:**
- Tie each strategic factor to **specific betting implications**.
- Analyze **coordinator adjustments** and **psychological elements**.
- Reference **historical precedents** that add depth to the analysis.

---

### 6. **Prediction Section**
Leverage both **statistical analysis** and **ELO model predictions** to forecast the game outcome:

**{$eloSection}**

**Final Prediction Structure:**

#### Game Prediction
- **Winner:** }
- **Final Score:** 
- **Spread:** [Cover/Not Cover]
- **Over/Under:** [Over/Under]
- **Confidence:** [High/Medium/Low]

**Supporting Analysis:**
Provide **2-3 sentences** explaining the key factors behind the prediction, including alignment or deviation from the **ELO model**.

**Risk Factors:**
Outline **1-2 sentences** about potential variables that could alter the outcome, such as **injuries**, **turnovers**, or **unexpected performances**.

**Example:**
> **#### Game Prediction**
> - **Winner:** NE
> - **Final Score:** LAR 24 - NE 27
> - **Spread:** Cover
> - **Over/Under:** Over
> - **Confidence:** Medium
> 
> **Supporting Analysis:** The New England Patriots' defensive prowess and ability to control possession could stifle the Rams' high-powered offense, leading to a close victory for New England. This aligns with the ELO model, which slightly favors NE based on recent performance metrics.
> 
> **Risk Factors:** Injuries on either side could significantly impact the game's outcome, and turnovers might swing momentum in favor of either team.

---

### **Style Guidelines:**
- **Clear Topic Sentences:** Start each paragraph with concise, informative topic sentences.
- **Narrative-Driven Statistics:** Present statistical comparisons within a narrative framework, avoiding tables.
- **Digestible Insights:** Break down complex statistics into easily understandable insights using clear prose.
- **Balanced Analysis:** Maintain a balance between statistical depth and narrative flow.
- **Smooth Transitions:** Use transitional phrases between sections for seamless reading.
- **Engaging Elements:** Incorporate direct quotes or insights from team personnel when available.
- **Emphasized Highlights:** Bold key statistics and insights to highlight important information.
- **Selective Bullet Points:** Use bullet points sparingly for emphasis without disrupting the narrative.
- **Conclusive Prediction:** End with a clear, justified (and slightly controversial if applicable) prediction that leaves room for discussion.

### **Additional Requirements:**
- **Article Length:** 1000-1500 words to ensure comprehensive coverage.
- **Subheadings:** Utilize clear subheadings for easy navigation and to guide the reader through the analysis.
- **Visual Emphasis:** Bold key statistics and insights to highlight important information.
- **Conclusion:** Conclude with a strong, justified prediction that ties together the analysis and encourages reader engagement or debate.

---

PROMPT;
        }
        return 0;
    }
}
