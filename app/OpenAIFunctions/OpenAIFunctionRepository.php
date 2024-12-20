<?php

namespace App\OpenAIFunctions;

class OpenAIFunctionRepository
{
    /**
     * Retrieve OpenAI function definitions for use in the API.
     *
     * @return array The function definitions.
     */
    public static function getFunctions(): array
    {
        return [


            [
                'name' => 'get_average_points',
                'description' => 'Get average points statistics for teams, with optional filters for team, location, conference, and division.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamFilter' => [
                            'type' => 'string',
                            'description' => 'The team abbreviation to filter by (optional).'
                        ],
                        'locationFilter' => [
                            'type' => 'string',
                            'description' => "The location filter ('home', 'away', or null for both)."
                        ],
                        'conferenceFilter' => [
                            'type' => 'string',
                            'description' => 'The conference abbreviation to filter by (optional).'
                        ],
                        'divisionFilter' => [
                            'type' => 'string',
                            'description' => 'The division to filter by (optional).'
                        ]
                    ]
                ]
            ],

            // NFL scores
            [
                'name' => 'calculate_team_scores',
                'description' => 'Calculates scores for teams based on various parameters such as team abbreviations, game IDs, week, and location.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'gameIds' => [
                            'type' => 'array',
                            'description' => 'Array of game IDs to filter scores.',
                            'items' => [
                                'type' => 'string',
                                'description' => 'Game ID'
                            ]
                        ],
                        'teamAbv1' => [
                            'type' => 'string',
                            'description' => 'Abbreviation of the first team (optional)'
                        ],
                        'teamAbv2' => [
                            'type' => 'string',
                            'description' => 'Abbreviation of the second team (optional)'
                        ],
                        'week' => [
                            'type' => 'integer',
                            'description' => 'Week number for which the scores are calculated (optional)'
                        ],
                        'locationFilter' => [
                            'type' => 'string',
                            'description' => "Location filter for scores, can be 'home' or 'away' (optional)"
                        ]
                    ],
                    'required' => [], // No fields are strictly required; logic handles optional cases dynamically
                    'additionalProperties' => false
                ]
            ],

            // NFL best receivers by week,
            [
                'name' => 'get_best_receivers',
                'description' => 'Retrieve a list of the best NFL receivers filtered by team or week. Use this to analyze top receiving performances.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamFilter' => [
                            'type' => 'string',
                            'description' => 'The abbreviation of the NFL team to filter receivers by (optional).'
                        ],
                        'week' => [
                            'type' => 'integer',
                            'description' => 'The specific week number to filter receiving stats (optional).'
                        ],
                        'startWeek' => [
                            'type' => 'integer',
                            'description' => 'Start week for filtering results within a range (optional).'
                        ],
                        'endWeek' => [
                            'type' => 'integer',
                            'description' => 'End week for filtering results within a range (optional).'
                        ],
                        'playerFilter' => [
                            'type' => 'string',
                            'description' => 'The name of the player to filter results for (optional).'
                        ],
                        'yardThreshold' => [
                            'type' => 'integer',
                            'description' => 'The yardage threshold for filtering player performances (optional).'
                        ],
                        'season' => [
                            'type' => 'integer',
                            'description' => 'The season year to filter results for (optional). Defaults to the current season year.'
                        ]
                    ],
                    'required' => []
                ]

            ],


            [
                'name' => 'get_best_tacklers',
                'description' => 'Retrieves the best tacklers based on defensive statistics, filtered by team, player, week(s), or other criteria.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamFilter' => [
                            'type' => 'string',
                            'description' => 'The abbreviation of the team to filter the results. Optional.'
                        ],
                        'playerFilter' => [
                            'type' => 'string',
                            'description' => 'The name of the player to filter the results (case-insensitive). Optional.'
                        ],
                        'tackleThreshold' => [
                            'type' => 'integer',
                            'description' => 'The minimum number of tackles in a game to include in the results. Optional.'
                        ],
                        'week' => [
                            'type' => 'integer',
                            'description' => 'Specific week number to filter the results. Optional.'
                        ],
                        'startWeek' => [
                            'type' => 'integer',
                            'description' => 'Start week for filtering results within a range. Optional.'
                        ],
                        'endWeek' => [
                            'type' => 'integer',
                            'description' => 'End week for filtering results within a range. Optional.'
                        ],
                        'season' => [
                            'type' => 'integer',
                            'description' => 'The NFL season year to filter the results. Optional.'
                        ]
                    ],
                    'required' => [] // All fields are optional to allow flexible queries
                ]
            ],

            [
                'name' => 'analyze_team_quarterly_performance',
                'description' => 'Advanced analysis of team performance by quarter with comprehensive filtering and statistical breakdown.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamAbv' => [
                            'type' => 'string',
                            'description' => 'Team abbreviation to analyze (optional).'
                        ],
                        'season' => [
                            'type' => 'integer',
                            'description' => 'Specific NFL season year to filter results (optional).'
                        ],
                        'locationFilter' => [
                            'type' => 'string',
                            'description' => "Location filter for scores ('home', 'away', or null for both).",
                            'enum' => ['home', 'away', null]
                        ],
                        'performanceMetrics' => [
                            'type' => 'array',
                            'description' => 'Specify which performance metrics to calculate.',
                            'items' => [
                                'type' => 'string',
                                'enum' => ['points', 'yards', 'turnovers', 'scoring_drives']
                            ]
                        ],
                        'aggregationType' => [
                            'type' => 'string',
                            'description' => 'Type of statistical aggregation to perform.',
                            'enum' => ['average', 'total', 'detailed']
                        ]
                    ],
                    'required' => [], // No mandatory parameters
                    'additionalProperties' => false
                ]
            ],

            [
                'name' => 'get_quarterly_points_analysis',
                'description' => 'Analyze quarterly points performance for NFL teams with comprehensive filtering and comparison options.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teams' => [
                            'type' => 'array',
                            'description' => 'Array of team abbreviations to analyze (required, max 2 for direct comparison or 1 for single-team analysis).',
                            'items' => [
                                'type' => 'string',
                                'description' => 'Team abbreviation (e.g., KC, SF)'
                            ]
                        ],
                        'season' => [
                            'type' => 'integer',
                            'description' => 'Specific NFL season year to filter results (optional)'
                        ],
                        'conferenceFilter' => [
                            'type' => 'string',
                            'description' => 'Filter results by conference abbreviation (optional)'
                        ],
                        'divisionFilter' => [
                            'type' => 'string',
                            'description' => 'Filter results by division (optional)'
                        ],
                        'returnType' => [
                            'type' => 'string',
                            'description' => 'Specify the type of return data. Defaults to "both" for team stats and comparisons.',
                            'enum' => ['team_stats', 'comparison', 'both'],
                            'default' => 'both'
                        ]
                    ],
                    'required' => ['teams'], // Require teams to ensure proper query construction
                    'additionalProperties' => false
                ]
            ],


            [
                'name' => 'get_best_rushers',
                'description' => 'Retrieves the best rushers based on rushing statistics filtered by team, player, week(s), or other criteria.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamFilter' => [
                            'type' => 'string',
                            'description' => 'The abbreviation of the team to filter the results. Optional.'
                        ],
                        'playerFilter' => [
                            'type' => 'string',
                            'description' => 'The name of the player to filter the results (case-insensitive). Optional.'
                        ],
                        'yardThreshold' => [
                            'type' => 'integer',
                            'description' => 'The minimum number of rushing yards in a game to include in the results. Optional.'
                        ],
                        'week' => [
                            'type' => 'integer',
                            'description' => 'Specific week number to filter the results. Optional.'
                        ],
                        'startWeek' => [
                            'type' => 'integer',
                            'description' => 'Start week for filtering results within a range. Optional.'
                        ],
                        'endWeek' => [
                            'type' => 'integer',
                            'description' => 'End week for filtering results within a range. Optional.'
                        ],
                        'season' => [
                            'type' => 'integer',
                            'description' => 'The NFL season year to filter the results. Optional.'
                        ]
                    ],
                    'required' => [] // No required fields for maximum flexibility
                ]
            ],

            [
                'name' => 'get_first_downs_average',
                'description' => 'Retrieve the average first downs per game for one or more NFL teams.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamFilters' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                                'description' => 'The abbreviation of an NFL team (e.g., KC for Kansas City Chiefs).'
                            ],
                            'description' => 'An array of team abbreviations to retrieve averages for.',
                            'minItems' => 1
                        ],
                        'week' => [
                            'type' => 'integer',
                            'description' => 'Specific week number to filter the results (optional).'
                        ],
                        'season' => [
                            'type' => 'integer',
                            'description' => 'The season year to filter results (optional). Defaults to the current season.'
                        ]
                    ],
                    'required' => ['teamFilters']
                ]
            ],


            [
                'name' => 'get_situational_performance',
                'description' => 'Fetches and calculates situational performance metrics for NFL teams, filtered by team abbreviation, location, and the conference they are playing against.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamFilter' => [
                            'type' => 'string',
                            'description' => 'Optional team abbreviation to filter the performance metrics. If null, metrics for all teams will be provided.'
                        ],
                        'locationFilter' => [
                            'type' => 'string',
                            'description' => "Optional location filter for stats, can be 'home', 'away', or 'combined'. If null, all locations are included."
                        ],
                        'againstConference' => [
                            'type' => 'string',
                            'description' => 'Optional filter for the opposing conference (e.g., AFC, NFC) that the team is playing against.'
                        ]
                    ],
                    'required' => [], // No required fields; all are optional
                    'additionalProperties' => false
                ]
            ],


            // NFL big playmakers
            [
                'name' => 'get_big_playmakers',
                'description' => 'Get the top NFL players with significant plays (e.g., receptions or rushes over 20 yards).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamFilter' => [
                            'type' => 'string',
                            'description' => 'The team abbreviation to filter by (optional).'
                        ]
                    ]
                ]
            ],

            // NFL team matchup edge
            [
                'name' => 'get_team_matchup_edge',
                'description' => 'Fetches matchup edge metrics for NFL teams based on recent performances, including yards differential, points differential, win probability, and edge score. Filters can include specific teams, weeks, and locations.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamFilter' => [
                            'type' => 'string',
                            'description' => 'Optional team abbreviation to filter matchup edge metrics. If null, metrics for all teams will be provided.'
                        ],
                        'teamAbv1' => [
                            'type' => 'string',
                            'description' => 'Abbreviation of the first team (optional).'
                        ],
                        'teamAbv2' => [
                            'type' => 'string',
                            'description' => 'Abbreviation of the second team (optional).'
                        ],
                        'week' => [
                            'type' => 'integer',
                            'description' => 'Week number for which the scores are calculated (optional).'
                        ],
                        'locationFilter' => [
                            'type' => 'string',
                            'description' => "Location filter for scores, can be 'home' or 'away' (optional)."
                        ]
                    ],
                    'required' => ['teamFilter'], // Adjust as needed if any other property should be required
                    'additionalProperties' => false
                ]
            ],

            // NFL first half tendencies
            [
                'name' => 'get_first_half_tendencies',
                'description' => 'Get first-half tendencies for a specified team, including average points scored and allowed, filtered by opposing conference and location.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamFilter' => [
                            'type' => 'string',
                            'description' => 'The team abbreviation to filter by.'
                        ],
                        'againstConference' => [
                            'type' => 'string',
                            'description' => 'Optional filter for the opposing team’s conference (e.g., AFC, NFC). If null, no conference filter is applied.'
                        ],
                        'locationFilter' => [
                            'type' => 'string',
                            'description' => "Optional location filter, can be 'home', 'away', or 'combined'. If null, all locations are included."
                        ]
                    ],
                    'required' => ['teamFilter'], // Only teamFilter is required; others are optional
                    'additionalProperties' => false
                ]
            ],

            // NFL get schedule by team
