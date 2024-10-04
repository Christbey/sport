<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Fetch NCAA FB data every Monday at 7:00 PM
Schedule::command('fetch:college-football-elo')->mondays()->at('19:00');
Schedule::command('fetch:college-football-fpi')->mondays()->at('19:05');
Schedule::command('fetch:sp-ratings')->mondays()->at('19:10');
Schedule::command('scrape:college-football-rankings')->mondays()->at('19:15');
Schedule::command('calculate:hypothetical-spreads')->mondays()->at('19:20');

// Fetch NFL data every day at 7:30 PM
Schedule::command('nfl:fetch-boxscore', ['--all'])->daily()->at('07:00');
