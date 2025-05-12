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

    'phonepe' => [
        'client_id'      => env('PHONEPE_CLIENT_ID'),
        'client_secret'  => env('PHONEPE_CLIENT_SECRET'),
        'salt_key'       => env('PHONEPE_SALT_KEY'),
        'salt_index'     => env('PHONEPE_SALT_INDEX'),
        'redirect_url'   => env('PHONEPE_REDIRECT_URL'),
        'callback_url'   => env('PHONEPE_CALLBACK_URL'),
        'env'            => env('PHONEPE_ENV', 'prod'),

        'base_url' => 'https://api.phonepe.com/apis/hermes/pg/v1/pay', // <<== FOR PRODUCTION ONLY
    ],
];
