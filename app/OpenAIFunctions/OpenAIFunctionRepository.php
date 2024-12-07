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
                'name' => 'get_recent_games',
                'description' => 'Get recent NFL games for a specific NFL team.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamId' => [
                            'type' => 'integer',
                            'description' => 'The ID of the NFL team to get recent games for.'
                        ],
                        'gamesBack' => [
                            'type' => 'integer',
                            'description' => 'The number of recent games to retrieve (default is 3).',
                            'default' => 3
                        ]
                    ],
                    'required' => ['teamId']
                ]
            ],

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
                'description' => 'Retrieves the best receivers based on receiving statistics filtered by team and week(s).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamFilter' => [
                            'type' => 'string',
                            'description' => 'The abbreviation of the team to filter the results. Optional.'
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
                        ]
                    ],
                    'required' => [] // If no required fields, this can be left empty
                ]
            ],

            // NFL best tacklers by week,
            [
                'name' => 'get_best_tacklers',
                'description' => 'Retrieves the best tacklers based on defensive statistics filtered by team and week(s).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamFilter' => [
                            'type' => 'string',
                            'description' => 'The abbreviation of the team to filter the results. Optional.'
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
                        ]
                    ],
                    'required' => [] // If no required fields, this can be left empty
                ]
            ],

            // NFL best rushers by week,
            [
                'name' => 'get_best_rushers',
                'description' => 'Retrieves the best rushers based on rushing statistics filtered by team and week(s).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamFilter' => [
                            'type' => 'string',
                            'description' => 'The abbreviation of the team to filter the results. Optional.'
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
                        ]
                    ],
                    'required' => [] // If no required fields, this can be left empty
                ]
            ],

            // NFL situational stats by team
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
                    'required' => ['teamFilter'], // Only teamFilter is required; others are optional
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
            [
                'name' => 'get_schedule_by_team',
                'description' => 'Get the schedule for a specific team.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamId' => [
                            'type' => 'string',
                            'description' => 'The ID of the NFL team to get the schedule for.'
                        ]
                    ],
                    'required' => ['teamId']
                ]
            ],

            // NFL get schedule by date range
            [
                'name' => 'get_schedule_by_date_range',
                'description' => 'Get the schedule for a team within a specified date range.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamId' => [
                            'type' => 'string',
                            'description' => 'The ID of the NFL team.'
                        ],
                        'startDate' => [
                            'type' => 'string',
                            'description' => 'The start date for the range (YYYY-MM-DD).'
                        ],
                        'endDate' => [
                            'type' => 'string',
                            'description' => 'The end date for the range (YYYY-MM-DD).'
                        ]
                    ],
                    'required' => ['teamId', 'startDate', 'endDate']
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
                    'required' => ['gameId']
                ]
            ],

            // NFL get odds by event IDs
            [
                'name' => 'get_odds_by_event_ids',
                'description' => 'Get betting odds for specific event IDs.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'eventIds' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string'
                            ],
                            'description' => 'The list of event IDs to get betting odds for.'
                        ]
                    ],
                    'required' => ['eventIds']
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
                    'required' => ['gameId']
                ]
            ],

            // NFL get odds by team and seaons
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
                    'required' => ['teamId', 'season']
                ]
            ],

            // NFL get odds by date range
            [
                'name' => 'get_odds_by_date_range',
                'description' => 'Get betting odds within a specified date range.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'startDate' => [
                            'type' => 'string',
                            'description' => 'The start date for the range (YYYY-MM-DD).'
                        ],
                        'endDate' => [
                            'type' => 'string',
                            'description' => 'The end date for the range (YYYY-MM-DD).'
                        ]
                    ],
                    'required' => ['startDate', 'endDate']
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
                    'required' => ['team'],
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
                'description' => 'Retrieve players based on their years of experience.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'years' => [
                            'type' => 'integer',
                            'description' => 'Number of years of experience.'
                        ]
                    ],
                    'required' => ['years'],
                    'additionalProperties' => false
                ]
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

            // NFL odds by event IDs
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

            // NFL get odds by date range
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

            //NFL get odds by moneyline
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
                    'additionalProperties' => false
                ]
            ],

        ];
    }
}
