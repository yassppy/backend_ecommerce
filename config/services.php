<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'paypal' => [
        'mode' => env('PAYPAL_MODE', 'sandbox'),
        'client_id' => env('PAYPAL_MODE', 'sandbox') === 'live'
            ? env('PAYPAL_LIVE_CLIENT_ID')
            : env('PAYPAL_SANDBOX_CLIENT_ID'),
        'client_secret' => env('PAYPAL_MODE', 'sandbox') === 'live'
            ? env('PAYPAL_LIVE_CLIENT_SECRET')
            : env('PAYPAL_SANDBOX_CLIENT_SECRET'),
        'currency' => env('PAYPAL_CURRENCY', 'USD'),
        'return_base_url' => env('PAYPAL_RETURN_BASE_URL', 'http://127.0.0.1:8000'),
    ],
];
