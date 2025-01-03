<?php

return [
    'seasonYear' => env('NFL_SEASON_YEAR', 2024),
    'seasonType' => env('NFL_SEASON_TYPE', 'Regular Season'),
    'current_week' => env('NFL_WEEK_NUMBER', 14),
    'weeks' => [
        '1' => ['start' => '2024-08-30', 'end' => '2024-09-11'],
        '2' => ['start' => '2024-09-12', 'end' => '2024-09-18'],
        '3' => ['start' => '2024-09-19', 'end' => '2024-09-25'],
        '4' => ['start' => '2024-09-26', 'end' => '2024-10-02'],
        '5' => ['start' => '2024-10-03', 'end' => '2024-10-09'],
        '6' => ['start' => '2024-10-10', 'end' => '2024-10-16'],
        '7' => ['start' => '2024-10-17', 'end' => '2024-10-23'],
        '8' => ['start' => '2024-10-24', 'end' => '2024-10-30'],
        '9' => ['start' => '2024-10-31', 'end' => '2024-11-06'],
        '10' => ['start' => '2024-11-07', 'end' => '2024-11-13'],
        '11' => ['start' => '2024-11-14', 'end' => '2024-11-20'],
        '12' => ['start' => '2024-11-21', 'end' => '2024-11-27'],
        '13' => ['start' => '2024-11-28', 'end' => '2024-12-04'],
        '14' => ['start' => '2024-12-05', 'end' => '2024-12-11'],
        '15' => ['start' => '2024-12-12', 'end' => '2024-12-18'],
        '16' => ['start' => '2024-12-19', 'end' => '2024-12-25'],
        '17' => ['start' => '2024-12-26', 'end' => '2025-01-01'],
        '18' => ['start' => '2025-01-02', 'end' => '2025-01-08'],
    ],

    // Added variables for trend analysis
    'trends' => [
        // Game structure
        'quarters' => [1, 2, 3, 4],
        'halves' => [
            'first' => [1, 2],
            'second' => [3, 4],
        ],

        // Scoring thresholds for analysis
        'scoring_thresholds' => [
            20 => 'twenty',
            24 => 'twenty-four',
            30 => 'thirty',
        ],

        // Default options for command parameters
        'default_options' => [
            'min_occurrences' => 2,
            'games' => 20,
        ],

        // Betting unit
        'unit' => 100,

        // Thresholds for half scoring
        'half_scoring_thresholds' => [
            'high' => 14,
            'mid' => 7,
        ],

        // Thresholds for total points in games
        'total_points_thresholds' => [
            'high' => 50,
            'low' => 40,
        ],

        // Spread ranges for categorizing favorites and underdogs
        'spread_ranges' => [
            [
                'min' => -20,
                'max' => -10.5,
                'label' => 'Heavy Favorite (-20 to -10.5)',
            ],
            [
                'min' => -10,
                'max' => -3.5,
                'label' => 'Moderate Favorite (-10 to -3.5)',
            ],
            [
                'min' => -3,
                'max' => -1,
                'label' => 'Slight Favorite (-3 to -1)',
            ],
            [
                'min' => 1,
                'max' => 3,
                'label' => 'Slight Underdog (+1 to +3)',
            ],
            [
                'min' => 3.5,
                'max' => 10,
                'label' => 'Moderate Underdog (+3.5 to +10)',
            ],
            [
                'min' => 10.5,
                'max' => 20,
                'label' => 'Heavy Underdog (+10.5 to +20)',
            ],
        ],

        // Conditions for perfect Against The Spread (ATS) records
        'perfect_ats_conditions' => [
            [
                'field' => 'points',
                'operator' => '>=',
                'value' => 24,
                'description' => 'When scoring 24+ points',
            ],
            [
                'field' => 'rushing_yards',
                'operator' => '>=',
                'value' => 150,
                'description' => 'With 150+ rushing yards',
            ],
            [
                'field' => 'passing_yards',
                'operator' => '>=',
                'value' => 250,
                'description' => 'With 250+ passing yards',
            ],
        ],

        // Additional thresholds for analysis
        'additional_thresholds' => [
            'scoring_thresholds' => [20, 24, 27, 30],
            'yardage_thresholds' => [
                'total_yards' => [300, 350, 400],
                'rushing_yards' => [100, 125, 150],
                'passing_yards' => [200, 250, 300],
            ],
            'quarter_high_scoring' => 7,
            'double_digit_win_margin' => 10,
            'close_game_margin' => 7,
        ],

        // Labels for game outcomes
        'result_labels' => [
            'W' => 'Win',
            'L' => 'Loss',
            'COVER' => 'Covered Spread',
            'MISS' => 'Missed Spread',
            'OVER' => 'Over Total',
            'UNDER' => 'Under Total',
        ],
    ],


    'queries' => [
        'average_points' => [
            'name' => 'Average Points by Quarter',
            'parameters' => ['games_back', 'date_range', 'season', 'week', 'day_of_week', 'location'],
        ],
        'quarter_scoring' => [
            'name' => 'Quarter-by-Quarter Analysis',
            'parameters' => ['games_back', 'date_range', 'season', 'week', 'day_of_week'],
        ],
        // ... other queries
    ],

    'parameters' => [
        'games_back' => [
            'type' => 'numeric',
            'options' => [3, 5, 10, 15],
            'default' => null,
            'validation' => 'nullable|integer|in:3,5,10,15',
            'description' => 'Number of recent games to analyze'
        ],
        'date_range' => [
            'type' => 'daterange',
            'requires' => ['start_date', 'end_date'],
            'validation' => [
                'start_date' => 'required_with:end_date|nullable|date',
                'end_date' => 'required_with:start_date|nullable|date|after_or_equal:start_date'
            ],
            'default' => null,
            'description' => 'Custom date range'
        ],
        'season' => [
            'type' => 'numeric',
            'options' => 'dynamic', // Will be generated from 2020 to current year
            'validation' => 'nullable|integer|min:2020|max:' . date('Y'),
            'default' => null,
            'description' => 'NFL Season year'
        ],
        'week' => [
            'type' => 'numeric',
            'options' => range(1, 18),
            'validation' => 'nullable|integer|between:1,18',
            'default' => null,
            'description' => 'NFL Week number'
        ],
        'day_of_week' => [
            'type' => 'array',
            'options' => ['Sunday', 'Monday', 'Thursday', 'Saturday'],
            'validation' => [
                'day_of_week' => 'nullable|array',
                'day_of_week.*' => 'required|in:Sunday,Monday,Thursday,Saturday'
            ],
            'default' => null,
            'description' => 'Game day of the week'
        ],
        'location' => [
            'type' => 'string',
            'options' => ['home', 'away', 'all'],
            'validation' => 'nullable|in:home,away,all',
            'default' => 'all',
            'description' => 'Game location'
        ],
    ],

    'markets' => [
        'player_assists' => [
            'label' => 'Assists (Over/Under)',
            'column' => 'defense',
            'key' => 'assists',
        ],
        'player_defensive_interceptions' => [
            'label' => 'Defensive Interceptions (Over/Under)',
            'column' => 'defense',
            'key' => 'interceptions',
        ],
        'player_field_goals' => [
            'label' => 'Field Goals (Over/Under)',
            'column' => 'kicking',
            'key' => 'field_goals',
        ],
        'player_kicking_points' => [
            'label' => 'Kicking Points (Over/Under)',
            'column' => 'kicking',
            'key' => 'points',
        ],
//        'player_pass_attempts' => [
//            'label' => 'Pass Attempts (Over/Under)',
//            'column' => 'passing',
//            'key' => 'attempts',
//        ],
//        'player_pass_completions' => [
//            'label' => 'Pass Completions (Over/Under)',
//            'column' => 'passing',
//            'key' => 'completions',
//        ],
//        'player_pass_interceptions' => [
//            'label' => 'Pass Interceptions (Over/Under)',
//            'column' => 'passing',
//            'key' => 'interceptions',
//        ],
//        'player_pass_longest_completion' => [
//            'label' => 'Longest Pass Completion (Over/Under)',
//            'column' => 'passing',
//            'key' => 'longest_completion',
//        ],
//        'player_pass_rush_reception_tds' => [
//            'label' => 'Pass + Rush + Reception Touchdowns (Over/Under)',
//            'column' => null, // Requires a calculated stat
//            'key' => null,    // Composite stat
//        ],
//        'player_pass_tds' => [
//            'label' => 'Pass Touchdowns (Over/Under)',
//            'column' => 'passing',
//            'key' => 'touchdowns',
//        ],
//        'player_pass_yds' => [
//            'label' => 'Pass Yards (Over/Under)',
//            'column' => 'passing',
//            'key' => 'yards',
//        ],
        'player_receptions' => [
            'label' => 'Receptions (Over/Under)',
            'column' => 'receiving',
            'key' => 'receptions',
        ],
        'player_reception_longest' => [
            'label' => 'Longest Reception (Over/Under)',
            'column' => 'receiving',
            'key' => 'longRec',
        ],
        'player_reception_yds' => [
            'label' => 'Reception Yards (Over/Under)',
            'column' => 'receiving',
            'key' => 'recYds',
        ],
        'player_rush_attempts' => [
            'label' => 'Rush Attempts (Over/Under)',
            'column' => 'rushing',
            'key' => 'carries',
        ],
        'player_rush_yds' => [
            'label' => 'Rush Yards (Over/Under)',
            'column' => 'rushing',
            'key' => 'rushYds',
        ],
        'player_tackles_assists' => [
            'label' => 'Tackles + Assists (Over/Under)',
            'column' => 'defense',
            'key' => 'tackles_assists',
        ],
        'player_sacks' => [
            'label' => 'Sacks (Over/Under)',
            'column' => 'defense',
            'key' => 'sacks',
        ],
        // Add more mappings as needed
    ],
];
