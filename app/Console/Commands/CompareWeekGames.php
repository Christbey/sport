<?php

namespace App\Console\Commands;

use App\Models\Nfl\NflTeamSchedule;
use App\Models\Post;
use App\Repositories\Nfl\NflBettingOddsRepository;
use App\Repositories\Nfl\NflPlayerDataRepository;
use App\Repositories\Nfl\NflPlayerStatRepository;
use App\Repositories\Nfl\NflTeamStatRepository;
use App\Repositories\Nfl\TeamStatsRepository;
use App\Services\NflTrendsAnalyzer;
use App\Services\OpenAIChatService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CompareWeekGames extends Command
{
    protected $signature = 'compare:week {week : The NFL game week to analyze} {--season= : The season year to analyze}';
    protected $description = 'Compare trends for all games in a given NFL game week.';

    private NflTrendsAnalyzer $trendsAnalyzer;
    private OpenAIChatService $chatService;
    private NflBettingOddsRepository $oddsRepository;
    private NflTeamStatRepository $nflTeamStatRepository;
    private NflPlayerStatRepository $playerStatRepository;
    private TeamStatsRepository $teamStatRepository;
    private NflPlayerDataRepository $nflPlayerRepository;

    public function __construct(
        NflTrendsAnalyzer        $trendsAnalyzer,
        OpenAIChatService        $chatService,
        NflBettingOddsRepository $oddsRepository,
        NflTeamStatRepository    $nflTeamStatRepository,
        NflPlayerStatRepository  $playerStatRepository,
        TeamStatsRepository      $teamStatRepository,
        NflPlayerDataRepository  $nflPlayerRepository
    )
    {
        parent::__construct();
        $this->trendsAnalyzer = $trendsAnalyzer;
        $this->chatService = $chatService;
        $this->oddsRepository = $oddsRepository;
        $this->nflTeamStatRepository = $nflTeamStatRepository;
        $this->playerStatRepository = $playerStatRepository;
        $this->teamStatRepository = $teamStatRepository;
        $this->nflPlayerRepository = $nflPlayerRepository;
    }

    public function handle(): void
    {
        $week = (int)$this->argument('week');
        $season = $this->option('season') ?? now()->year;

        $this->info("Fetching games for game_week {$week}, season {$season}...");
        $games = $this->getGamesForWeek($week, $season);

        if ($games->isEmpty()) {
            $this->error("No games found for game_week {$week}, season {$season}.");
            return;
        }

        $this->info("Found {$games->count()} games. Analyzing matchups...");
        foreach ($games as $game) {
            $this->analyzeMatchup($game, $week, $season);
        }
    }

    private function getGamesForWeek(int $week, int $season)
    {
        return NflTeamSchedule::where('game_week', $week)
            ->where('season', $season)
            ->get();
    }

    private function analyzeMatchup($game, int $week, int $season): void
    {
        $awayTeam = $game->away_team;
        $homeTeam = $game->home_team;
        $awayRecord = $game->away_team_record;
        $homeRecord = $game->home_team_record;
        $gameId = $game->game_id;
        $gameDate = $game->game_date;
        $gameTime = $game->game_time;

        $this->info("Analyzing matchup: {$awayTeam} vs {$homeTeam}");

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

            // Fetch additional statistics
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

            // Generate statistical comparison analysis including new stats
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

            // Fetch and format impact players
            $awayImpactPlayers = $this->formatImpactPlayers($this->getImpactPlayers($awayTeam, $startWeek, $endWeek, $season));
            $homeImpactPlayers = $this->formatImpactPlayers($this->getImpactPlayers($homeTeam, $startWeek, $endWeek, $season));

            // Add injury analysis
            $awayTeamInjuries = $this->nflPlayerRepository->getTeamInjuries($awayTeam);
            $homeTeamInjuries = $this->nflPlayerRepository->getTeamInjuries($homeTeam);

            $awayInjuryReport = $this->formatInjuryReport($awayTeam, $awayTeamInjuries);
            $homeInjuryReport = $this->formatInjuryReport($homeTeam, $homeTeamInjuries);

            // Generate summaries
            $awaySummary = $this->generateSummary($awayTeam, $awayTrends, $awayRecord, $awayStats);
            $homeSummary = $this->generateSummary($homeTeam, $homeTrends, $homeRecord, $homeStats);

            // Generate comparison analysis with prediction
            $analysis = $this->generateComparison(
                $awayTeam,
                $homeTeam,
                $awaySummary,
                $homeSummary,
                $bettingSummary,
                $statComparisonAnalysis,
                $awayImpactPlayers,
                $homeImpactPlayers,
                $awayInjuryReport,
                $homeInjuryReport
            );

            // Extract prediction from analysis
            $prediction = $this->extractPrediction($analysis);

            // Create or update the post
            $post = Post::updateOrCreate(
                ['game_id' => $gameId],
                [
                    'title' => "NFL Week {$week} Showdown: {$awayTeam} vs {$homeTeam}",
                    'slug' => Str::slug("NFL Week {$week} Showdown: {$awayTeam} vs {$homeTeam}"),
                    'content' => $analysis,
                    'week' => $week,
                    'season' => $season,
                    'away_team' => $awayTeam,
                    'home_team' => $homeTeam,
                    'game_date' => $gameDate,
                    'game_time' => $gameTime,
                    'prediction' => $prediction,
                    'published' => false,
                ]
            );

            $this->info("Post created successfully: {$post->title}");
            $this->info("View the post at: https://your-domain.com/posts/{$post->slug}");
        } catch (Exception $e) {
            $this->error("Error analyzing matchup {$awayTeam} vs {$homeTeam}: {$e->getMessage()}");
            Log::error("Matchup Analysis Error: {$e->getMessage()}", ['exception' => $e]);
        }
    }

    /**
     * Generate a betting summary based on odds data.
     *
     * @param mixed $odds Betting odds data.
     * @return string The formatted betting summary.
     */
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

    /**
     * Format impact players data into markdown.
     *
     * @param array $impactPlayers Array of impact players.
     * @return string The formatted impact players section.
     */
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
    private function generateComparison(
        string $team1,
        string $team2,
        string $team1Summary,
        string $team2Summary,
        string $bettingSummary,
        string $statComparisonAnalysis,
        string $team1ImpactPlayers,
        string $team2ImpactPlayers,
        string $awayInjuryReport,
        string $homeInjuryReport
    ): string
    {
        $prompt = <<<PROMPT
You are an experienced sports journalist with expertise in statistical analysis and betting trends. Write a compelling, data-driven article comparing {$team1} and {$team2}. Structure your analysis to create an engaging narrative that both casual fans and sophisticated analysts will appreciate.

System Context:
- Use a professional journalistic tone
- Support all claims with specific data points
- Maintain objectivity while providing expert insights
- Format key statistics and comparisons in an easily digestible way

Team Analysis Required Sections:

1. **Opening Hook**
   - Create an attention-grabbing introduction highlighting the key narrative
   - Mention any notable streaks, rivalries, or circumstances that make this matchup significant

2. **Head-to-Head Comparison**
   {$team1} Recent Performance:
   {$team1Summary}

   {$team2} Recent Performance:
   {$team2Summary}

   {$statComparisonAnalysis}

3. **Key Matchups Analysis**
   {$team1} Impact Players:
   {$team1ImpactPlayers}

   {$team2} Impact Players:
   {$team2ImpactPlayers}

4. **Betting Landscape**
   Current Lines and Movements:
   {$bettingSummary}

   Historical Betting Patterns:
   - Address home/away performance against the spread
   - Analyze over/under trends
   - Discuss line movement implications

5. **Expert Analysis Focus Points:**
   - Identify statistical mismatches that could influence the outcome
   - Analyze pace of play and style compatibility
   - Evaluate injury impacts and roster depth advantages {$homeInjuryReport}, {$awayInjuryReport}
   - Consider environmental factors (home/away, rest days, travel)
   - Examine historical matchup patterns

6. **Prediction Section**
   Provide a detailed prediction including:
   - Projected score
   - Key factors supporting the prediction
   - Confidence level in the prediction
   - Potential upset scenarios
   - Recommended betting angles (if applicable)

Style Guidelines:
- Use clear topic sentences to start each paragraph
- Present all statistical comparisons in narrative form, avoiding tables
- Break down complex statistics into digestible insights using clear prose
- Maintain a balance between statistical analysis and narrative flow
- Use transitional phrases between sections for smooth reading
- Incorporate direct quotes or insights from team personnel when available
- When presenting multiple statistics, use semicolons or natural language transitions rather than tabular formats


Additional Requirements:
- Article length: 800-1200 words
- Include subheadings for easy navigation
- Bold key statistics and insights
- Use bullet points sparingly for emphasis
- Conclude with a clear, justified prediction
PROMPT;

        try {
            $response = $this->chatService->getChatCompletion([
                [
                    'role' => 'system',
                    'content' => 'You are a professional sports journalist with extensive experience in statistical analysis, betting trends, and creating engaging narratives. Your expertise includes interpreting complex sports data and translating it into compelling insights for both casual fans and experts.'
                ],
                ['role' => 'user', 'content' => $prompt],
            ], [
                'temperature' => 0.7,  // Balance between creativity and consistency
                'max_tokens' => 2000,  // Ensure enough space for detailed analysis
                'presence_penalty' => 0.3,  // Encourage varied language
                'frequency_penalty' => 0.3,  // Avoid repetitive phrases
            ]);

            return $response['choices'][0]['message']['content'] ?? 'Unable to generate analysis.';
        } catch (Exception $e) {
            Log::error('Error generating sports analysis: ' . $e->getMessage(), [
                'team1' => $team1,
                'team2' => $team2,
            ]);
            return 'Unable to generate analysis due to an error.';
        }
    }

    /**
     * Extract the prediction from the generated analysis.
     *
     * @param string $analysis The full analysis content.
     * @return string The extracted prediction.
     */
    private function extractPrediction(string $analysis): string
    {
        // Extract the prediction from the analysis
        // Assumes the prediction section starts with "#### Prediction" or similar
        $pattern = '/#### Prediction\s*\n([\s\S]+)/i';
        if (preg_match($pattern, $analysis, $matches)) {
            return trim($matches[1]);
        }

        // Fallback: Return a default message if prediction section not found
        return 'No prediction available.';
    }
}
