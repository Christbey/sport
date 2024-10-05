<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;

class CollegeFootballTeamAliasesSeeder extends Seeder
{
    public function run()
    {
        DB::table('college_football_team_aliases')->insert([
            ['team_id' => 40, 'alias_name' => 'Army West Point'],           // Army
            ['team_id' => 256, 'alias_name' => 'Fla. International'],       // Florida International
            ['team_id' => 416, 'alias_name' => 'Louisiana-Lafayette'],      // Louisiana
            ['team_id' => 418, 'alias_name' => 'LouisianaMonroe(ULM)'],     // Louisiana Monroe
            ['team_id' => 459, 'alias_name' => 'Miami-Florida'],            // Miami
            ['team_id' => 478, 'alias_name' => 'South Carolina State'],     // South Carolina State
            ['team_id' => 522, 'alias_name' => 'Southern Illinois'],        // Southern Illinois
            ['team_id' => 533, 'alias_name' => 'Southern Miss'],            // Southern Miss
            ['team_id' => 618, 'alias_name' => 'Tennessee-Martin'],         // Tennessee-Martin
            ['team_id' => 670, 'alias_name' => 'Texas State-San Marcos'],   // Texas State-San Marcos
            ['team_id' => 775, 'alias_name' => 'University of Utah'],       // Utah
            ['team_id' => 813, 'alias_name' => 'Virginia Tech'],            // Virginia Tech
        ]);
    }
}
