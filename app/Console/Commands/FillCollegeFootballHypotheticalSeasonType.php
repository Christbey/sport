<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FillCollegeFootballHypotheticalSeasonType extends Command
{
    protected $signature = 'cfb:fill-season-type';
    protected $description = 'Fill season_type in college_football_hypotheticals based on college_football_games';

    public function handle()
    {
        $this->info('Updating season_type for college football hypotheticals...');

        $updated = DB::table('college_football_hypotheticals')
            ->join('college_football_games', 'college_football_hypotheticals.game_id', '=', 'college_football_games.id')
            ->update([
                'college_football_hypotheticals.season_type' => DB::raw('college_football_games.season_type')
            ]);

        $this->info("Updated {$updated} records.");

        return 0;
    }
}