//            [
//                'name' => 'get_schedule_by_team',
//                'description' => 'Get the schedule for a specific team or teams based on filters.',
//                'parameters' => [
//                    'type' => 'object',
//                    'properties' => [
//                        'teamId' => [
//                            'type' => 'string',
//                            'description' => 'The ID of the NFL team to get the schedule for (optional if teamFilter is used).'
//                        ],
//                        'teamFilter' => [
//                            'type' => 'string',
//                            'description' => 'The abbreviation of the NFL team to filter the schedule for (e.g., KC for Kansas City Chiefs).'
//                        ]
//                    ],
//                    'required' => [], // Neither is strictly required; one or the other can be provided
//                    'additionalProperties' => false
//                ]
//            ],
//
//            // NFL get schedule by date range
//            [
//                'name' => 'get_schedule_by_date_range',
//                'description' => 'Get the schedule for a team within a specified date range.',
//                'parameters' => [
//                    'type' => 'object',
//                    'properties' => [
//                        'teamId' => [
//                            'type' => 'string',
//                            'description' => 'The ID of the NFL team.'
//                        ],
//                        'startDate' => [
//                            'type' => 'string',
//                            'description' => 'The start date for the range (YYYY-MM-DD).'
//                        ],
//                        'endDate' => [
//                            'type' => 'string',
//                            'description' => 'The end date for the range (YYYY-MM-DD).'
//                        ]
//                    ],
//                    'required' => [
//                    ],
//                    'additionalProperties' => false
//                ]
//            ], // NFL get schedule by date range

            // Consolidated Schedule Function
            [
                'name' => 'get_schedule',
                'description' => 'Get the NFL team schedule with optional filters for team, season, week, date range, and opponent conference.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamId' => [
                            'type' => 'string',
                            'description' => 'The ID of the NFL team to get the schedule for (optional if teamFilter is used).'
                        ],
                        'teamFilter' => [
                            'type' => 'string',
                            'description' => 'The abbreviation of the NFL team to filter the schedule for (e.g., KC for Kansas City Chiefs).'
                        ],
                        'season' => [
                            'type' => 'string',
                            'description' => 'The NFL season year (default is 2024).'
                        ],
                        'week' => [
                            'type' => 'integer',
                            'description' => 'The specific week number to filter the schedule (optional).'
                        ],
                        'startDate' => [
                            'type' => 'string',
                            'format' => 'date',
                            'description' => 'The start date for the schedule range (YYYY-MM-DD) (optional).'
                        ],
                        'endDate' => [
                            'type' => 'string',
                            'format' => 'date',
                            'description' => 'The end date for the schedule range (YYYY-MM-DD) (optional).'
                        ],
                        'conferenceFilter' => [
                            'type' => 'string',
                            'description' => 'Filter opponents by conference (e.g., AFC, NFC) (optional).'
                        ],
                        'timeFrame' => [
                            'type' => 'string',
                            'description' => 'Relative time frame for the schedule (e.g., "last week", "this week", "in September") (optional).'
                        ],
                    ],
                    'required' => [], // At least one of teamId or teamFilter should be provided
                    'additionalProperties' => true
                ]
            ],


            // NFL get game by ID
            [
                'name' => 'find_game_by_id',
                'description' => 'Find a game by its game ID.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'gameId' => [
                            'type' => 'string',
                            'description' => 'The ID of the game.'
                        ]
                    ],
                    'required' => ['gameId'],
                    'additionalProperties' => false
                ]
            ],

            // NFL get odds by event IDs (Retained)
            [
                'name' => 'get_odds_by_event_ids',
                'description' => 'Retrieve betting odds based on event IDs.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'eventIds' => [
                            'type' => 'array',
                            'description' => 'Array of event IDs.',
                            'items' => [
                                'type' => 'string'
                            ]
                        ]
                    ],
                    'required' => ['eventIds'],
                    'additionalProperties' => false
                ]
            ],

            // NFL get odds by game ID
            [
                'name' => 'get_odds_by_game_id',
                'description' => 'Get betting odds for a specific game ID.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'gameId' => [
                            'type' => 'string',
                            'description' => 'The ID of the game to get betting odds for.'
                        ]
                    ],
                    'required' => ['gameId'],
                    'additionalProperties' => false
                ]
            ],

            // NFL get odds by team and seasons
            [
                'name' => 'get_odds_by_team_and_season',
                'description' => 'Get betting odds for a specific team and season.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamId' => [
                            'type' => 'string',
                            'description' => 'The ID of the NFL team to get betting odds for.'
                        ],
                        'season' => [
                            'type' => 'integer',
                            'description' => 'The season year to get betting odds for.'
                        ]
                    ],
                    'required' => ['teamId', 'season'],
                    'additionalProperties' => false
                ]
            ],

            // NFL get odds by date range (Retained)
            [
                'name' => 'get_odds_by_date_range',
                'description' => 'Retrieve betting odds for games within a specific date range.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'startDate' => [
                            'type' => 'string',
                            'description' => 'The start date in YYYY-MM-DD format.'
                        ],
                        'endDate' => [
                            'type' => 'string',
                            'description' => 'The end date in YYYY-MM-DD format.'
                        ]
                    ],
                    'required' => ['startDate', 'endDate'],
                    'additionalProperties' => false
                ]
            ],

            // NFL player Stats vs. Conference
            [
                'name' => 'get_player_vs_conference_stats',
                'description' => 'Get player statistics broken down by conference (AFC vs NFC) matchups.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamFilter' => [
                            'type' => 'string',
                            'description' => 'The team abbreviation to filter by (optional).'
                        ],
                        'conference' => [
                            'type' => 'string',
                            'description' => 'The conference (AFC or NFC) to filter matchups against (optional).'
                        ],
                        'playerFilter' => [
                            'type' => 'string',
                            'description' => 'The player identifier to filter statistics for a specific player (optional).'
                        ]
                    ],
                    'required' => [], // Leave empty if all properties are optional
                    'additionalProperties' => false
                ]
            ],

            // NFL players by team
            [
                'name' => 'find_players_by_team',
                'description' => 'Retrieve NFL players based on a specific team identifier or abbreviation.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamId' => [
                            'type' => 'string',
                            'description' => 'Unique identifier for the team.'
                        ],
                        'teamFilter' => [
                            'type' => 'string',
                            'description' => 'Team abbreviation for filtering players.'
                        ]
                    ],
                    'required' => ['teamId'], // Corrected from 'team' to 'teamId'
                    'additionalProperties' => false
                ]
            ],

            // NFL players by age range
            [
                'name' => 'find_players_by_age_range',
                'description' => 'Retrieve NFL players within a specified age range.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'minAge' => [
                            'type' => 'integer',
                            'description' => 'Minimum age of players when filtering.'
                        ],
                        'maxAge' => [
                            'type' => 'integer',
                            'description' => 'Maximum age of players when filtering.'
                        ]
                    ],
                    'required' => [],
                    'additionalProperties' => false
                ]
            ],

            // NFL players by injury status
            [
                'name' => 'find_players_by_injury_status',
                'description' => 'Retrieve NFL players based on their injury designation.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'injuryDesignation' => [
                            'type' => 'string',
                            'description' => 'Injury designation of the player.'
                        ]
                    ],
                    'required' => ['injuryDesignation'],
                    'additionalProperties' => false
                ]
            ],

            // NFL players by position
            [
                'name' => 'find_players_by_position',
                'description' => 'Retrieve players based on their position.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'position' => [
                            'type' => 'string',
                            'description' => 'Position of the player.'
                        ]
                    ],
                    'required' => ['position'],
                    'additionalProperties' => false
                ]
            ],

            // NFL players by experience
            [
                'name' => 'find_players_by_experience',
                'description' => 'Find NFL players with a specific number of years of experience, optionally filtered by team.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'years' => [
                            'type' => 'integer',
                            'description' => 'The number of years of experience to filter by.',
                        ],
                        'teamFilter' => [
                            'type' => 'string',
                            'description' => 'The abbreviation of the NFL team to filter players by (optional).',
                        ],
                    ],
                    'required' => ['years'], // Only years is required
                ],
            ],


            // NFL players by School
            [
                'name' => 'find_players_by_school',
                'description' => 'Retrieve players based on the school they attended.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'school' => [
                            'type' => 'string',
                            'description' => 'School the player attended.'
                        ]
                    ],
                    'required' => ['school'],
                    'additionalProperties' => false
                ]
            ],

            // NFL players by ID
            [
                'name' => 'find_player_by_id',
                'description' => 'Retrieve a player based on their unique identifier.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'playerId' => [
                            'type' => 'string',
                            'description' => 'Unique identifier for the player.'
                        ]
                    ],
                    'required' => ['playerId'],
                    'additionalProperties' => false
                ]
            ],

            [
                'name' => 'get_team_injuries',
                'description' => 'Retrieve a list of injured players for a specific NFL team.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamFilter' => [
                            'type' => 'string',
                            'description' => 'The abbreviation of the NFL team to retrieve injury data for.'
                        ],
                    ],
                    'required' => ['teamFilter']
                ],
            ],


            // NFL predictions by team
            [
                'name' => 'get_predictions_by_team',
                'description' => 'Retrieve Elo predictions for a specific team, filtered by team abbreviation, week, date range, and opponent.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'team_abv' => [
                            'type' => 'string',
                            'description' => 'The abbreviation of the team to filter by (optional).'
                        ],
                        'start_date' => [
                            'type' => 'string',
                            'format' => 'date',
                            'description' => 'The start date for filtering predictions in the format YYYY-MM-DD (optional).'
                        ],
                        'end_date' => [
                            'type' => 'string',
                            'format' => 'date',
                            'description' => 'The end date for filtering predictions in the format YYYY-MM-DD (optional).'
                        ],
                        'opponent' => [
                            'type' => 'string',
                            'description' => 'The abbreviation of the opponent team to filter by (optional).'
                        ],
                        'week' => [
                            'type' => 'integer',
                            'description' => 'The week number for filtering predictions (optional).'
                        ]
                    ],
                    'required' => [], // No required parameters; all are optional
                    'additionalProperties' => false
                ]
            ],

            [
                'name' => 'find_by_espn_name',
                'description' => 'Retrieve details for a specific NFL player by their ESPN name.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'espnName' => [
                            'type' => 'string',
                            'description' => 'The ESPN display name of the NFL player.'
                        ],
                    ],
                    'required' => ['espnName']
                ],
            ],

