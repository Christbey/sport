<?php

return [
    'season' => env('CFB_SEASON', 2024),  // default to 2024 if not set
    'week' => env('CFB_WEEK', 1),         // default to week 1 if not set

    'season_start' => '2024-08-26', // First week's start date
    'season_end' => '2024-01-26',   // Last week's end date
    'season_type' => 'regular season',      // Default to regular season
    'regular season' => [
        'weeks' => [
            1 => ['start' => '2024-08-26', 'end' => '2024-09-01'],
            2 => ['start' => '2024-09-02', 'end' => '2024-09-08'],
            3 => ['start' => '2024-09-09', 'end' => '2024-09-15'],
            4 => ['start' => '2024-09-16', 'end' => '2024-09-22'],
            5 => ['start' => '2024-09-23', 'end' => '2024-09-29'],
            6 => ['start' => '2024-09-30', 'end' => '2024-10-06'],
            7 => ['start' => '2024-10-07', 'end' => '2024-10-13'],
            8 => ['start' => '2024-10-14', 'end' => '2024-10-20'],
            9 => ['start' => '2024-10-21', 'end' => '2024-10-27'],
            10 => ['start' => '2024-10-28', 'end' => '2024-11-03'],
            11 => ['start' => '2024-11-04', 'end' => '2024-11-10'],
            12 => ['start' => '2024-11-11', 'end' => '2024-11-17'],
            13 => ['start' => '2024-11-18', 'end' => '2024-11-24'],
            14 => ['start' => '2024-11-25', 'end' => '2024-12-01'],
            15 => ['start' => '2024-12-02', 'end' => '2024-12-08'],
            16 => ['start' => '2024-12-09', 'end' => '2024-12-15'],

        ],
        'postseason' => [
            'weeks' => [
                1 => ['start' => '2024-12-16', 'end' => '2024-01-12'],
                2 => ['start' => '2024-01-13', 'end' => '2024-01-19'],
                3 => ['start' => '2024-01-20', 'end' => '2024-01-26'],
                // Week for conference championships or additional games
            ],
        ],
    ],
];

