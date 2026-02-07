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

    'pneadm' => [
        'api_url' => env('PNEADM_API_URL'),
        'api_token' => env('PNEADM_API_TOKEN'),
        'timeout' => env('PNEADM_API_TIMEOUT', 30),
    ],

    'payu' => [
        'sandbox' => filter_var(env('PAYU_SANDBOX', true), FILTER_VALIDATE_BOOLEAN),
        'pos_id' => env('PAYU_POS_ID'),
        'second_key' => env('PAYU_SECOND_KEY'),
        'client_id' => env('PAYU_CLIENT_ID'),
        'client_secret' => env('PAYU_CLIENT_SECRET'),
        // Sandbox ma osobne POS – jeśli 4421299 nie działa w sandbox, użyj produkcji (PAYU_SANDBOX=false)
        'base_url' => filter_var(env('PAYU_SANDBOX', true), FILTER_VALIDATE_BOOLEAN) ? 'https://secure.snd.payu.com' : 'https://secure.payu.com',
    ],

];
