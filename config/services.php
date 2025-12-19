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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'scheduler' => [
        'schedule_user_email' => env('SCHEDULE_USER_EMAIL'),
    ],
    'ai_client' => [
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY')
        ],

        'open_ai' => [
            'api_key' => env('OPEN_AI_API_KEY')
        ],

        'deep_seek' => [
            'api_key' => env('DEEP_SEEK_API_KEY')
        ],

        'grok' => [
            'api_key' => env('GROK_API_KEY')
        ],
    ],

];
