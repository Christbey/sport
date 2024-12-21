<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'discord' => [
        // 'webhook_url' => env('DISCORD_WEBHOOK_URL'),
        'token' => env('DISCORD_BOT_TOKEN'),
        'channel_id' => env('DISCORD_CHANNEL_ID'),
    ],

    'rapidapi' => [
        'key' => env('RAPIDAPI_KEY'),
        'host' => env('RAPIDAPI_HOST'),
    ],

    'college_football_data' => [
        'key' => env('COLLEGE_FOOTBALL_DATA_API_KEY'),
        'host' => env('COLLEGE_FOOTBALL_DATA_HOST'),
        'fpi_url' => env('COLLEGE_FOOTBALL_FPI_URL', 'https://apinext.collegefootballdata.com/ratings/fpi'), // Add the FPI URL here
    ],

    'forge' => [
        'token' => env('FORGE_API_TOKEN'),
        'base_uri' => 'https://forge.laravel.com/api/v1/', // Forge API base URL
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'base_uri' => env('OPENAI_BASE_URI', 'https://api.openai.com'),
        'temperature' => env('OPENAI_TEMPERATURE', 0.7),
    ],

    'stripe' => [
        'model' => App\Models\User::class,
        'api' => [
            'key' => env('STRIPE_KEY'),
            'secret' => env('STRIPE_SECRET'),
        ],
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
    ],


];