//            [
//                'name' => 'get_free_agents',
//                'description' => 'Retrieve a list of NFL players who are currently free agents.',
//                'parameters' => [
//                    //'type' => 'object',
//                    'properties' => [], // No properties needed for this function
//                    'required' => [] // No required properties
//                ],
//            ],


            // NFL get odds by team
            [
                'name' => 'get_odds_by_team',
                'description' => 'Retrieve betting odds for a specific team.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamFilter' => [
                            'type' => 'string',
                            'description' => 'Team abbreviation.'
                        ]
                    ],
                    'required' => ['teamFilter'],
                    'additionalProperties' => false
                ]
            ],

            // NFL get odds by week
            [
                'name' => 'get_odds_by_week',
                'description' => 'Retrieve betting odds for games in a specific week.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'week' => [
                            'type' => 'integer',
                            'description' => 'The week number.'
                        ]
                    ],
                    'required' => ['week'],
                    'additionalProperties' => false
                ]
            ],

            [
                'name' => 'get_odds_by_team_and_week',
                'description' => 'Retrieve betting odds for a specific team in a specific week. Returns moneyline, spread, and totals data for matching games.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamFilter' => [
                            'type' => 'string',
                            'description' => 'Team abbreviation to filter odds (matches either home or away team).',
                            'minLength' => 2,
                            'maxLength' => 3
                        ],
                        'week' => [
                            'type' => 'integer',
                            'description' => 'NFL week number to retrieve odds for.',
                            'minimum' => 1,
                            'maximum' => 18
                        ]
                    ],
                    'required' => ['teamFilter', 'week'],
                    'additionalProperties' => false
                ],
                'returns' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'event_id' => [
                                'type' => 'string',
                                'description' => 'Unique identifier for the game event'
                            ],
                            'game_date' => [
                                'type' => 'string',
                                'format' => 'date-time',
                                'description' => 'Game date and time'
                            ],
                            'home_team' => [
                                'type' => 'string',
                                'description' => 'Home team abbreviation'
                            ],
                            'away_team' => [
                                'type' => 'string',
                                'description' => 'Away team abbreviation'
                            ],
                            'moneyline_home' => [
                                'type' => 'number',
                                'description' => 'Home team moneyline odds'
                            ],
                            'moneyline_away' => [
                                'type' => 'number',
                                'description' => 'Away team moneyline odds'
                            ],
                            'spread_home' => [
                                'type' => 'number',
                                'description' => 'Home team point spread'
                            ],
                            'spread_away' => [
                                'type' => 'number',
                                'description' => 'Away team point spread'
                            ],
                            'total_over' => [
                                'type' => 'number',
                                'description' => 'Over/under total points line - over'
                            ],
                            'total_under' => [
                                'type' => 'number',
                                'description' => 'Over/under total points line - under'
                            ],
                            'implied_total_home' => [
                                'type' => 'number',
                                'description' => 'Implied total points for home team'
                            ],
                            'implied_total_away' => [
                                'type' => 'number',
                                'description' => 'Implied total points for away team'
                            ]
                        ]
                    ]
                ]
            ],

            // NFL get odds by moneyline
            [
                'name' => 'get_odds_by_moneyline',
                'description' => 'Retrieve betting odds for a specific moneyline value.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'moneyline' => [
                            'type' => 'number',
                            'description' => 'The moneyline value.'
                        ]
                    ],
                    'required' => ['moneyline'],
                    'additionalProperties' => false
                ]
            ],

            // NFL get odds by spread
            [
                'name' => 'get_odds_by_spread',
                'description' => 'Retrieve betting odds for a specific spread value.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'spread' => [
                            'type' => 'number',
                            'description' => 'The spread value.'
                        ]
                    ],
                    'required' => ['spread'],
                    'additionalProperties' => false
                ]
            ],

            // NFL get odds by total
            [
                'name' => 'get_odds_by_total',
                'description' => 'Retrieve betting odds for a specific total value.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'total' => [
                            'type' => 'number',
                            'description' => 'The total value.'
                        ]
                    ],
                    'required' => ['total'],
                    'additionalProperties' => false
                ]
            ],

            // NFL get odds by implied total
            [
                'name' => 'get_odds_by_implied_total',
                'description' => 'Retrieve betting odds for a specific implied total value.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'impliedTotal' => [
                            'type' => 'number',
                            'description' => 'The implied total value.'
                        ]
                    ],
                    'required' => ['impliedTotal'],
                    'additionalProperties' => false
                ]
            ],

            // NFL get odds by team and spread
            [
                'name' => 'get_odds_by_team_and_spread',
                'description' => 'Retrieve betting odds for a specific team and spread value.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamFilter' => [
                            'type' => 'string',
                            'description' => 'Team abbreviation.'
                        ],
                        'spread' => [
                            'type' => 'number',
                            'description' => 'The spread value.'
                        ]
                    ],
                    'required' => ['teamFilter', 'spread'],
                    'additionalProperties' => false
                ]
            ],

            // NFL get odds by team and total
            [
                'name' => 'get_odds_by_team_and_total',
                'description' => 'Retrieve betting odds for a specific team and total value.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamFilter' => [
                            'type' => 'string',
                            'description' => 'Team abbreviation.'
                        ],
                        'total' => [
                            'type' => 'number',
                            'description' => 'The total value.'
                        ]
                    ],

                    'required' => ['teamFilter', 'total'],
                ]
            ],

            // NFL scoring by half
            [
                'name' => 'get_half_scoring',
                'description' => 'Retrieve half scoring data for teams, filtered by team, location, conference, division, or opponent.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamFilter' => [
                            'type' => 'string',
                            'description' => 'Filter to specify a team (optional).'
                        ],
                        'locationFilter' => [
                            'type' => 'string',
                            'description' => 'Filter to specify the location (e.g., home, away) (optional).'
                        ],
                        'conferenceFilter' => [
                            'type' => 'string',
                            'description' => 'Filter to specify a conference (e.g., AFC, NFC) (optional).'
                        ],
                        'divisionFilter' => [
                            'type' => 'string',
                            'description' => 'Filter to specify a division (e.g., East, West) (optional).'
                        ],
                        'opponentFilter' => [
                            'type' => 'string',
                            'description' => 'Filter to specify an opponent team (optional).'
                        ]
                    ],
                    'required' => [], // No required fields; all filters are optional
                    'additionalProperties' => false
                ]
            ],

            [
                'name' => 'get_nfl_team_stats',
                'description' => 'Retrieve detailed statistics for a specific NFL team during a particular week. Requires both team abbreviation and week number.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'team_abv' => [
                            'type' => 'string',
                            'description' => 'The abbreviation of the NFL team (e.g., KC for Kansas City Chiefs).'
                        ],
                        'week' => [
                            'type' => 'integer',
                            'description' => 'The week number for which to retrieve the team\'s statistics.'
                        ],
                    ],
                    'required' => ['team_abv', 'week'],
                    'additionalProperties' => false
                ]
            ],

            [
                'name' => 'compare_teams_stats',
                'description' => 'Compare specific statistics between multiple NFL teams for a given week.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'team_abvs' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                                'description' => 'The abbreviation of an NFL team (e.g., KC for Kansas City Chiefs).'
                            ],
                            'description' => 'List of NFL team abbreviations to compare.'
                        ],
                        'stat_column' => [
                            'type' => 'string',
                            'description' => 'The specific statistic to compare (e.g., total_yards, passing_yards).'
                        ],
                        'week' => [
                            'type' => 'integer',
                            'description' => 'The week number for which to compare the teams\' statistics.'
                        ],
                    ],
                    'required' => ['team_abvs', 'stat_column', 'week'],
                    'additionalProperties' => false
                ]
            ],

            [
                'name' => 'get_top_teams_by_stat',
                'description' => 'Retrieve the top N NFL teams based on a specific statistic for a given week.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'stat_column' => [
                            'type' => 'string',
                            'description' => 'The statistic to rank teams by (e.g., total_yards, passing_yards).'
                        ],
                        'week' => [
                            'type' => 'integer',
                            'description' => 'The week number for which to retrieve the top teams.'
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'The number of top teams to retrieve. Defaults to 5.',
                            'default' => 5
                        ],
                    ],
                    'required' => ['stat_column', 'week'],
                    'additionalProperties' => false
                ]
            ],

            [
                'name' => 'get_league_averages',
                'description' => 'Calculate league-wide average statistics for a given week.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'week' => [
                            'type' => 'integer',
                            'description' => 'The week number for which to calculate league averages.'
                        ],
                    ],
                    'required' => ['week'],
                    'additionalProperties' => false
                ]
            ],

            [
                'name' => 'get_team_stat_average',
                'description' => 'Retrieve the average of a specific team statistic per game for one or more teams.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamFilters' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                                'description' => 'The abbreviation of an NFL team (e.g., KC for Kansas City Chiefs).'
                            ],
                            'description' => 'An array of team abbreviations to retrieve averages for.',
                            'minItems' => 1
                        ],
                        'statColumn' => [
                            'type' => 'string',
                            'description' => 'The statistic to calculate averages for (e.g., first_downs, rushing_yards).'
                        ],
                        'week' => [
                            'type' => 'integer',
                            'description' => 'Specific week number to filter the results (optional).'
                        ],
                        'season' => [
                            'type' => 'integer',
                            'description' => 'The season year to filter results (optional). Defaults to the current season.'
                        ]
                    ],
                    'required' => ['teamFilters']
                ]
            ],

            [
                'name' => 'check_team_prediction',
                'description' => 'Check if a specific NFL team is predicted to win their game this week.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'team_abv' => [
                            'type' => 'string',
                            'description' => 'The abbreviation of the team to check (e.g., KC for Kansas City Chiefs).'
                        ],
                        'week' => [
                            'type' => 'integer',
                            'description' => 'The week number to check predictions for. If not provided, defaults to current week.'
                        ],
                        'include_stats' => [
                            'type' => 'boolean',
                            'description' => 'Whether to include detailed team statistics in the prediction response.',
                            'default' => false
                        ],
                        'include_factors' => [
                            'type' => 'boolean',
                            'description' => 'Whether to include key factors affecting the prediction.',
                            'default' => false
                        ]
                    ],
                    'required' => ['team_abv'],
                    'additionalProperties' => false
                ]
            ],


            [
                'name' => 'get_receiving_stats',
                'description' => 'Get receiving statistics for NFL players',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'long_name' => [
                            'type' => 'string',
                            'description' => 'Player full name (e.g., "A.J. Brown")'
                        ],
                        'team_abv' => [
                            'type' => 'string',
                            'description' => 'Team abbreviation'
                        ]
                    ],
                    'required' => ['long_name']
                ]
            ],


            [
                'name' => 'get_rushing_stats',
                'description' => 'Get rushing statistics for NFL players',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'long_name' => [
                            'type' => 'string',
                            'description' => 'Player full name (e.g., "Josh Allen")',
                        ],
                        'team_abv' => [
                            'type' => 'string',
                            'description' => 'Team abbreviation (e.g., "BUF")',
                        ],
                    ],
                    'required' => ['long_name'], // Make long_name required
                ],
            ],

            [
                'name' => 'get_defense_stats',
                'description' => 'Fetch defensive statistics for NFL players including tackles, sacks, and interceptions.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'long_name' => [
                            'type' => 'string',
                            'description' => 'Player full name (e.g., "Aaron Donald"). Required for player-specific stats.',
                        ],
                        'team_abv' => [
                            'type' => 'string',
                            'description' => 'Optional team abbreviation to filter stats for a specific team (e.g., "LAR").',
                        ],
                    ],
                    'required' => ['long_name'], // Only `long_name` is required
                    'additionalProperties' => false, // Ensure no extra parameters are accepted
                ],
            ],
            [
                'name' => 'get_kicking_stats',
                'description' => 'Get kicking statistics for NFL players including field goals, extra points, and touchbacks.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'long_name' => [
                            'type' => 'string',
                            'description' => 'Player full name (e.g., "Harrison Butker"). Required for player-specific stats.',
                        ],
                        'team_abv' => [
                            'type' => 'string',
                            'description' => 'Optional team abbreviation to filter stats for a specific team.'
                        ]
                    ],
                    'required' => ['long_name'], // Only `long_name` is required
                    'additionalProperties' => false
                ],
            ],


            [
                'name' => 'get_punting_stats',
                'description' => 'Get punting statistics for NFL players including yards, attempts, and blocked punts.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'long_name' => [
                            'type' => 'string',
                            'description' => 'Player full name (e.g., "Matthew Wright"). Required for player-specific stats.',
                        ],
                        'team_abv' => [
                            'type' => 'string',
                            'description' => 'Optional team abbreviation to filter stats for a specific team.'
                        ]
                    ],
                    'required' => ['long_name'], // Only `long_name` is required
                    'additionalProperties' => false
                ],

            ],

            [
                'name' => 'get_player_vs_conference',
                'description' => 'Get player statistics split by opponent conference (AFC vs NFC).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamFilter' => [
                            'type' => 'string',
                            'description' => 'Optional team abbreviation to filter stats.'
                        ],
                        'playerFilter' => [
                            'type' => 'string',
                            'description' => 'Optional player name to filter stats.'
                        ]
                    ],

                    'additionalProperties' => false
                ],
                'returns' => [
                    'type' => 'object',
                    'properties' => [
                        'data' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'player' => ['type' => 'string'],
                                    'team_abv' => ['type' => 'string'],
                                    'conference' => ['type' => 'string'],
                                    'division' => ['type' => 'string'],
                                    'location_type' => ['type' => 'string'],
                                    'afc_games' => ['type' => 'integer'],
                                    'afc_receiving_yards' => ['type' => 'number'],
                                    'afc_rushing_yards' => ['type' => 'number'],
                                    'afc_receiving_tds' => ['type' => 'integer'],
                                    'afc_rushing_tds' => ['type' => 'integer'],
                                    'afc_avg_tackles' => ['type' => 'number'],
                                    'afc_sacks' => ['type' => 'integer'],
                                    'afc_ints' => ['type' => 'integer'],
                                    'nfc_games' => ['type' => 'integer'],
                                    'nfc_receiving_yards' => ['type' => 'number'],
                                    'nfc_rushing_yards' => ['type' => 'number'],
                                    'nfc_receiving_tds' => ['type' => 'integer'],
                                    'nfc_rushing_tds' => ['type' => 'integer'],
                                    'nfc_avg_tackles' => ['type' => 'number'],
                                    'nfc_sacks' => ['type' => 'integer'],
                                    'nfc_ints' => ['type' => 'integer']
                                ]
                            ]
                        ],
                        'headings' => [
                            'type' => 'array',
                            'items' => ['type' => 'string']
                        ]
                    ]
                ]
            ],

            // College Basketball Functions
            [
                'name' => 'get_college_game_predictions',
                'description' => 'Retrieve game predictions for college basketball based on game ID or team abbreviations.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'game_id' => [
                            'type' => 'string',
                            'description' => 'The ID of the game to retrieve predictions for (optional).'
                        ],
                        'home_team_abv' => [
                            'type' => 'string',
                            'description' => 'Abbreviation of the home team (optional).'
                        ],
                        'away_team_abv' => [
                            'type' => 'string',
                            'description' => 'Abbreviation of the away team (optional).'
                        ],
                        'season' => [
                            'type' => 'integer',
                            'description' => 'The season year to filter predictions (optional).'
                        ]
                    ],
                    'required' => [], // All fields are optional
                    'additionalProperties' => false
                ]
            ],
            [
                'name' => 'analyze_team_performance',
                'description' => 'Analyze the performance of a college basketball team based on various metrics.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'team_abv' => [
                            'type' => 'string',
                            'description' => 'Abbreviation of the team to analyze.'
                        ],
                        'season' => [
                            'type' => 'integer',
                            'description' => 'The season year to analyze.'
                        ],
                        'metrics' => [
                            'type' => 'array',
                            'description' => 'List of performance metrics to analyze.',
                            'items' => [
                                'type' => 'string',
                                'enum' => ['offense', 'defense', 'rebounds', 'assists', 'turnovers']
                            ]
                        ]
                    ],
                    'required' => ['team_abv', 'season'],
                    'additionalProperties' => false
                ]
            ],
            // Add more functions as needed...
            // College Basketball Function: Get Predictions by Date
            [
                'name' => 'get_college_game_predictions_by_date',
                'description' => 'Retrieve college basketball game predictions for today or this week based on game dates.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'time_frame' => [
                            'type' => 'string',
                            'description' => 'The time frame for which to retrieve predictions. Options: "today", "this_week".',
                            'enum' => ['today', 'this_week']
                        ],
                        'season' => [
                            'type' => 'integer',
                            'description' => 'The season year to filter predictions (optional).'
                        ],
                        'team_abv' => [
                            'type' => 'string',
                            'description' => 'Abbreviation of a specific team to filter predictions (optional).'
                        ]
                    ],
                    'required' => ['time_frame'], // 'time_frame' is required
                    'additionalProperties' => false
                ]
            ],

            [
                'name' => 'get_upcoming_games',
                'description' => 'Retrieve a list of upcoming college basketball games based on date, team, or location filters.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'time_frame' => [
                            'type' => 'string',
                            'description' => 'The time frame for which to retrieve upcoming games. Options: "today", "this_week", "next_week", "custom".',
                            'enum' => ['today', 'this_week', 'next_week', 'custom']
                        ],
                        'start_date' => [
                            'type' => 'string',
                            'format' => 'date',
                            'description' => 'The start date for custom time frame in YYYY-MM-DD format (required if time_frame is "custom").'
                        ],
                        'end_date' => [
                            'type' => 'string',
                            'format' => 'date',
                            'description' => 'The end date for custom time frame in YYYY-MM-DD format (required if time_frame is "custom").'
                        ],
                        'team_abv' => [
                            'type' => 'string',
                            'description' => 'Abbreviation of a specific team to filter games (optional).'
                        ],
                        'location' => [
                            'type' => 'string',
                            'description' => 'Location to filter games (e.g., "Home", "Away", "Neutral") (optional).'
                        ]
                    ],
                    'required' => ['time_frame'],
                    'additionalProperties' => false
                ]
            ],
            [
                'name' => 'get_game_details',
                'description' => 'Fetch detailed information about a specific college basketball game using game ID.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'game_id' => [
                            'type' => 'string',
                            'description' => 'The unique identifier of the game.'
                        ]
                    ],
                    'required' => ['game_id'],
                    'additionalProperties' => false
                ]
            ],
            [
                'name' => 'get_completed_games',
                'description' => 'Retrieve a list of completed college basketball games with their final scores and statistics.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'time_frame' => [
                            'type' => 'string',
                            'description' => 'The time frame for which to retrieve completed games. Options: "today", "this_week", "last_week", "custom".',
                            'enum' => ['today', 'this_week', 'last_week', 'custom']
                        ],
                        'start_date' => [
                            'type' => 'string',
                            'format' => 'date',
                            'description' => 'The start date for custom time frame in YYYY-MM-DD format (required if time_frame is "custom").'
                        ],
                        'end_date' => [
                            'type' => 'string',
                            'format' => 'date',
                            'description' => 'The end date for custom time frame in YYYY-MM-DD format (required if time_frame is "custom").'
                        ],
                        'team_abv' => [
                            'type' => 'string',
                            'description' => 'Abbreviation of a specific team to filter games (optional).'
                        ],
                        'location' => [
                            'type' => 'string',
                            'description' => 'Location to filter games (e.g., "Home", "Away", "Neutral") (optional).'
                        ]
                    ],
                    'required' => ['time_frame'],
                    'additionalProperties' => false
                ]
            ],
            [
                'name' => 'analyze_team_performance',
                'description' => 'Analyze the performance of a college basketball team based on various metrics over a season or specific games.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'team_abv' => [
                            'type' => 'string',
                            'description' => 'Abbreviation of the team to analyze.'
                        ],
                        'season' => [
                            'type' => 'integer',
                            'description' => 'The season year to analyze (optional).',
                            'default' => Config('college_basketball.season') // Assuming you have this config
                        ],
                        'metrics' => [
                            'type' => 'array',
                            'description' => 'List of performance metrics to analyze.',
                            'items' => [
                                'type' => 'string',
                                'enum' => ['points_scored', 'points_allowed', 'rebounds', 'assists', 'turnovers', 'steals', 'blocks']
                            ]
                        ]
                    ],
                    'required' => ['team_abv'],
                    'additionalProperties' => false
                ]
            ],

            // You can add more functions as needed...


        ];
    }
}
