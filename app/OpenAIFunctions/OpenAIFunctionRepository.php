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
                'description' => 'Get recent games for a specific team.',
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
            [
                'name' => 'get_best_receivers',
                'description' => 'Get the top NFL receivers based on receiving yards, receptions, and touchdowns.',
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
            [
                'name' => 'get_best_rushers',
                'description' => 'Get the top NFL rushers based on rushing yards, attempts, and touchdowns.',
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
            [
                'name' => 'get_team_matchup_edge',
                'description' => 'Get matchup edge information for a specified NFL team.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamFilter' => [
                            'type' => 'string',
                            'description' => 'The team abbreviation to filter by.'
                        ]
                    ],
                    'required' => ['teamFilter']
                ]
            ],
            [
                'name' => 'get_first_half_tendencies',
                'description' => 'Get first-half tendencies for a specified team, including average points scored and allowed.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamFilter' => [
                            'type' => 'string',
                            'description' => 'The team abbreviation to filter by.'
                        ]
                    ],
                    'required' => ['teamFilter']
                ]
            ],
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
//            [
//                'name' => 'get_team_injuries',
//                'description' => 'Get the list of injured players for a specific team.',
//                'parameters' => [
//                    'type' => 'object',
//                    'properties' => [
//                        'teamId' => [
//                            'type' => 'string',
//                            'description' => 'The ID of the NFL team to get injury information for.'
//                        ]
//                    ],
//                    'required' => ['teamId']
//                ]
//            ],
//            [
//                'name' => 'find_player_by_id',
//                'description' => 'Find a player by their player ID.',
//                'parameters' => [
//                    'type' => 'object',
//                    'properties' => [
//                        'playerId' => [
//                            'type' => 'string',
//                            'description' => 'The ID of the player.'
//                        ]
//                    ],
//                    'required' => ['playerId']
//                ]
//            ],
            [
                'name' => 'find_players_by_injury_status',
                'description' => 'Find players by their injury designation.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'injuryDesignation' => [
                            'type' => 'string',
                            'description' => 'The injury designation to filter by (e.g., "Questionable", "Out").'
                        ]
                    ],
                    'required' => ['injuryDesignation']
                ]
            ],
            [
                'name' => 'find_players_by_position',
                'description' => 'Find players by their position.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'position' => [
                            'type' => 'string',
                            'description' => 'The position to filter by (e.g., "QB", "RB", "WR").'
                        ]
                    ],
                    'required' => ['position']
                ]
            ],
            
            [
                'name' => 'find_players_by_experience',
                'description' => 'Find players by their number of years of experience.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'years' => [
                            'type' => 'integer',
                            'description' => 'The number of years of experience to filter by.'
                        ]
                    ],
                    'required' => ['years']
                ]
            ],
            [
                'name' => 'find_players_by_age_range',
                'description' => 'Find players within a specific age range.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'minAge' => [
                            'type' => 'integer',
                            'description' => 'The minimum age to filter by.'
                        ],
                        'maxAge' => [
                            'type' => 'integer',
                            'description' => 'The maximum age to filter by.'
                        ]
                    ],
                    'required' => ['minAge', 'maxAge']
                ]
            ],

            [
                'name' => 'get_player_vs_conference_stats',
                'description' => 'Get player statistics broken down by conference (AFC vs NFC) matchups.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'teamFilter' => [
                            'type' => 'string',
                            'description' => 'The team abbreviation to filter by (optional).'
                        ]
                    ]
                ]
            ]

        ];
    }
}
