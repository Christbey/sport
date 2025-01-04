<?php

namespace App\Console\Commands\CollegeFootball;

use App\Helpers\CollegeFootballCommandHelpers;
use App\Jobs\CollegeFootball\StoreCollegeFootballEloRatings;
use Illuminate\Console\Command;
use NotificationChannels\Discord\Discord;

class FetchCollegeFootballEloRatings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:college-football-elo 
                            {year? : The year to fetch data for} 
                            {week? : The week number to fetch} 
                            {seasonType? : The season type (regular, postseason, etc.)} 
                            {team? : Specific team to fetch data for} 
                            {conference? : Specific conference to fetch data for}
                            {--force : Force the command to run even if recently executed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and store college football ELO ratings';

    /**
     * @var Discord
     */
    protected Discord $discord;

    /**
     * Create a new command instance.
     */
    public function __construct(Discord $discord)
    {
        parent::__construct();
        $this->discord = $discord;
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // Get base parameters
        $year = $this->argument('year') ?? config('college_football.season');
        $week = $this->argument('week') ?? CollegeFootballCommandHelpers::getCurrentWeek();
        $seasonType = $this->argument('seasonType') ?? config('college_football.season_type', 'regular');

        // Validate parameters
        if (!$this->validateParameters($year, $week, $seasonType)) {
            return;
        }

        // Build parameters array
        $params = [
            'year' => (int)$year,
            'week' => (int)$week,
            'seasonType' => $seasonType,
            'team' => $this->argument('team'),
            'conference' => $this->argument('conference'),
        ];

        $this->info("Fetching ELO ratings for Year: $year, Week: $week, Season Type: $seasonType");

        // Use helper to handle command execution
        CollegeFootballCommandHelpers::handleCommand(
            $this,
            "cfb_elo_fetch_{$year}_{$week}_{$seasonType}",
            function () use ($params) {
                StoreCollegeFootballEloRatings::dispatch($params);
                return 0;
            },
            $this->option('force')
        );

        $this->info('ELO ratings job dispatched successfully.');
    }

    /**
     * Validate command parameters
     */
    private function validateParameters($year, $week, $seasonType): bool
    {
        // Validate year
        if ($year < 2000 || $year > 2100) {
            $this->error('Invalid year specified.');
            return false;
        }

        // Validate week
        if ($seasonType === 'regular' && ($week < 1 || $week > 16)) {
            $this->error('Invalid week specified for regular season (must be between 1 and 16).');
            return false;
        }

        if ($seasonType === 'postseason' && ($week < 1 || $week > 3)) {
            $this->error('Invalid week specified for postseason (must be between 1 and 3).');
            return false;
        }

        // Validate season type
        $validSeasonTypes = ['regular', 'postseason', 'both', 'allstar', 'spring_regular', 'spring_postseason'];
        if (!in_array($seasonType, $validSeasonTypes)) {
            $this->error('Invalid season type specified. Valid types are: ' . implode(', ', $validSeasonTypes));
            return false;
        }

        return true;
    }
}