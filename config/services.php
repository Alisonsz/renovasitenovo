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

    'pagbank' => [
        'env' => env('PAGBANK_ENV', 'production'),
        'token' => env('PAGBANK_TOKEN'),
        'notification_url' => env('PAGBANK_NOTIFICATION_URL'),
        'redirect_base_url' => env('PAGBANK_REDIRECT_BASE_URL', env('APP_URL')),
        'pix_discount_percent' => (int) env('PAGBANK_PIX_DISCOUNT_PERCENT', 5),
        'max_installments' => (int) env('PAGBANK_MAX_INSTALLMENTS', 12),
        'min_installment_cents' => (int) env('PAGBANK_MIN_INSTALLMENT_CENTS', 7800),
        'soft_descriptor' => env('PAGBANK_SOFT_DESCRIPTOR', 'RenovaLaserDepil'),
    ],

];
